The `php-fpm` process runs containerized.
The php application always uses `/var/www` as it's work directory and home folder, and if `/var/www` is bind mounted it
is necessary to adjust the path passed via FastCGI to `php-fpm` by changing the root directory to `/var/www`.
This can achieved in nginx by setting the `fastcgi_param SCRIPT_FILENAME` to `/var/www/$fastcgi_script_name;`

The default docker compose settings are intended for development and testing purposes.
The folder structure expected by compose is as follows

```
<vichan-project>
└── local-instances
    └── 1
        ├── mysql
        └── www
```
The vichan container is by itself much less rigid.


Use `docker compose up --build` to start the docker compose.
Use `docker compose up --build -d php` to rebuild just the vichan container while the compose is running. Useful for development.
