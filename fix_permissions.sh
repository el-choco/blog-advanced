#!/bin/bash

echo "🔐 Setting permissions..."

# Config schreibbar machen
chmod 666 /var/www/html/data/config.ini

# JavaScript und CSS lesbar
chmod 644 /var/www/html/static/scripts/app.js
chmod 644 /var/www/html/static/styles/custom1.css

# Admin-Ordner
chmod -R 755 /var/www/html/admin/
chown -R www-data:www-data /var/www/html/admin/

# Data-Ordner
chmod -R 775 /var/www/html/data/
chown -R www-data:www-data /var/www/html/data/

echo "✅ Berechtigungen gesetzt!"