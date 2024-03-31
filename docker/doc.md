The `php-fpm` process runs containerized.
The php application always uses `/var/www` as it's work directory and home folder, and if `/var/www` is bind mounted it
is necessary to adjust the path passed via FastCGI to `php-fpm` by changing the root directory to `/var/www`.
This can achieved in nginx by setting the `fastcgi_param SCRIPT_FILENAME` to `/var/www/$fastcgi_script_name;`
