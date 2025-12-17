#!/bin/bash
# Backup Script - Wird vom HOST ausgeführt

BACKUP_DIR="/mnt/intenso2tb/blog-advanced/data/backups"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="backup_${TIMESTAMP}.sql"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

# Erstelle Backup-Verzeichnis falls nicht vorhanden
mkdir -p "$BACKUP_DIR"

# Führe mysqldump im DB-Container aus
cd /mnt/intenso2tb/blog-advanced
docker compose exec -T db mysqldump -u bloguser -pblogpass123 blog > "$FILEPATH"

# Prüfe ob Backup erfolgreich
if [ $? -eq 0 ] && [ -s "$FILEPATH" ]; then
    echo "SUCCESS:$FILENAME:$(stat -c%s "$FILEPATH")"
    exit 0
else
    rm -f "$FILEPATH"
    echo "ERROR:Backup fehlgeschlagen"
    exit 1
fi
