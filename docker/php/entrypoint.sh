#!/bin/sh
set -e

echo "[entrypoint] Discovering packages and caching Laravel config, routes and views..."
php artisan package:discover --no-ansi
php artisan config:cache     --no-ansi
php artisan route:cache      --no-ansi
php artisan view:cache       --no-ansi

echo "[entrypoint] Starting PHP-FPM..."
exec "$@"
