#!/bin/bash

# Fix Admin-Links in allen Admin-Dateien
echo "🔧 Fixing Admin view links..."

# posts.php
sed -i 's|href="../?post=|href="../#id=|g' /var/www/html/admin/posts.php

# index.php
sed -i 's|href="../?post=|href="../#id=|g' /var/www/html/admin/index.php

# trash.php
sed -i 's|href="../?post=|href="../#id=|g' /var/www/html/admin/trash.php

echo "✅ Admin-Links wurden aktualisiert!"
echo ""
echo "📝 Dateien geändert:"
echo "  - /var/www/html/admin/posts.php"
echo "  - /var/www/html/admin/index.php"
echo "  - /var/www/html/admin/trash.php"
echo ""
echo "🎉 Fertig! Teste jetzt die Admin-Seite."