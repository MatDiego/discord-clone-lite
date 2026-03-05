FROM dunglas/frankenphp:1.11-php8.3-alpine

ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip \
    xsl \
    && apk add --no-cache git unzip

COPY Caddyfile /etc/frankenphp/Caddyfile

COPY . /app
WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction

RUN php bin/console sass:build \
    && php bin/console asset-map:compile \
    && php bin/console cache:clear \
    && php bin/console cache:warmup

RUN chown -R root:root /app/var \
    && chmod -R 777 /app/var

EXPOSE 80 443 443/udp
