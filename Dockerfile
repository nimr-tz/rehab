# Rehab Health Events Portal
#
# Runtime contract used by the msmt-02 cluster (nimr-tz/platform-gitops,
# clusters/msmt-02/research/rehab): php-fpm on port 9000 with the app in
# /var/www. An nginx sidecar serves /var/www/public, storage is a persistent
# volume, and `php artisan migrate --force` runs as a sync hook.

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Downloads and self-hosts the Plus Jakarta Sans font into public/build.
RUN npm run build


FROM php:8.4-fpm

WORKDIR /var/www

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git unzip \
        libicu-dev libonig-dev libxml2-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev zlib1g-dev \
        libsqlite3-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pdo_sqlite \
        intl mbstring exif pcntl bcmath gd zip \
        opcache; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP dependencies first, for better build caching.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN php artisan package:discover --ansi \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
