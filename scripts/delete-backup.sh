#!/bin/bash
# Delete Script - Wird vom HOST ausgeführt

BACKUP_FILE="$1"
BACKUP_DIR="/mnt/intenso2tb/blog-advanced/data/backups"
FILEPATH="${BACKUP_DIR}/${BACKUP_FILE}"

# Prüfe ob Datei existiert
if [ ! -f "$FILEPATH" ]; then
    echo "ERROR:Backup-Datei nicht gefunden"
    exit 1
fi

# Lösche Datei
rm -f "$FILEPATH"

if [ $? -eq 0 ]; then
    echo "SUCCESS:Backup gelöscht"
    exit 0
else
    echo "ERROR:Löschen fehlgeschlagen"
    exit 1
fi
