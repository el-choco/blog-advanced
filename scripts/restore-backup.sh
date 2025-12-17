#!/bin/bash
# Restore Script - Wird vom HOST ausgeführt

BACKUP_FILE="$1"
BACKUP_DIR="/mnt/intenso2tb/blog-advanced/data/backups"
FILEPATH="${BACKUP_DIR}/${BACKUP_FILE}"

# Prüfe ob Datei existiert
if [ ! -f "$FILEPATH" ]; then
    echo "ERROR:Backup-Datei nicht gefunden"
    exit 1
fi

# Restore in DB-Container
cd /mnt/intenso2tb/blog-advanced
docker compose exec -T db mysql -u bloguser -pblogpass123 blog < "$FILEPATH"

if [ $? -eq 0 ]; then
    echo "SUCCESS:Backup wiederhergestellt"
    exit 0
else
    echo "ERROR:Wiederherstellung fehlgeschlagen"
    exit 1
fi
