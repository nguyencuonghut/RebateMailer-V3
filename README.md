# RebateMailer V3

Nen tang backend cho he thong tu dong gui mail chiet khau hang thang.

## Trang thai nen tang

Trang thai hien tai:

- Laravel 13 da duoc scaffold vao repo.
- Database mac dinh la PostgreSQL.
- Cache, session, va queue mac dinh da duoc cau hinh qua Redis.
- InertiaJS da duoc noi vao Laravel.
- Frontend entry da chay voi Vue 3 va TypeScript.
- Route `/` dang render mot trang Inertia de xac minh stack end-to-end.
- PrimeVue v4 da duoc wired vao app.
- Dashboard shell da duoc nang cap theo cau truc layout Sakai (topbar, sidebar, content).
- Mailpit da duoc cau hinh lam SMTP local de kiem thu luong gui mail.
- RBAC da duoc cai dat bang Spatie Laravel Permission voi 3 role mac dinh: Admin, Nguoi dung, Khach.
- Dang nhap da duoc scaffold bang Laravel Breeze va noi vao dashboard PrimeVue hien co.
- Dang ky cong khai da bi tat; user moi duoc tao boi Admin trong man hinh quan ly nguoi dung.

## Yeu cau moi truong

- PHP 8.5+
- Composer 2.9+
- Node.js va npm
- Redis PHP extension (`phpredis`)
- PostgreSQL va Redis da duoc cai san va dang chay tren may

## Khoi dong nhanh

1. Sao chep cau hinh moi truong neu can:

```bash
cp .env.example .env
```

2. Chay migrate:

```bash
php artisan migrate
```

3. Chay ung dung:

```bash
php artisan serve
```

4. Chay frontend dev server:

```bash
npm run dev
```

5. Chay Mailpit tren may cua ban:

```bash
mailpit
```

6. Gui email kiem thu vao Mailpit:

```bash
php artisan mailpit:probe
```

7. Seed role, permission va user mac dinh:

```bash
php artisan db:seed
```

8. Dang nhap vao he thong:

- Mo `/login`
- Dung mot trong cac tai khoan seed ben duoi
- Sau khi dang nhap, sidebar va route se tu dong mo/khoa theo permission

## Cau hinh mac dinh

`.env.example` dang tro toi stack cuc bo sau:

- PostgreSQL: database `rebate_mailer_v3`, user `rebate_mailer`, password `secret`
- Redis: `127.0.0.1:6379`
- Mailpit SMTP: `127.0.0.1:1025`
- Mailpit UI: `http://127.0.0.1:8025`

Neu PostgreSQL hoac Redis cua may dung cong/credential khac, sua lai bien moi truong trong `.env`.

## Phan quyen mac dinh

- `Admin`: toan quyen tren tat ca permission.
- `Nguoi dung`: duoc tat ca nghiep vu, tru nhom CRUD User.
- `Khach`: chi duoc xem nghiep vu import va gui mail.

Tai khoan seed mac dinh:

- `admin@rebatemailer.test` / `password`
- `user@rebatemailer.test` / `password`
- `guest@rebatemailer.test` / `password`

## Ghi chu van hanh

- Route `/` se tu dong chuyen huong ve `login` neu chua dang nhap, hoac `dashboard` neu da co session.
- Khu vuc `/users` chi mo cho `Admin`.
- `Khach` chi xem duoc `/imports` va `/mail`.
