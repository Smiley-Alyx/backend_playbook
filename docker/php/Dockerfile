FROM composer:2 AS composer

FROM php:8.3-fpm-alpine

RUN set -eux; \
    apk add --no-cache \
        bash \
        icu-dev \
        libpq-dev \
        linux-headers \
        postgresql-client \
        $PHPIZE_DEPS; \
    docker-php-ext-install -j$(nproc) intl pdo_pgsql opcache; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del --no-network $PHPIZE_DEPS

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
