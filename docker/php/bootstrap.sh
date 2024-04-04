#!/bin/sh

set -eu

if ! mountpoint -q /var/www; then
    echo "WARNING: '/var/www' is not a mountpoint. All the data will remain inside the container!"
fi

if [ ! -w /var/www ] ; then
    echo "ERROR: '/var/www' is not writable. Closing."
    exit 1
fi

# Link the entrypoints from the exposed directory.
ln -nfs \
    /code/banners/ \
    /code/static/ \
    /code/stylesheets/ \
    /code/tools/ \
    /code/walls/ \
    /code/*.php \
    /code/LICENSE.* \
    /code/404.html \
    /code/install.sql \
    /var/www/
# Ensure correct permissions are set, since this might be bind mount.
chown www-data /var/www
chgrp www-data /var/www

# Initialize an empty robots.txt with the default if it doesn't exist.
touch /var/www/robots.txt

# Initialize an empty writable secrests.php with the default if it doesn't exist.
touch /var/www/inc/secrets.php
chown www-data /var/www/inc/secrets.php
chgrp www-data /var/www/inc/secrets.php

# Link the cache and tmp files directory.
ln -nfs /var/tmp/leftypol /var/www/tmp

# Link the javascript directory.
ln -nfs /code/js /var/www/

# Link the html templates directory and it's cache.
ln -nfs /code/templates /var/www/
ln -nfs -T /var/cache/template-cache /var/www/templates/cache
chown -h www-data /var/www/templates/cache
chgrp -h www-data /var/www/templates/cache

# Link the generic cache.
ln -nfs -T /var/cache/gen-cache /var/www/tmp/cache
chown -h www-data /var/www/tmp/cache
chgrp -h www-data /var/www/tmp/cache

# Create the included files directory and link them
install -d -m 700 -o www-data -g www-data /var/www/inc
for file in /code/inc/*; do
    file="${file##*/}"
    if [ ! -e /var/www/inc/$file ]; then
        ln -s /code/inc/$file /var/www/inc/
    fi
done
# Copy an empty instance configuration if the file is a link (it was linked because it did not exist before).
if [ -L '/var/www/inc/instance-config.php' ]; then
    echo 'INFO: Resetting instance configuration'
    rm /var/www/inc/instance-config.php
    cp /code/inc/instance-config.php /var/www/inc/instance-config.php
    chown www-data /var/www/inc/instance-config.php
    chgrp www-data /var/www/inc/instance-config.php
    chmod 600 /var/www/inc/instance-config.php
else
    echo 'INFO: Using existing instance configuration'
fi

# Link the composer dependencies.
ln -nfs /code/vendor /var/www/

# Start the php-fpm server.
exec php-fpm
