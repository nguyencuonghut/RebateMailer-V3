# RebateMailer V3

Nen tang backend cho he thong tu dong gui mail chiet khau hang thang.

## Task 0.1

Trang thai hien tai:

- Laravel 13 da duoc scaffold vao repo.
- Database mac dinh la PostgreSQL.
- Cache, session, va queue mac dinh da duoc cau hinh qua Redis.

## Yeu cau moi truong

- PHP 8.5+
- Composer 2.9+
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

## Cau hinh mac dinh

`.env.example` dang tro toi stack cuc bo sau:

- PostgreSQL: database `rebate_mailer_v3`, user `rebate_mailer`, password `secret`
- Redis: `127.0.0.1:6379`

Neu PostgreSQL hoac Redis cua may dung cong/credential khac, sua lai bien moi truong trong `.env`.
