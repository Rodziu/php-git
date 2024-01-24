FROM php:7.2-cli-alpine

ENV apk update

RUN apk add \
    bash \
    libzip-dev \
    autoconf \
    gcc \
    g++ \
    make

RUN docker-php-ext-install zip
RUN yes | pecl install xdebug-2.9.8 && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; exit 0

CMD ["bash"]
