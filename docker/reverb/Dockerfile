# ============================================================
# Reverb WebSocket Server — PHP CLI minimal
# ============================================================
FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    unzip \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    postgresql-libs \
    icu-dev \
    icu-libs \
    zlib-dev \
    g++ \
    make \
    autoconf \
    linux-headers

RUN docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring bcmath pcntl sockets \
    && docker-php-ext-configure intl && docker-php-ext-install intl

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/reverb.ini \
    && echo "max_execution_time=0" >> /usr/local/etc/php/conf.d/reverb.ini

WORKDIR /var/www/html

RUN addgroup -g 1000 -S laravel && adduser -u 1000 -S laravel -G laravel
USER laravel
