###############################################################################
# Stage 1 — Install PHP/Composer dependencies (produces vendor/)
#   Chạy riêng để frontend stage có thể lấy Ziggy mà không cần vendor/ trên host
###############################################################################
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN COMPOSER_NO_PLATFORM_CHECK=1 composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --ignore-platform-reqs

###############################################################################
# Stage 2 — Build frontend assets (Node 20)
###############################################################################
FROM node:20-alpine AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY vite.config.js postcss.config.js tailwind.config.js tsconfig.json ./
COPY resources/ resources/
COPY public/ public/

# Ziggy JS is imported from vendor/ by resources/js/app.ts — get it from Stage 1
COPY --from=composer-deps /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

RUN npx vite build

###############################################################################
# Stage 3 — PHP 8.3-FPM production image  (used by: app, worker, scheduler)
###############################################################################
FROM php:8.4-fpm-alpine AS app

# ── System packages ────────────────────────────────────────────────────────
RUN apk add --no-cache \
        bash \
        curl \
        unzip \
        git \
        libpq-dev \
        icu-dev \
        libzip-dev \
        libpng-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        oniguruma-dev \
        libxml2-dev \
        linux-headers \
        autoconf \
        g++ \
        make

# ── PHP extensions ─────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pgsql \
        bcmath intl pcntl \
        zip gd mbstring xml \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

WORKDIR /var/www/html

# ── PHP vendor từ Stage 1 (đã install sẵn, không cần composer trên image) ─
COPY --from=composer-deps /app/vendor ./vendor

# ── Application source ─────────────────────────────────────────────────────
COPY . .

# ── Compiled frontend assets từ Stage 2 ──────────────────────────────────
COPY --from=frontend /build/public/build ./public/build

# ── Finalise Composer autoloader + framework discovery ───────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN COMPOSER_NO_PLATFORM_CHECK=1 composer dump-autoload --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi

# ── Writable storage directories + permissions ────────────────────────────
RUN mkdir -p \
        storage/logs \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ── PHP production ini & startup entrypoint ───────────────────────────────
COPY docker/php/php.ini     "$PHP_INI_DIR/conf.d/99-app.ini"
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

###############################################################################
# Stage 4 — Nginx  (serves static files + reverse-proxies PHP-FPM)
###############################################################################
FROM nginx:1.26-alpine AS web

RUN rm -f /etc/nginx/conf.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/app.conf

# Static assets baked into the image — no runtime volume required
COPY --from=app /var/www/html/public /var/www/html/public

EXPOSE 80
