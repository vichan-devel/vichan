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


# Vichan Podman Setup (Docker alternative)

**Podman** offers a daemonless, rootless alternative for running Vichan in containers. Podman is API-compatible with Docker, allowing you to use the existing `compose.yml` file with minimal changes, while providing a more secure and lightweight environment.

This tutorial is for administrators who prefer to avoid Docker, addressing security concerns and ensuring a robust Vichan install.

---

## Why Podman?

- **Daemonless**: Unlike Docker, Podman doesn’t require a central daemon, reducing the attack surface and eliminating the need for a privileged process.
- **Rootless**: Podman runs containers as a non-root user by default, improving security by limiting the impact of potential container escapes.
- **Lightweight**: Podman has a smaller footprint and is better suited for environments where simplicity and security are priorities.
- **Docker Compatibility**: Podman supports Docker Compose files via Podman Compose, making it easy to adapt the existing Vichan setup.

Learn more at: https://podman.io
---

## Prerequisites

**Install Podman**

The official installation tutorial can be found at: https://podman.io/docs/installation

**Configure the `.env` file** as described in the main setup guide (copy `.env.example` to `.env` and update passwords).

---

## Steps to Run Vichan with Podman

### 1. Prepare File Permissions

Since Podman runs rootless, ensure the `local-instances/1/db` and `local-instances/1/www` directories are writable by your user:

```bash
chmod -R u+rw local-instances/1
```

If using SELinux (e.g., on Fedora), you may need to set the correct context:

```bash
chcon -R -t container_file_t local-instances/1
```

### 2. Start Containers with Podman Compose

Run the following command from the root of the Vichan project directory to build and start all containers:

```bash
podman-compose up -d --build
```

This command mirrors `docker compose up -d --build` but uses Podman’s container engine.

### 3. Rebuild Specific Containers

To rebuild only the PHP container (e.g., during development):

```bash
podman-compose up -d --build php
```

---

## Managing Podman Containers

**List running containers:**

```bash
podman ps
```

**Stop all containers:**

```bash
podman-compose down
```

**View logs for a specific service (e.g., PHP):**

```bash
podman logs vichan_php
```

## Troubleshooting

### unqualified-search-registries for Docker.io

1. Edit the config file with `nano /etc/containers/registries.conf`

2. Add the registry for `docker.io` with `unqualified-search-registries = ["docker.io"]`

3. Save and exit

4. Run `podman-compose up -d`