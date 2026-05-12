###############################################################################
# Stage 1 — Build frontend assets (Node 20)
###############################################################################
FROM node:20-alpine AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY vite.config.js postcss.config.js tailwind.config.js tsconfig.json ./
COPY resources/ resources/
COPY public/ public/

RUN npx vite build

###############################################################################
# Stage 2 — PHP 8.3-FPM production image  (used by: app, worker, scheduler)
###############################################################################
FROM php:8.3-fpm-alpine AS app

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

# ── Composer ──────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── PHP vendor dependencies (separate layer for cache efficiency) ──────────
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-autoloader \
        --no-scripts \
        --prefer-dist

# ── Application source ─────────────────────────────────────────────────────
COPY . .

# ── Compiled frontend assets from Stage 1 ────────────────────────────────
COPY --from=frontend /build/public/build ./public/build

# ── Finalise Composer + framework discovery ───────────────────────────────
RUN composer dump-autoload --classmap-authoritative --no-dev \
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
# Stage 3 — Nginx  (serves static files + reverse-proxies PHP-FPM)
###############################################################################
FROM nginx:1.26-alpine AS web

RUN rm -f /etc/nginx/conf.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/app.conf

# Static assets baked into the image — no runtime volume required
COPY --from=app /var/www/html/public /var/www/html/public

EXPOSE 80
