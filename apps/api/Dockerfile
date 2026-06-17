FROM php:8.3-cli-alpine

RUN apk add --no-cache git unzip libpq-dev postgresql-client icu-dev icu-libs openssl \
    && docker-php-ext-install pdo_pgsql intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV APP_SECRET=build-time-placeholder

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p var/cache var/log config/jwt \
    && chmod -R 777 var

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t public"]
