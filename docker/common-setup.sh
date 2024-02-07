#!/bin/sh

set -eu

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol
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
   /var/www-leftypol/

install -m 775 -o leftypol -g leftypol -d /var/www/js
ln -s /code/js/* /var/www/js/

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/templates
install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/templates/cache
ln -s /code/templates/* /var/www-leftypol/templates/

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/inc
ln -s /code/inc/* /var/www-leftypol/inc/
