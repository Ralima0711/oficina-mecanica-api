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
    libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache \
    && rm -rf /var/lib/apt/lists/*

# Usuário não-root
RUN groupadd -g 1000 appgroup && useradd -u 1000 -g appgroup -s /bin/sh -m appuser

WORKDIR /var/www/html

COPY --from=builder --chown=appuser:appgroup /var/www/html .

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

USER appuser

EXPOSE 9000

CMD ["php-fpm"]