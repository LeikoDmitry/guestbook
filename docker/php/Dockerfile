FROM php:7.4-fpm

RUN apt-get update && apt-get install -y wget git g++ zip unzip librabbitmq-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libonig-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install -j$(nproc) gd pgsql pdo_pgsql intl opcache zip \
    && pecl install xdebug-2.8.1 apcu amqp \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable amqp

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
RUN wget https://get.symfony.com/cli/installer -O - | bash && mv ~/.symfony/bin/symfony /usr/local/bin/symfony
RUN addgroup --force-badname _www; \
    adduser --no-create-home --force-badname --disabled-login --disabled-password --system _www; \
    addgroup _www _www

WORKDIR /code
