#!/bin/bash
#=================================================================
# 🧹 CONFIG.INI FORMATTER
# Formatiert die config.ini mit sauberen Zwischenräumen
#=================================================================

CONFIG_FILE="config.ini"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ $CONFIG_FILE nicht gefunden!"
    exit 1
fi

# Backup
cp "$CONFIG_FILE" "${CONFIG_FILE}.backup-$(date +%Y%m%d-%H%M%S)"

# Erstelle formatierte Version
cat > "$CONFIG_FILE" << 'ENDCONFIG'
[database]
db_connection = "mysql"
mysql_host = "db"
mysql_port = "3306"
mysql_user = "bloguser"
mysql_pass = "blogpass123"
db_name = "blog"

[profile]
title = "Pacos-Tech-Blog"
name = "Paco"
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
logs = "0"
SOFT_DELETE = "0"
HARD_DELETE_FILES = "1"
AUTO_CLEANUP_IMAGES = ""

[visitor]
enabled = "1"
title = ""
name = ""
subtitle = ""
lang = "de"
timezone = "Europe/Berlin"

[email]
notifications_enabled = "1"
admin_email = "paquele@gmail.com"
notify_admin_new_comment = "1"
notify_user_approved = "1"
from_email = "noreply@pacos-blog.dynv6.net"
from_name = "Pacos-Tech-Blog"
title = "Pacos-Tech-Blog"
name = "Paco"
subtitle = "alles rund um docker..."
lang = "de"
timezone = "Europe/Berlin"
theme = "theme02"
ENDCONFIG

echo "✅ config.ini formatiert!"
echo ""
echo "📋 Vorschau:"
cat "$CONFIG_FILE"
