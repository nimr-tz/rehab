FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


FROM php:8.2-fpm

WORKDIR /var/www

# System deps
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git curl zip unzip \
        libpng-dev libonig-dev libxml2-dev \
        libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev zlib1g-dev \
        libpq-dev \
        libsqlite3-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pdo_pgsql pdo_sqlite \
        mbstring exif pcntl bcmath gd zip \
        opcache; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP deps first (better build caching)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# Copy app
COPY . .

# Copy built frontend assets (ensures Vite manifest exists in production)
COPY --from=frontend /app/public/build ./public/build

RUN php artisan package:discover --ansi

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
