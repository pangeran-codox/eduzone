# ================================
# Stage 1: Node - Build frontend assets
# ================================
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --frozen-lockfile

COPY . .
RUN npm run build

# ================================
# Stage 2: PHP - Base
# ================================
FROM php:8.3-fpm-alpine AS base

# Install system dependencies (minimal)
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    shadow

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        mbstring \
        bcmath \
        opcache \
        intl \
        pcntl

# Install Redis extension
# Gabungkan instalasi semua extension dalam satu blok RUN
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis grpc protobuf \
    && docker-php-ext-enable redis grpc protobuf \
    && apk del .build-deps
# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN addgroup -g 1000 -S laravel && adduser -u 1000 -S laravel -G laravel

WORKDIR /var/www/html

# ================================
# Stage 3: Development
# ================================
FROM base AS development

# Config PHP & FPM dari folder docker/ di Laravel repo
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Install composer deps (with dev)
COPY --chown=laravel:laravel composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy app
COPY --chown=laravel:laravel . .

# Copy built assets from node stage
COPY --from=node-builder --chown=laravel:laravel /app/public/build ./public/build

RUN composer dump-autoload --optimize

# Storage & cache permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views,testing} bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 9000
CMD ["php-fpm"]

# ================================
# Stage 4: Production
# ================================
FROM base AS production

# Config PHP & FPM dari folder docker/ di Laravel repo
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Install composer deps (no dev)
COPY --chown=laravel:laravel composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Copy app
COPY --chown=laravel:laravel . .

# Copy built assets from node stage
COPY --from=node-builder --chown=laravel:laravel /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative

# Storage & cache permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views,testing} bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Laravel production optimizations
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

USER laravel

EXPOSE 9000
CMD ["php-fpm"]
