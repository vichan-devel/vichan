# syntax = devthefuture/dockerfile-x
INCLUDE ./docker/php/Dockerfile

RUN apk add --no-cache \
        linux-headers \
        $PHPIZE_DEPS \
    && pecl update-channels \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del \
        linux-headers \
        $PHPIZE_DEPS \
    && rm -rf /var/cache/*

ENV XDEBUG_OUT_DIR=/var/www/xdebug_out
CMD [ "bootstrap.sh" ]