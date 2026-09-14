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

# Massa de demonstracao (staff, clientes e ordens de servico).
# O ambiente do lab e efemero: quando o RDS e recriado o banco sobe vazio e a
# Lambda nao encontra CPF nenhum. Os seeders sao idempotentes, entao rodar a
# cada boot nao duplica registros. Controlado por SEED_DEMO no ConfigMap.
if [ "$SEED_DEMO" = "true" ]; then
  php artisan db:seed --force || echo "Seed falhou; a aplicacao sobe mesmo assim."
fi

php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
