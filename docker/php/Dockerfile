# Based on https://github.com/dead-guru/devichan/blob/master/php-fpm/Dockerfile

FROM composer AS composer
FROM php:8.1-fpm-bullseye

RUN apt-get update && apt-get upgrade -y && apt-get install -y \
      zlib1g-dev libicu-dev g++ \
      libjpeg62-turbo-dev \
      libzip-dev \
      libpng-dev \
      libwebp-dev \
      libfreetype6-dev \
      libxml2-dev \
      git \
      zip \
      ffmpeg \
      libonig-dev \
      unzip \
      libcurl4-openssl-dev \
      libmagickwand-dev \
      gifsicle \
      graphicsmagick \
      gettext \
      imagemagick \
      locales locales-all \
      libmagickwand-dev \
      libmcrypt-dev \
    && docker-php-ext-configure gd \
        --with-webp=/usr/include/webp \
        --with-jpeg=/usr/include \
        --with-freetype=/usr/include/freetype2/ \
    && pecl install redis \
    && pecl install imagick \
    && pecl install -o -f igbinary \
    && docker-php-ext-install gd zip opcache intl pdo pdo_mysql mysqli bcmath gettext iconv mbstring curl \
    && docker-php-ext-enable igbinary redis imagick \
    && rm -rf /var/cache/* \
    && rmdir /var/www/html \
    && install -d -m 744 -o www-data -g www-data /var/www \
    && install -d -m 700 -o www-data -g www-data /var/tmp/leftypol \
    && install -d -m 700 -o www-data -g www-data /var/cache/gen-cache \
    && install -d -m 700 -o www-data -g www-data /var/cache/template-cache

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Copy the bootstrap script.
COPY ./docker/php/bootstrap.sh /usr/local/bin/bootstrap.sh

# Copy the actual project (use .dockerignore to exclude stuff).
COPY . /code

# Make the instance configuration owned by www-data.
# Make it writable by php.
# Install the compose depedencies.
RUN chown www-data /code/inc/instance-config.php && chgrp www-data /code/inc/instance-config.php \
    && chmod 660 /code/inc/instance-config.php \
    && cd /code && composer install

WORKDIR "/var/www"
CMD [ "bootstrap.sh" ]
EXPOSE 9000
