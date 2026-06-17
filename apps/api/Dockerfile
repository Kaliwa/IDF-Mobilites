FROM php:8.3-cli-alpine

RUN apk add --no-cache git unzip libpq-dev postgresql-client icu-dev icu-libs \
    && docker-php-ext-install pdo_pgsql intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 8000
