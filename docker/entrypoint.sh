#!/bin/sh
set -e

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache config & routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor (nginx + php-fpm + reverb)
exec supervisord -c /etc/supervisord.conf
