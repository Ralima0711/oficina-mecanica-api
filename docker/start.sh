#!/bin/sh

# Start PHP-FPM in background
# Set permissions
mkdir -p /var/www/html/storage/framework/views /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# New Relic: injeta license key no ini
if [ -n "$NEW_RELIC_LICENSE_KEY" ] && [ -f /usr/local/etc/php/conf.d/newrelic.ini ]; then
  sed -i "s/^newrelic.license = .*/newrelic.license = \"${NEW_RELIC_LICENSE_KEY}\"/" /usr/local/etc/php/conf.d/newrelic.ini
  sed -i "s/^newrelic.appname = .*/newrelic.appname = \"${NEW_RELIC_APP_NAME:-oficina-mecanica-api}\"/" /usr/local/etc/php/conf.d/newrelic.ini
fi

# Run migrations
php artisan migrate --force

php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
