# 📝 Blog Advanced - PHP Blog System

A powerful, self-hosted blogging platform with advanced features including, comments, backups, and Progressive Web App capabilities.

## ✨ Features

### Current Features (v1.0)
- ✅ **Multi-Image Upload** (up to 12 images per post)
- ✅ **File Attachments** (PDF, DOC, etc.)
- ✅ **Markdown & HTML Editor** with toolbar
- ✅ **Emoji Picker** (44+ emojis)
- ✅ **Sticky Posts** (pin important posts)
- ✅ **Trash System** (soft delete with restore)
- ✅ **Admin Dashboard** with statistics
- ✅ **Post Management** (filter, search)
- ✅ **Media Manager** (images & files)
- ✅ **Settings Panel** (blog configuration)
- ✅ **Multi-Language** (10 languages: DE, EN, ES, FR, NL, SK, ZH, BS, CZ, RU)
- ✅ **Responsive Design** (mobile-friendly)
- ✅ **Comment System** (nested comments with moderation)
- ✅ **Inline Admin Editor** (edit posts with live preview)
- ✅ **Notifications** (email alerts, in-app notifications)

### Upcoming Features (v2.0 - In Development) (maybe)
- 🔄 **Multi-User Support** (roles: Super Admin, Admin, Editor, Viewer) (maybe)
- 🔄 **Advanced Search** (fulltext, filters, saved searches)
- 🔄 **Calendar View** (posts per day with color coding)
- 🔄 **Export/Import** (JSON, CSV, ZIP backup)
- 🔄 **Automated Backups** (scheduled backups)
- 🔄 **Security Features** (2FA, IP whitelist, brute-force protection)
- 🔄 **Audit Log** (track all changes)
- 🔄 **Theme Editor** (customize colors & CSS)


## 🚀 Quick Start

---

### Requirements
- PHP 8.1+ with extensions:
  - pdo_mysql, mbstring, json, curl, intl, gd (optional for image ops), openssl, zip
- Web server: Nginx or Apache
- MySQL/MariaDB 10.5+ (or compatible)
- Composer (optional; if you plan to manage dependencies)
- docker compose

---

## Features

- Posts, comments, sticky posts, trash management
- Backups (export), media manager
- Security (CSRF tokens, sanitized theme selection, optional email notifications)
- PWA-ready front-end
- Simple config via `config.ini`

---

Optional for email:
- msmtp or a local MTA if using SMTP
- App-password for Gmail or a dedicated SMTP account

---

## Script permissions

The installation scripts are not executable by default. On macOS/Linux, make them executable first:

```bash
chmod +x install.sh docker-install.sh
```

If your environment requires ownership changes (to avoid using 777), run the script with sudo so it can set safe ownership and permissions:

```bash
# Local install (writes to data/ and uploads/ and data/config.ini)
sudo ./install.sh

# Docker quick install (also adjusts config.ini in repo root)
sudo ./docker-install.sh
```

These scripts will try to set secure permissions (dirs 775, files 664) and adjust ownership on:
- Non-Docker: `data/`, `uploads/`, `data/config.ini`
- Docker quick install: `data/`, `uploads/`, `config.ini`

If you are not using the default web user (e.g., `www-data` on Debian/Ubuntu or `apache` on CentOS/Fedora), set ownership manually:

```bash
# Replace www-data with your web server user (e.g., apache, nginx, etc.)
sudo chown -R www-data:www-data data/ uploads/
sudo chown www-data:www-data data/config.ini  # for non-Docker
sudo chown www-data:www-data config.ini        # for Docker quick install
```

**Windows users:** Run scripts via Git Bash or WSL, or execute docker compose commands directly if you prefer not to use the scripts.

---

## Quick Start (Docker)

This is the fastest way to get the blog running.

### Automated Installation (Recommended)

1) Clone the repository
```bash
git clone https://github.com/el-choco/blog-advanced.git
cd blog-advanced
```

2) Make the installation scripts executable
```bash
chmod +x install.sh docker-install.sh
```

3) Run the Docker installation script
```bash
./docker-install.sh
# Or with sudo if you need to set ownership on config files and writable directories:
# sudo ./docker-install.sh
```

The script will:
- Create necessary directories (data/, uploads/, data/backups/)
- Set proper ownership (www-data:www-data)
- Set proper permissions (0775)
- Create .env file from .env.example
- Start Docker containers
- Import database schema

4) Access your blog
- Frontend: http://localhost:3333
- Admin Panel: http://localhost:3333/admin/
- phpMyAdmin: http://localhost:3334

Default credentials:
- Username: admin
- Password: admin123
- ⚠️ **IMPORTANT: Change the default password immediately!**

### Manual Docker Installation

If you prefer to set up manually or customize the installation:

1) Clone the repository
```bash
git clone https://github.com/el-choco/blog-advanced.git
cd blog-advanced
```

2) Prepare environment files
- Copy example env files (if present) or create your own `.env`:
```bash
cp .env.example .env 2>/dev/null || true
# Edit .env with your DB credentials and app settings
```

3) Create necessary directories and set permissions
```bash
# Make scripts executable
chmod +x install.sh docker-install.sh

# Run the install script to create directories
./install.sh
# Or with sudo if ownership changes are required:
# sudo ./install.sh

# Or manually create and set permissions
mkdir -p data data/backups uploads
chown -R www-data:www-data data uploads
chmod -R 0775 data uploads
```

4) Use existing docker-compose.yml or customize
The repository includes pre-configured docker-compose files:
- `docker-compose.yml` - Default MySQL setup
- `docker-compose.sqlite.yml` - SQLite setup
- `docker-compose.postgres.yml` - PostgreSQL setup

Or create a custom `docker-compose.yml` (choose MySQL, Postgres or SQLite):
```yaml
version: "3.9"
services:
  web:
    image: php:8.2-apache
    container_name: blog-advanced-web
    volumes:
      - ./:/var/www/html
    ports:
      - "8080:80"
    environment:
      - PHP_OPCACHE_VALIDATE_TIMESTAMPS=1
    depends_on:
      - db
  db:
    image: mariadb:11
    container_name: blog-advanced-db
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=changeme-root
      - MYSQL_DATABASE=blog
      - MYSQL_USER=bloguser
      - MYSQL_PASSWORD=changeme
    volumes:
      - db_data:/var/lib/mysql
volumes:
  db_data:
```

5) Start containers
```bash
docker compose up -d
```

6) Configure PHP and Apache inside the container (optional)
- Enable required extensions:
```bash
docker exec -it blog-advanced-web bash -lc "apt-get update && apt-get install -y libicu-dev libzip-dev libjpeg62-turbo-dev libpng-dev && docker-php-ext-install intl zip"
```
Note: official image ships with many basics. Adjust steps as needed.

7) Access the app
- Open http://localhost:3333
- Go to http://localhost:3333/admin to configure settings, language, timezone, and theme.

8) Database configuration
- Edit `config.ini` (see “Configuration” section) with the DB host `db` and your credentials from docker-compose:
  - mysql_host = db
  - mysql_port = 3306
  - mysql_user = bloguser
  - mysql_pass = changeme
  - db_name    = blog

9) Import database schema (if not using automated script)
```bash
# Wait for database to be ready
sleep 30

# Import schema
docker exec -i blog-advanced-db mysql -u bloguser -pblogpass123 blog < app/db/mysql/01_schema.sql
```


10) Logs and backups
- Use the admin “Backups” page to create backups.
- Configure file paths in `config.ini` as needed.

---

## Manual Installation (Bare Metal)

1) Clone the repository into your web root
```bash
cd /var/www
git clone https://github.com/el-choco/blog-advanced.git
cd blog-advanced
```

2) Make the installation script executable
```bash
chmod +x install.sh
```

3) Run the installation script
```bash
# Run as root or with sudo to set proper ownership
sudo ./install.sh
```

The script will:
- Create necessary directories (data/, uploads/, data/backups/, logs/, sessions/)
- Set ownership to www-data:www-data
- Set permissions to 0775
- Create .gitkeep files
- Check for PHP and required extensions

Or manually create directories and set permissions (if not using the install script):
```bash
mkdir -p data/{posts,images,files,users,backups,cache} uploads/{images,files} logs sessions
chown -R www-data:www-data data uploads
chmod -R 0775 data uploads data/backups
```

3) Configure your web server

- Apache (VirtualHost example):
```
<VirtualHost *:80>
    ServerName blog.local
    DocumentRoot /var/www/blog-advanced

    <Directory /var/www/blog-advanced>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/blog-advanced-error.log
    CustomLog ${APACHE_LOG_DIR}/blog-advanced-access.log combined
</VirtualHost>
```
Enable site and reload:
```bash
a2enmod rewrite
a2ensite blog-advanced.conf
systemctl reload apache2
```

- Nginx (server block example):
```
server {
    listen 80;
    server_name blog.local;

    root /var/www/blog-advanced;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|svg|webp|ico)$ {
        expires 30d;
        access_log off;
    }
}
```
Reload:
```bash
nginx -t && systemctl reload nginx
```

4) PHP extensions
- Ensure required extensions are enabled:
```bash
php -m | grep -E 'pdo_mysql|mbstring|intl|zip|gd|curl|openssl|json'
```
Install missing ones via package manager (Debian/Ubuntu example):
```bash
apt-get install -y php8.2-{mysql,mbstring,intl,zip,gd,curl}
systemctl reload php8.2-fpm || systemctl restart apache2
```

5) Database
- Create a database and user:
```sql
CREATE DATABASE blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bloguser'@'%' IDENTIFIED BY 'changeme';
GRANT ALL PRIVILEGES ON blog.* TO 'bloguser'@'%';
FLUSH PRIVILEGES;
```

- Import the schema for your database type:
  - **MySQL/MariaDB**: `mysql -u bloguser -p blog < app/db/mysql/01_schema.sql`
  - **PostgreSQL**: `psql -U bloguser -W -d blog -f app/db/postgres/01_schema.sql`
  - **SQLite**: Schema is automatically loaded on first run

The schema files include all required tables: `posts`, `categories`, `comments`, `images`, and `users`.

6) Configuration
- Create or edit `config.ini` in the project root:
- chose DB Type by removing the Semikolon ( ; ).
```
;[database]
;db_connection = sqlite
;sqlite_db = data/sqlite.db

;[database]
;db_connection = "mysql"
;mysql_host = "db"
;mysql_port = "3306"
;mysql_user = "bloguser"
;mysql_pass = "blogpass123"
;db_name = "blog"

;[database]
;db_connection = postgres
;postgres_socket = /tmp/postgres.sock
;postgres_host = db
;postgres_port = 5432
;postgres_user = bloguser
;postgres_pass = blogpass123
;db_name = blog

[profile]
title = "Blog"
name = "your name"
pic_small = "static/images/profile.jpg"
pic_big = "static/images/profile_big.jpg"
cover = "static/images/cover.jpg"

[language]
lang = "de"

[components]
highlight = "1"

[custom]
theme = "theme02"

[admin]
force_login = "1"
nick = "admin"
pass = "admin"

[friends]

[directories]
images_path = "data/i/"
thumbnails_path = "data/t/"
logs_path = "data/logs/"
backup_dir = "data/backups/"

[proxy]

[system]
timezone = "Europe/Berlin"
version = "1.42"
debug = "0"
logs = "1"
SOFT_DELETE = "0"
HARD_DELETE_FILES = "1"
AUTO_CLEANUP_IMAGES = "1"

[visitor]
enabled = "1"
title = "Blog"
name = "your name"
subtitle = ""
lang = "en"
timezone = "Europe/Berlin"

[email]
notifications_enabled = "1"
admin_email = "your@mail.com"
notify_admin_new_comment = "1"
notify_user_approved = "1"
from_email = "noreply@pacos-blog.dynv6.net"
from_name = "your-Tech-Blog"
title = "Blog"
name = "your name"
subtitle = ""
lang = "en"
timezone = "Europe/Berlin"
theme = "theme02"

```
Note: Do not commit secrets to the repo. Add sensitive files to `.gitignore` (see below).

7) Permissions
- If you skipped running `./install.sh` earlier, ensure proper permissions:
```bash
# Create additional directories if needed
mkdir -p data/i data/t data/logs static/images

# Set ownership and permissions
chown -R www-data:www-data data uploads
chmod -R 0775 data uploads data/backups
```

Note: The `install.sh` script already handles most permissions. This step is only needed if you're setting up manually.

8) Access
- Open http://blog.local (adjust hosts/DNS) and http://blog.local/admin

---

## Email Setup (Optional)

- If you use msmtp:
  - Place `~/.msmtprc` or `/etc/msmtprc` with chmod 600.
  - Configure PHP `sendmail_path`:
    ```
    sendmail_path = "/usr/bin/msmtp -t"
    ```
  - In the app, enable email notifications in Admin → Email (set admin_email, from_email, from_name).
- Alternative: Use native PHP mail or a transactional service (SMTP credentials).

Security: Never commit credentials. Rotate app passwords if exposed.

---

## Operations

- Backups: Use Admin → Backups to create and download archives. Store externally.
- Logs: Keep `data/logs/`**readable** and rotated.
- Theme: Switch under Admin → Appearance. Theme setting is sanitized to prevent invalid names.
- Trash: Admin → Trash for restore/permanent delete.
- Comments: Admin → Comments for moderation (email notifications optional).

### Export / Import

The backup system supports multiple export formats:

1. **SQL Backup** - Traditional mysqldump backup (database only)
2. **JSON Export** - Structured export of posts, categories, and comments
3. **CSV Export** - Spreadsheet-compatible export as ZIP archive
4. **Full Backup** - Complete backup including database and media files

**Backup Directory:**
- Default location: `data/backups/`
- Configurable via `config.ini` under `[directories]` section: `backup_dir = "data/backups/"`

**Import (Replace Mode):**
- Importing a backup will replace existing data (truncate tables first)
- Import order: categories → posts → posts_categories → comments
- For MySQL: Foreign key checks are temporarily disabled during import
- Supported formats: `.json` (JSON export) and `.zip` (Full backup)

**MySQL Foreign Key Handling:**
When restoring data on MySQL, the import process:
1. Executes `SET FOREIGN_KEY_CHECKS = 0` before import
2. Truncates tables and inserts data
3. Executes `SET FOREIGN_KEY_CHECKS = 1` after import

This ensures proper restoration even with complex table relationships.

---

## **Quick Guide: Updating Your Installation**

**Standard Update: (Bind mount or code in the image doesn't matter):**

**Get the latest changes:**

    git fetch origin
    git pull origin main

**Rebuild and start the container:**

    docker compose down
    docker compose build
    docker compose up -d

**Quick version (combined build on startup):**

    git pull origin main
    docker compose up -d --build

**If your Compose version doesn't support flags like --build:**

    git pull origin main
    docker compose down
    docker compose build
    docker compose up -d

**Optional (if old code is stubbornly running):**

    docker compose exec web grep -n "tab-language" /var/www/html/admin/settings.php
    docker compose restart

**If necessary, remove old images and rebuild:**

    docker images
    docker rmi <image_id_of_web_image>
    docker compose build && docker compose up -d

**After the Check for updates:**

  `git log -1 --oneline`  (shows the last commit)
`docker compose ps` (is the container running?)

Hardly reload the browser (Ctrl+F5)

Verify specific functionality (e.g., language tab): 
`grep -n "tab-language" admin/settings.php` or in the container as above.

**Shortcut for everyday use:**

    git pull origin main
    docker compose up -d --build

If there are errors or conflicts during the pull:

    git stash push -u -m "before update"
    git pull origin main

Afterwards, if necessary, git stash pop (or discard changes)
---
## Troubleshooting

- 403/permissions on uploads:
  - Check ownership/permissions for `data/` and `static/images/`.
- Missing PHP extensions:
  - Install and reload PHP-FPM/Apache.
- Blank page:
  - Check web server error logs and PHP logs.
- Email not sending:
  - Verify msmtp logs and credentials, enable app passwords if using Gmail.

---

## License

See repository for license information.

## Credits

Maintained by el-choco. Contributions welcome via issues and pull requests.


---

## Bare Metal Install Options: `install.sh` vs `bare-metal-install.sh`

If you deploy without Docker, you can choose between a minimal installer and a comfort helper script:

- `install.sh` (minimal):
  - Creates required directories (`data/`, `uploads/`, `logs/`, `sessions/`)
  - Sets safe ownership (default `www-data:www-data`) and permissions (dirs `0775`, files `0664`)
  - Ensures Theme Editor paths exist and are writable (`static/styles/custom1.css`)

- `bare-metal-install.sh` (comfort):
  - Auto-detects the web user (`www-data`, `nginx`, or `apache`) with fallback to your current user
  - Optional ACLs (`--apply-acl`) to grant write access without changing ownership (uses `setfacl` if available)
  - Guarantees `static/styles/custom1.css` exists and is writable
  - Optional web server reload (`--reload`) for `nginx`, `apache2`/`httpd` via `systemctl`
  - Customizable user/group via flags: `--web-user`, `--web-group`
  - Can skip the base installer: `--skip-install`

### Usage examples
```bash
# Run with auto-detection and base setup
./bare-metal-install.sh

# Explicit web user/group and reload the web server
./bare-metal-install.sh --web-user www-data --web-group www-data --reload

# Use ACLs to grant write access without changing ownership
./bare-metal-install.sh --apply-acl

# If install.sh already ran and you want only the comfort steps
./bare-metal-install.sh --skip-install
```

### Recommended ownership/permissions
- Writable by the web server:
  - `data/`, `uploads/`, `logs/`, `sessions/` → owner `WEB_USER:WEB_GROUP`, dirs `0775`, files `0664`
  - `static/styles/` and `static/styles/custom1.css` (Theme Editor) → same as above
- Read-only for the web server:
  - `app/`, `static/scripts/`, other static assets → dirs `0755`, files `0644`
- Avoid `0777` entirely.

Note: On systems without `setfacl`, ACL mode automatically falls back to `chown/chmod`.
