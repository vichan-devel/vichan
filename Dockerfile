FROM php:5.6-fpm
RUN docker-php-ext-install pdo pdo_mysql
RUN apt-get update -y && apt-get install -y libpng-dev libjpeg-dev
RUN docker-php-ext-install mbstring
RUN apt-get update -y && apt-get install -y libmcrypt-dev
RUN docker-php-ext-install -j$(nproc) mcrypt
RUN docker-php-ext-install iconv
RUN apt-get update -y && apt-get install -y imagemagick
RUN apt-get update -y && apt-get install -y graphicsmagick
RUN apt-get update -y && apt-get install -y gifsicle
RUN docker-php-ext-configure gd \
        --with-png-dir=/usr \
        --with-jpeg-dir=/usr
RUN docker-php-ext-install gd
RUN apt-get update -y \
  && apt-get install -y libmemcached11 libmemcachedutil2 build-essential libmemcached-dev libz-dev \
  && pecl install memcached-2.2.0 \
  && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini \
  && apt-get remove -y build-essential libmemcached-dev libz-dev \
  && apt-get autoremove -y \
  && apt-get clean \
  && rm -rf /tmp/pear