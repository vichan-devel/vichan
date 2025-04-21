# Vichan Docker Setup

The `php-fpm` process runs containerized.  
The PHP application always uses `/var/www` as its work directory and home folder. If `/var/www` is bind mounted, you must adjust the path passed via FastCGI to `php-fpm`.

To fix this:
1. **Adjust the root path**: Set `fastcgi_param SCRIPT_FILENAME` to `/var/www/$fastcgi_script_name;` in your nginx config.

The default Docker Compose settings are meant for development and testing.

Expected folder structure:
```
<vichan-project>
└── local-instances
    └── 1
        ├── db
        └── www
```

To run the app:
1. **Start all containers**: Run `docker compose up -d --build` at the root of vichan directory
2. **Rebuild just the PHP container**: Run `docker compose up -d --build php` (useful during development)

---

## PHP File Size Limit

To upload larger files, increase the default PHP file size limit (2MB). Since this setup uses Docker, follow these steps:

1. **Open the config file**: Edit `./docker/php/php.ini` in your project directory.
2. **Add or update these lines** to increase the file size limit to 10MB:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. **Restart your containers** to apply the changes:
   ```bash
   docker compose restart
   ```

---

By default, PHP limits file uploads to **2MB** — so increasing this is often required when uploading images or documents.

---

## Using the `.env` File

Environment variables for the Docker Compose setup can be managed easily using a `.env` file.

### Steps to Use It:
1. **Copy the example**: Start by copying `.env.example` to `.env`
   ```bash
   cp .env.example .env
   ```
2. **Edit `.env`**: Please make sure to change the default passwords for your setup.

### What It Controls:
- Instance folder reference (e.g. `local-instances/0`)
- Database credentials
- Redis connection details
- Secure login
- Optional SSL certificate paths for nginx
