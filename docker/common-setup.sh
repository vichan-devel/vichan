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

install -m 775 -o leftypol -g leftypol -d /var/tmp/leftypol
install -m 775 -o leftypol -g leftypol -d /var/tmp/leftypol/cache
ln -s /var/tmp/leftypol /var/www-leftypol/tmp

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/js
ln -s /code/js/* /var/www-leftypol/js/

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/templates
install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/templates/cache
ln -s /code/templates/* /var/www-leftypol/templates/

install -m 775 -o leftypol -g leftypol -d /var/www-leftypol/inc
ln -s /code/inc/* /var/www-leftypol/inc/
