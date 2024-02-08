FROM php:8.2-cli-alpine

ENV apk update

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

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; exit 0

CMD ["bash"]
