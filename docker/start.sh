#!/bin/sh

# Start PHP-FPM in background
# Set permissions
mkdir -p /var/www/html/storage/framework/views /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations
php artisan migrate --force

php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
