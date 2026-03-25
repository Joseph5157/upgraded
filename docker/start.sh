#!/usr/bin/env sh
set -eu

PORT="${PORT:-8080}"

if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set. Deploy aborted to prevent session invalidation." >&2
    exit 1
fi

mkdir -p \
    /var/www/html/storage/app/private \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

php artisan storage:link --force || true
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan migrate --force

sed -ri "s!Listen 80!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \\*:8080>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
