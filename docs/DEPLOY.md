# Hướng dẫn triển khai Production — RebateMailer-V3

Tài liệu này hướng dẫn triển khai **RebateMailer-V3** lên máy chủ thực chạy
**Ubuntu 24.04 / 26.04 LTS** bằng Docker và Docker Compose.

---

## Kiến trúc Docker

```
Internet
    │
    ▼ :80 (hoặc :443)
┌─────────┐
│  nginx  │  Nginx 1.26 — Serve static files, proxy PHP
└────┬────┘
     │ FastCGI :9000
     ▼
┌─────────┐        ┌──────────┐     ┌──────────────┐
│   app   │        │  worker  │     │  scheduler   │
│PHP-FPM  │        │queue:work│     │schedule:run  │
│  8.3    │        │          │     │(every 60s)   │
└────┬────┘        └────┬─────┘     └──────┬───────┘
     │                  │                  │
     └──────────────────┴──────────────────┘
                        │
            ┌───────────┴────────────┐
            ▼                        ▼
     ┌────────────┐          ┌──────────────┐
     │ PostgreSQL │          │    Redis 7   │
     │     16     │          │ Queue+Cache  │
     └────────────┘          └──────────────┘
```

| Service     | Image                     | Vai trò                               |
|-------------|---------------------------|---------------------------------------|
| `postgres`  | postgres:16-alpine        | Cơ sở dữ liệu chính                   |
| `redis`     | redis:7-alpine            | Queue, Session, Cache                 |
| `app`       | rebate-mailer-app:latest  | PHP-FPM 8.3 — Xử lý HTTP request     |
| `nginx`     | (build từ Dockerfile)     | Web server, serve static files        |
| `worker`    | rebate-mailer-app:latest  | Queue worker — gửi mail               |
| `scheduler` | rebate-mailer-app:latest  | Cron scheduler (schedule:run)         |
| `migrator`  | rebate-mailer-app:latest  | Chạy migration khi deploy (one-shot)  |

---

## Phần 1 — Cài đặt Docker trên Ubuntu

### 1.1 Cập nhật hệ thống

```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2 Cài đặt Docker Engine

```bash
# Xoá phiên bản cũ nếu có
sudo apt remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true

# Cài dependencies
sudo apt install -y ca-certificates curl gnupg lsb-release

# Thêm Docker GPG key và repo
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
    https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
    | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Cài Docker Engine + Compose plugin
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io \
                   docker-buildx-plugin docker-compose-plugin
```

### 1.3 Thêm user hiện tại vào group docker (không cần sudo)

```bash
sudo usermod -aG docker $USER
newgrp docker          # Áp dụng ngay, hoặc logout/login lại
```

### 1.4 Xác nhận cài thành công

```bash
docker --version              # Docker version 27.x.x
docker compose version        # Docker Compose version v2.x.x
docker run --rm hello-world   # Kiểm tra Docker chạy được
```

---

## Phần 2 — Chuẩn bị source code trên server

### 2.1 Tạo thư mục ứng dụng

```bash
sudo mkdir -p /opt/rebate-mailer
sudo chown $USER:$USER /opt/rebate-mailer
cd /opt/rebate-mailer
```

### 2.2 Đưa source code lên server

**Cách A — Clone từ Git:**
```bash
git clone https://github.com/your-org/RebateMailer-V3.git .
```

**Cách B — Upload bằng rsync (từ máy dev):**
```bash
# Chạy lệnh này trên máy DEVELOPMENT, không phải server
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' \
    /path/to/RebateMailer-V3/ user@server-ip:/opt/rebate-mailer/
```

**Cách C — SCP file zip:**
```bash
# Trên máy dev: đóng gói
tar -czf rebate-mailer.tar.gz --exclude='.git' --exclude='node_modules' \
    --exclude='vendor' --exclude='public/build' .

# Upload lên server
scp rebate-mailer.tar.gz user@server-ip:/opt/rebate-mailer/

# Trên server: giải nén
cd /opt/rebate-mailer
tar -xzf rebate-mailer.tar.gz
rm rebate-mailer.tar.gz
```

---

## Phần 3 — Cấu hình môi trường production

### 3.1 Tạo file .env

```bash
cd /opt/rebate-mailer
cp .env.example .env
nano .env    # Hoặc dùng editor khác
```

**Những giá trị BẮT BUỘC phải thay đổi:**

| Key | Giá trị cần đặt |
|-----|-----------------|
| `APP_KEY` | Xem bước 3.2 bên dưới |
| `APP_URL` | URL thực của server, VD: `http://192.168.1.100` hoặc `https://rebate.company.vn` |
| `DB_PASSWORD` | Mật khẩu mạnh cho PostgreSQL |
| `REDIS_PASSWORD` | Mật khẩu mạnh cho Redis |
| `MAIL_HOST` | SMTP server |
| `MAIL_USERNAME` | Tài khoản SMTP |
| `MAIL_PASSWORD` | Mật khẩu SMTP |

**Kiểm tra DB_HOST và REDIS_HOST:**
- `DB_HOST=postgres` — phải để là `postgres` (tên service trong docker-compose)
- `REDIS_HOST=redis` — phải để là `redis` (tên service trong docker-compose)

### 3.2 Tạo APP_KEY

```bash
# Chạy lệnh này để lấy key mới
docker run --rm php:8.3-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# Dán kết quả vào .env tại dòng APP_KEY=
```

### 3.3 Bảo mật file cấu hình

```bash
chmod 600 /opt/rebate-mailer/.env
```

---

## Phần 4 — Build và khởi động

### 4.1 Build Docker images

```bash
cd /opt/rebate-mailer

# Build cả 2 stages: app (PHP-FPM) và web (Nginx)
docker compose build --no-cache
```

> **Lần đầu** sẽ mất 5–10 phút (tải packages). Lần sau nhanh hơn nhờ layer cache.

Kiểm tra images đã build:
```bash
docker images | grep rebate-mailer
# rebate-mailer-app   latest   ...
```

### 4.2 Chạy migration lần đầu

```bash
# Khởi động PostgreSQL và Redis trước
docker compose up -d postgres redis

# Chờ PostgreSQL sẵn sàng (healthcheck)
docker compose ps postgres    # Xem STATUS = healthy

# Chạy migrator (tạo bảng + storage symlink)
docker compose run --rm migrator
```

Nếu thành công sẽ thấy:
```
==> Ensuring storage directories...
==> Running database migrations...
   INFO  Running migrations.
   2026_05_09_000000_create_mail_campaign_tables .. DONE
   ...
==> Creating storage symlink...
==> Migrator complete.
```

### 4.3 Khởi động toàn bộ stack

```bash
docker compose up -d
```

Kiểm tra tất cả services đang chạy:
```bash
docker compose ps
```

Kết quả mong đợi:
```
NAME                   IMAGE                     STATUS
rebate-mailer-app      rebate-mailer-app:latest  Up (healthy)
rebate-mailer-nginx    (build)                   Up
rebate-mailer-worker   rebate-mailer-app:latest  Up
rebate-mailer-scheduler rebate-mailer-app:latest Up
rebate-mailer-postgres postgres:16-alpine         Up (healthy)
rebate-mailer-redis    redis:7-alpine             Up (healthy)
```

---

## Phần 5 — Xác nhận deployment

### 5.1 Kiểm tra ứng dụng chạy

```bash
# Kiểm tra health check endpoint
curl -i http://localhost/up
# Kết quả mong đợi: HTTP/1.1 200 OK

# Kiểm tra trang login
curl -I http://localhost/
# Kết quả mong đợi: HTTP/1.1 302 Found (redirect đến /login)
```

### 5.2 Kiểm tra logs

```bash
# Xem logs của tất cả services
docker compose logs -f

# Xem logs từng service
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f worker
docker compose logs -f scheduler
```

### 5.3 Kiểm tra queue worker

```bash
# Xem worker đang chạy và lắng nghe queue
docker compose logs worker
# Mong đợi: [2026-05-11 07:00:00][1] Processing: App\Jobs\...

# Kiểm tra Redis queue
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" llen queues:default
```

### 5.4 Tạo user admin đầu tiên

**Bước 1** — Chạy seeder để tạo roles và permissions:

```bash
docker compose exec app php artisan db:seed --class=RoleAndPermissionSeeder
```

**Bước 2** — Tạo user và gán role admin:

```bash
docker compose exec app php artisan tinker
```

Trong tinker:

```php
$user = \App\Models\User::create([
    'name'     => 'Admin',
    'email'    => 'admin@company.vn',
    'password' => \Illuminate\Support\Facades\Hash::make('your-strong-password'),
]);
$user->assignRole('admin');
exit
```

> **Lưu ý:** Bước 1 phải chạy trước — nếu chạy `assignRole` khi chưa có role sẽ báo lỗi
> `RoleDoesNotExist: There is no role named 'admin' for guard 'web'`.

---

## Phần 6 — Cấu hình HTTPS với SSL (Let's Encrypt)

> Yêu cầu: Server phải có domain trỏ vào IP của server và port 80/443 được mở.

### 6.1 Cài Certbot trên host

```bash
sudo snap install --classic certbot
sudo ln -sf /snap/bin/certbot /usr/bin/certbot
```

### 6.2 Lấy SSL certificate

```bash
# Tạm dừng nginx để certbot có thể dùng port 80
docker compose stop nginx

# Lấy certificate (thay your-domain.com bằng domain thực)
sudo certbot certonly --standalone -d your-domain.com

# Certificate sẽ được lưu tại:
# /etc/letsencrypt/live/your-domain.com/fullchain.pem
# /etc/letsencrypt/live/your-domain.com/privkey.pem
```

### 6.3 Cập nhật docker-compose.yml để mount certificate

Thêm volume vào service `nginx` trong `docker-compose.yml`:

```yaml
nginx:
  build:
    context: .
    target: web
  restart: unless-stopped
  ports:
    - "80:80"
    - "443:443"
  volumes:
    - /etc/letsencrypt:/etc/letsencrypt:ro
  depends_on:
    app:
      condition: service_started
  networks:
    - rebate-net
```

### 6.4 Cập nhật nginx config để dùng HTTPS

Sửa file `docker/nginx/default.conf`, thêm server block HTTPS:

```nginx
# Redirect HTTP → HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    # ... phần còn lại giữ nguyên như file hiện tại ...
}
```

### 6.5 Rebuild nginx và khởi động lại

```bash
docker compose build nginx
docker compose up -d nginx
```

### 6.6 Auto-renew certificate

```bash
# Tạo systemd timer để tự gia hạn cert mỗi 12 giờ
sudo systemctl enable --now certbot.timer

# Test renewal
sudo certbot renew --dry-run
```

---

## Phần 7 — Cập nhật ứng dụng (Redeploy)

Mỗi khi có code mới, thực hiện theo quy trình sau:

```bash
cd /opt/rebate-mailer

# 1. Kéo code mới
git pull origin main

# 2. Build images mới
docker compose build --no-cache

# 3. Chạy migrations (nếu có migration mới)
docker compose run --rm migrator

# 4. Khởi động lại các services với image mới
docker compose up -d --no-deps app nginx worker scheduler

# 5. Xoá images cũ không còn dùng
docker image prune -f
```

> **Không bao giờ** chạy `docker compose down` khi update — sẽ xoá volumes dữ liệu!
> Dùng `docker compose up -d` hoặc `docker compose restart`.

---

## Phần 8 — Quản lý hàng ngày

### Xem logs real-time

```bash
docker compose logs -f                    # Tất cả services
docker compose logs -f worker             # Chỉ worker
docker compose logs --tail=100 app        # 100 dòng cuối của app
```

### Xem logs Laravel trong storage

```bash
docker compose exec app cat storage/logs/laravel.log | tail -50
# Hoặc:
docker compose exec app tail -f storage/logs/laravel.log
```

### Restart service

```bash
docker compose restart app
docker compose restart worker
docker compose restart nginx
```

### Chạy Artisan commands

```bash
# Xem danh sách commands
docker compose exec app php artisan list

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Tạo user mới
docker compose exec app php artisan tinker

# Kiểm tra scheduled jobs
docker compose exec app php artisan schedule:list
```

### Kiểm tra trạng thái queue

```bash
# Xem jobs đang pending trong Redis
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" llen queues:default

# Xem failed jobs trong database
docker compose exec app php artisan queue:failed
```

### Dừng và khởi động lại toàn bộ

```bash
# Dừng (KHÔNG xoá volumes)
docker compose stop

# Khởi động lại
docker compose start

# Hoặc:
docker compose down --timeout 30    # Xoá containers nhưng GIỮ volumes
docker compose up -d
```

---

## Phần 9 — Backup và Restore

### 9.1 Backup database

```bash
# Tạo thư mục backup
mkdir -p /opt/rebate-mailer/backups

# Dump database (filename có timestamp)
docker compose exec -T postgres pg_dump \
    -U rebate_mailer rebate_mailer_v3 \
    > /opt/rebate-mailer/backups/db_$(date +%Y%m%d_%H%M%S).sql

# Nén file backup
gzip /opt/rebate-mailer/backups/db_*.sql
```

### 9.2 Tự động backup hàng ngày (crontab)

```bash
crontab -e
```

Thêm dòng sau:
```cron
0 2 * * * cd /opt/rebate-mailer && docker compose exec -T postgres \
    pg_dump -U rebate_mailer rebate_mailer_v3 \
    | gzip > /opt/rebate-mailer/backups/db_$(date +\%Y\%m\%d).sql.gz \
    && find /opt/rebate-mailer/backups -name "*.sql.gz" -mtime +30 -delete
```

### 9.3 Restore database

```bash
# Giải nén (nếu file .gz)
gunzip /opt/rebate-mailer/backups/db_20260511.sql.gz

# Restore
docker compose exec -T postgres psql \
    -U rebate_mailer rebate_mailer_v3 \
    < /opt/rebate-mailer/backups/db_20260511.sql
```

### 9.4 Backup storage volume

```bash
# Backup thư mục storage (uploads, logs)
docker run --rm \
    -v rebate-mailer_app-storage:/source:ro \
    -v /opt/rebate-mailer/backups:/backup \
    alpine tar -czf /backup/storage_$(date +%Y%m%d).tar.gz -C /source .
```

---

## Phần 10 — Xử lý sự cố thường gặp

### Lỗi: Permission denied trên storage/

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Lỗi: 502 Bad Gateway từ Nginx

```bash
# Kiểm tra app container có đang chạy không
docker compose ps app

# Xem logs app
docker compose logs --tail=50 app

# Restart app
docker compose restart app
```

### Lỗi: Queue jobs không được xử lý

```bash
# Kiểm tra worker có chạy không
docker compose ps worker

# Restart worker
docker compose restart worker

# Kiểm tra kết nối Redis từ worker
docker compose exec worker php artisan tinker --execute="Redis::ping();"
```

### Lỗi: Database connection refused

```bash
# Kiểm tra postgres có healthy không
docker compose ps postgres

# Xem logs postgres
docker compose logs postgres

# Thử kết nối tay
docker compose exec postgres psql -U rebate_mailer -d rebate_mailer_v3 -c "\l"
```

### Rebuild hoàn toàn (khi cần thiết)

```bash
# CẢNH BÁO: Lệnh này xoá TOÀN BỘ dữ liệu!
# Chỉ dùng khi cần reset hoàn toàn môi trường

# Backup trước!
docker compose exec -T postgres pg_dump -U rebate_mailer rebate_mailer_v3 > backup_before_reset.sql

# Xoá tất cả (containers + volumes + images)
docker compose down -v --rmi all

# Build và deploy lại từ đầu
docker compose build --no-cache
docker compose up -d postgres redis
docker compose run --rm migrator
docker compose up -d
```

---

## Tóm tắt commands nhanh

```bash
# ── Lần đầu deploy ──────────────────────────────────────────────────────────
cp .env.example .env && nano .env
docker compose build --no-cache
docker compose up -d postgres redis
docker compose run --rm migrator
docker compose up -d
docker compose exec app php artisan db:seed --class=RoleAndPermissionSeeder
docker compose exec app php artisan tinker  # tạo user admin (xem Phần 5.4)

# ── Update code mới ─────────────────────────────────────────────────────────
git pull && docker compose build --no-cache
docker compose run --rm migrator
docker compose up -d --no-deps app nginx worker scheduler

# ── Monitoring ──────────────────────────────────────────────────────────────
docker compose ps
docker compose logs -f

# ── Maintenance ─────────────────────────────────────────────────────────────
docker compose exec app php artisan cache:clear
docker compose restart worker

# ── Backup ──────────────────────────────────────────────────────────────────
docker compose exec -T postgres pg_dump -U rebate_mailer rebate_mailer_v3 > backup.sql
```
