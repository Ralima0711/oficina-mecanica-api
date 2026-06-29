# ── Stage 1: Builder ─────────────────────────────────────────────
FROM php:8.2-fpm AS builder

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ── Stage 2: Production ───────────────────────────────────────────
FROM php:8.2-fpm AS production

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev nginx \
    && docker-php-ext-install pdo pdo_pgsql zip opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=builder --chown=www-data:www-data /var/www/html .

# Ensure storage and cache directories exist and are owned by www-data
RUN mkdir -p storage/logs bootstrap/cache storage/framework/views storage/framework/cache storage/framework/sessions \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx-k8s.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default && rm -f /etc/nginx/conf.d/default.conf

COPY docker/start.sh /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]