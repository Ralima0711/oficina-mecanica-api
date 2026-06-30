#!/bin/sh

# Start PHP-FPM in background
# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations
php artisan migrate --force

php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
