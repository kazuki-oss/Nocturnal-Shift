FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git \
    icu-dev \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip

RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
