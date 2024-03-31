#!/bin/sh

set -eu

install -m 775 -o www-data -g www-data -d /var/www
ln -s \
   /code/banners/ \
   /code/static/ \
   /code/stylesheets/ \
   /code/tools/ \
   /code/walls/ \
   /code/*.php \
   /code/404.html \
   /code/LICENSE.* \
   /code/robots.txt \
   /code/install.sql \
   /var/www/

install -m 775 -o www-data -g www-data -d /var/tmp/leftypol
install -m 775 -o www-data -g www-data -d /var/tmp/leftypol/cache
ln -s /var/tmp/leftypol /var/www/tmp

install -m 775 -o www-data -g www-data -d /var/www/js
ln -s /code/js/* /var/www/js/

install -m 775 -o www-data -g www-data -d /var/www/templates
install -m 775 -o www-data -g www-data -d /var/www/templates/cache
ln -s /code/templates/* /var/www/templates/

install -m 775 -o www-data -g www-data -d /var/www/inc
ln -s /code/inc/* /var/www/inc/
