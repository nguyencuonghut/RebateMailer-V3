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

## Cau hinh mac dinh

`.env.example` dang tro toi stack cuc bo sau:

- PostgreSQL: database `rebate_mailer_v3`, user `rebate_mailer`, password `secret`
- Redis: `127.0.0.1:6379`
- Mailpit SMTP: `127.0.0.1:1025`
- Mailpit UI: `http://127.0.0.1:8025`

Neu PostgreSQL hoac Redis cua may dung cong/credential khac, sua lai bien moi truong trong `.env`.
