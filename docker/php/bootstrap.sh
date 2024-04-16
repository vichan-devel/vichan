#!/bin/sh

set -eu

function set_cfg() {
    if [ -L "/var/www/inc/$1" ]; then
        echo "INFO: Resetting $1"
        rm "/var/www/inc/$1"
        cp "/code/inc/$1" "/var/www/inc/$1"
        chown www-data "/var/www/inc/$1"
        chgrp www-data "/var/www/inc/$1"
        chmod 600 "/var/www/inc/$1"
    else
        echo "INFO: Using existing $1"
    fi
}

if ! mountpoint -q /var/www; then
    echo "WARNING: '/var/www' is not a mountpoint. All the data will remain inside the container!"
fi

if [ ! -w /var/www ] ; then
    echo "ERROR: '/var/www' is not writable. Closing."
    exit 1
fi

if [ -z  "${XDEBUG_OUT_DIR:-''}" ] ; then
    echo "INFO: Initializing xdebug out directory at $XDEBUG_OUT_DIR"
    mkdir -p "$XDEBUG_OUT_DIR"
    chown www-data "$XDEBUG_OUT_DIR"
    chgrp www-data "$XDEBUG_OUT_DIR"
    chmod 755 "$XDEBUG_OUT_DIR"
fi

# Link the entrypoints from the exposed directory.
ln -nfs \
    /code/tools/ \
    /code/*.php \
    /code/LICENSE.* \
    /code/install.sql \
    /var/www/
# Static files accessible from the webserver must be copied.
cp -ur /code/static /var/www/
cp -ur /code/stylesheets /var/www/

# Ensure correct permissions are set, since this might be bind mount.
chown www-data /var/www
chgrp www-data /var/www

# Initialize an empty robots.txt with the default if it doesn't exist.
touch /var/www/robots.txt

# Link the cache and tmp files directory.
ln -nfs /var/tmp/vichan /var/www/tmp

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
set_cfg 'instance-config.php'
set_cfg 'secrets.php'

# Link the composer dependencies.
ln -nfs /code/vendor /var/www/

# Start the php-fpm server.
exec php-fpm
