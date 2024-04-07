# Based on https://github.com/dead-guru/devichan/blob/master/php-fpm/Dockerfile

FROM composer AS composer
FROM php:8.1-fpm-alpine

RUN apk add --no-cache \
    zlib \
    zlib-dev \
    libpng \
    libpng-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    libwebp \
    libwebp-dev \
    libcurl \
    curl-dev \
    imagemagick \
    graphicsmagick \
    gifsicle \
    ffmpeg \
    bind-tools \
    gettext \
    gettext-dev \
    icu-dev \
    oniguruma \
    oniguruma-dev \
    libmcrypt \
    libmcrypt-dev \
    lz4-libs \
    lz4-dev \
    imagemagick-dev \
    pcre-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-webp=/usr/include/webp \
        --with-jpeg=/usr/include \
    && docker-php-ext-install -j$(nproc) \
        gd \
        curl \
        bcmath \
        opcache \
        pdo_mysql \
        gettext \
        intl \
        mbstring \
    && pecl update-channels \
    && pecl install -o -f igbinary \
    && pecl install redis \
    && pecl install imagick \
    $$ docker-php-ext-enable \
        igbinary \
        redis \
        imagick \
    && apk del \
        zlib-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        curl-dev \
        gettext-dev \
        oniguruma-dev \
        libmcrypt-dev \
        lz4-dev \
        imagemagick-dev \
        pcre-dev \
        $PHPIZE_DEPS \
    && rm -rf /var/cache/*
RUN rmdir /var/www/html \
    && install -d -m 744 -o www-data -g www-data /var/www \
    && install -d -m 700 -o www-data -g www-data /var/tmp/vichan \
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
