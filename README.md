# 📝 Blog Advanced - Professional PHP Blog System

A powerful, self-hosted blogging platform with advanced features including, comments, backup/export, and Progressive Web App capabilities.

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

### Upcoming Features (v2.0 - In Development) (maybe)
- 🔄 **Multi-User Support** (roles: Super Admin, Admin, Editor, Viewer)
- 🔄 **Comment System** (nested comments with moderation)
- 🔄 **Inline Editor** (edit posts with live preview)
- 🔄 **Advanced Search** (fulltext, filters, saved searches)
- 🔄 **Calendar View** (posts per day with color coding)
- 🔄 **Export/Import** (JSON, CSV, ZIP backup)
- 🔄 **Automated Backups** (scheduled backups)
- 🔄 **Security Features** (2FA, IP whitelist, brute-force protection)
- 🔄 **Audit Log** (track all changes)
- 🔄 **Notifications** (email alerts, in-app notifications)
- 🔄 **Theme Editor** (customize colors & CSS)


## 🚀 Quick Start

---

### Requirements
- PHP 8.1+ with extensions:
  - pdo_mysql, mbstring, json, curl, intl, gd (optional for image ops), openssl, zip
- Web server: Nginx or Apache
- MySQL/MariaDB 10.5+ (or compatible)
- Composer (optional; if you plan to manage dependencies)

---

## Features

- Multi-user roles (Admin, Editor, Viewer)
- Posts, comments, sticky posts, trash management
- Backups (export/import), media manager
- Security (CSRF tokens, sanitized theme selection, optional email notifications)
- PWA-ready front-end
- Simple config via `config.ini`

---

Optional for email:
- msmtp or a local MTA if using SMTP
- App-password for Gmail or a dedicated SMTP account

---

## Quick Start (Docker)

This is the fastest way to get the blog running.

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

3) Create a `docker-compose.yml` (choose MySQL, Postgres or SQLite)
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

4) Start containers
```bash
docker compose up -d
```

5) Configure PHP and Apache inside the container (optional)
- Enable required extensions:
```bash
docker exec -it blog-advanced-web bash -lc "apt-get update && apt-get install -y libicu-dev libzip-dev libjpeg62-turbo-dev libpng-dev && docker-php-ext-install intl zip"
```
Note: official image ships with many basics. Adjust steps as needed.

6) Access the app
- Open http://localhost:3333
- Go to http://localhost:3333/admin to configure settings, language, timezone, and theme.

7) Database configuration
- Edit `config.ini` (see “Configuration” section) with the DB host `db` and your credentials from docker-compose:
  - mysql_host = db
  - mysql_port = 3306
  - mysql_user = bloguser
  - mysql_pass = changeme
  - db_name    = blog

8) Logs and backups
- Use the admin “Backups” page to create backups.
- Configure file paths in `config.ini` as needed.

---

## Manual Installation (Bare Metal)

1) Clone the repository into your web root
```bash
cd /var/www
git clone https://github.com/el-choco/blog-advanced.git
chown -R www-data:www-data blog-advanced
```

2) Configure your web server

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

3) PHP extensions
- Ensure required extensions are enabled:
```bash
php -m | grep -E 'pdo_mysql|mbstring|intl|zip|gd|curl|openssl|json'
```
Install missing ones via package manager (Debian/Ubuntu example):
```bash
apt-get install -y php8.2-{mysql,mbstring,intl,zip,gd,curl}
systemctl reload php8.2-fpm || systemctl restart apache2
```

4) Database
- Create a database and user:
```sql
CREATE DATABASE blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bloguser'@'%' IDENTIFIED BY 'changeme';
GRANT ALL PRIVILEGES ON blog.* TO 'bloguser'@'%';
FLUSH PRIVILEGES;
```

5) Configuration
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

6) Permissions
- Ensure the web server user can write to data/logs/ and any upload directories:
```bash
mkdir -p data/i data/t data/logs static/images
chown -R www-data:www-data data static/images
chmod -R 750 data static/images
```

7) Access
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

## .gitignore Recommendations

Add sensitive or local-only files:
```
# Secrets / local config
config.ini
msmtprc
data/*
*.bak
*backup*
*KAPUTT
internal_backup/
.env
```
Stop tracking existing files with secrets:
```bash
git rm --cached config.ini msmtprc 2>/dev/null || true
git add .gitignore
git commit -m "Add ignore patterns for secrets and backup artifacts"
git push
```

---

## Operations

- Backups: Use Admin → Backups to create and download archives. Store externally.
- Logs: Keep `data/logs/` readable and rotated.
- Theme: Switch under Admin → Appearance. Theme setting is sanitized to prevent invalid names.
- Trash: Admin → Trash for restore/permanent delete.
- Comments: Admin → Comments for moderation (email notifications optional).
- Upgrading: Pull changes, review `config.ini` diffs, run DB migrations if introduced in future versions.

---

## Troubleshooting

- “stale info” when pushing:
  - `git fetch origin main && git push --force-with-lease origin main`
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

