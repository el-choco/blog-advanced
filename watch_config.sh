#!/bin/bash
# Überwacht config.ini und formatiert bei Änderungen

while inotifywait -e modify config.ini 2>/dev/null; do
    sleep 1
    ./format_config.sh
    echo "🔄 config.ini automatisch formatiert!"
done
