FROM php:8.2-cli-alpine as base

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk update
RUN apk add \
    bash \
    libzip-dev \
    autoconf \
    gcc \
    g++ \
    make \
    linux-headers

RUN docker-php-ext-install zip
RUN yes | pecl install xdebug && docker-php-ext-enable xdebug

FROM base as dependencies

WORKDIR /composer
COPY composer.json .
RUN composer install

FROM base as develop

WORKDIR /var/www/php-git
COPY --from=dependencies /composer /composer

RUN apk add rsync

CMD rsync -arv /composer/vendor /var/www/php-git/vendor && php
