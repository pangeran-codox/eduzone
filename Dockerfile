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
#
# ⚠️ grpc & protobuf SENGAJA DITUNDA (bukan dihapus permanen) — belum ada
# controller yang makai encryption service, dan compile grpc dari source di
# Alpine makan waktu ~1 jam + rawan masalah cache. Begitu mulai kerjain
# integrasi encryption service (lihat ARCHITECTURE.md §4), aktifkan lagi:
# 1. tambahkan "grpc protobuf" balik ke baris pecl install & ext-enable di bawah
# 2. pastikan "linux-headers" ditambahkan lagi ke .build-deps (wajib buat grpc di Alpine)
# 3. HAPUS "--ignore-platform-req=ext-grpc" dari kedua baris "composer install"
#    di bawah (development & production stage) — itu cuma buat bypass sementara
#    karena composer.json minta package grpc/grpc yang butuh ext-grpc aktif.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
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
RUN composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-req=ext-grpc

# Copy app
COPY --chown=laravel:laravel . .

# Copy built assets from node stage
COPY --from=node-builder --chown=laravel:laravel /app/public/build ./public/build

# ⚠️ DEBUG SEMENTARA — dipecah 2 langkah biar bisa diagnosa
# Langkah 1: dump-autoload TANPA trigger script (--no-scripts), biar nggak
# langsung mati kena package:discover. Ini generate vendor/autoload.php dkk
# tanpa Laravel ikut boot.
RUN composer dump-autoload --no-scripts

# Langkah 2: inspeksi hasil autoload_psr4.php buat namespace Telescope,
# dan test class_exists() langsung pakai autoloader yang baru dibikin.
RUN echo "=== Isi vendor/laravel/telescope/composer.json (autoload) ===" \
    && grep -A15 '"autoload"' vendor/laravel/telescope/composer.json \
    && echo "=== Cari 'Telescope' di autoload_psr4.php ===" \
    && grep -i "telescope" vendor/composer/autoload_psr4.php || echo "TIDAK ADA entry Telescope di autoload_psr4.php!" \
    && echo "=== Test class_exists langsung ===" \
    && php -r "require 'vendor/autoload.php'; var_dump(class_exists('Laravel\\\\Telescope\\\\TelescopeApplicationServiceProvider'));"

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
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader --ignore-platform-req=ext-grpc

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