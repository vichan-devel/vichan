FROM php:8.1.8-fpm

COPY . /code

RUN docker-php-ext-install pdo pdo_mysql
RUN apt-get update -y && apt-get install -y libpng-dev libjpeg-dev libonig-dev
RUN docker-php-ext-install mbstring
RUN apt-get update -y && apt-get install -y libmcrypt-dev
# RUN docker-php-ext-install -j$(nproc) mcrypt
RUN docker-php-ext-install iconv
RUN apt-get update -y && apt-get install -y imagemagick
RUN apt-get update -y && apt-get install -y graphicsmagick
RUN apt-get update -y && apt-get install -y gifsicle
# RUN docker-php-ext-configure gd
#         --with-jpeg=/usr/include
#         --with-png-dir=/usr \
RUN docker-php-ext-install gd
RUN apt-get update -y \
  && apt-get install -y libmemcached11 libmemcachedutil2 build-essential libmemcached-dev libz-dev git \
  && pecl install memcached \
  && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini \
  && apt-get remove -y build-essential libmemcached-dev libz-dev \
  && apt-get autoremove -y \
  && apt-get clean \
  && rm -rf /tmp/pear \
  && curl -sS https://getcomposer.org/installer -o composer-setup.php \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && docker-php-ext-install bcmath \
  && cd /code && composer install