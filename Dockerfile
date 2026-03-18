FROM dunglas/frankenphp:1.11-php8.3-alpine

ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip \
    xsl \
    redis \
    && apk add --no-cache git unzip

COPY Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . /app
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

RUN php bin/console sass:build \
    && php bin/console asset-map:compile \
    && php bin/console cache:clear \
    && php bin/console cache:warmup

RUN chown -R root:root /app/var \
    && chmod -R 777 /app/var

EXPOSE 80 443 443/udp
