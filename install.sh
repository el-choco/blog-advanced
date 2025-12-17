#!/bin/bash

# Blog Advanced Installation Script
# Version 2.0.0

echo "================================================"
echo "  📝 Blog Advanced - Installation"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Web user/group (adjust if your webserver runs as a different user, e.g. nginx/apache)
WEB_USER="www-data"
WEB_GROUP="www-data"

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    echo -e "${YELLOW}⚠️  WARNING: Running as root is not recommended${NC}"
    echo ""
fi

# Create necessary directories
echo -e "${GREEN}📁 Creating directories...${NC}"
mkdir -p data/posts
mkdir -p data/images
mkdir -p data/files
mkdir -p data/users
mkdir -p data/backups
mkdir -p data/cache
mkdir -p uploads/images
mkdir -p uploads/files
mkdir -p logs
mkdir -p sessions
# Theme Editor: ensure custom CSS directory exists
mkdir -p static/styles

# Create .gitkeep files
echo -e "${GREEN}📄 Creating .gitkeep files...${NC}"
touch data/posts/.gitkeep
touch data/images/.gitkeep
touch data/files/.gitkeep
touch data/users/.gitkeep
touch data/backups/.gitkeep
touch data/cache/.gitkeep
touch uploads/images/.gitkeep
touch uploads/files/.gitkeep
touch logs/.gitkeep
touch sessions/.gitkeep

# Theme Editor: ensure custom1.css exists so the editor can write to it
if [ ! -f "static/styles/custom1.css" ]; then
    echo -e "${GREEN}🖊️  Creating static/styles/custom1.css...${NC}"
    touch static/styles/custom1.css
fi

# Copy config file if it doesn't exist
if [ ! -f "data/config.ini" ]; then
    if [ -f "data/config.ini.example" ]; then
        echo -e "${GREEN}⚙️  Creating config.ini from example...${NC}"
        cp data/config.ini.example data/config.ini
        # ensure correct ownership right after creation (best effort)
        if command -v chown &> /dev/null; then
            chown "$WEB_USER":"$WEB_GROUP" data/config.ini 2>/dev/null || true
        fi
        echo -e "${GREEN}✅ config.ini created${NC}"
    else
        echo -e "${RED}❌ data/config.ini.example not found!${NC}"
    fi
else
    echo -e "${YELLOW}ℹ️  data/config.ini already exists, skipping...${NC}"
fi

# Set ownership
echo -e "${GREEN}👤 Setting ownership...${NC}"
if command -v chown &> /dev/null; then
    chown -R "$WEB_USER":"$WEB_GROUP" data uploads 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set ownership on data/uploads (may need root/sudo)${NC}"
    chown -R "$WEB_USER":"$WEB_GROUP" logs sessions 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set ownership on logs/sessions (may need root/sudo)${NC}"
    chown -R "$WEB_USER":"$WEB_GROUP" static/styles 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set ownership on static/styles (may need root/sudo)${NC}"
    # explicitly ensure config.ini belongs to web user so 777 is not needed
    if [ -f "data/config.ini" ]; then
        chown "$WEB_USER":"$WEB_GROUP" data/config.ini 2>/dev/null || echo -e "${YELLOW}⚠️  Could not chown data/config.ini (may need root/sudo)${NC}"
    fi
    # ensure custom1.css belongs to web user
    if [ -f "static/styles/custom1.css" ]; then
        chown "$WEB_USER":"$WEB_GROUP" static/styles/custom1.css 2>/dev/null || echo -e "${YELLOW}⚠️  Could not chown static/styles/custom1.css (may need root/sudo)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  chown command not found, skipping ownership change${NC}"
fi

# Set permissions
echo -e "${GREEN}🔐 Setting permissions...${NC}"
chmod -R 0775 data 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set permissions on data${NC}"
chmod -R 0775 uploads 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set permissions on uploads${NC}"
chmod -R 0775 logs/ 2>/dev/null || true
chmod -R 0775 sessions/ 2>/dev/null || true
chmod -R 0775 static/styles 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set permissions on static/styles${NC}"

# secure permissions for config.ini (read/write for owner+group)
if [ -f "data/config.ini" ]; then
    chmod 0664 data/config.ini 2>/dev/null || true
fi

# Theme Editor: secure permissions for custom CSS file (rw for owner/group)
if [ -f "static/styles/custom1.css" ]; then
    chmod 0664 static/styles/custom1.css 2>/dev/null || true
fi

# Check for PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo ""
    echo -e "${GREEN}✅ PHP found: $PHP_VERSION${NC}"
    
    # Check for required PHP extensions
    echo ""
    echo -e "${GREEN}🔍 Checking PHP extensions...${NC}"
    
    extensions=("pdo" "pdo_mysql" "pdo_sqlite" "gd" "mbstring" "fileinfo" "curl" "zip")
    
    for ext in "${extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo -e "  ${GREEN}✅ $ext${NC}"
        else
            echo -e "  ${RED}❌ $ext - MISSING!${NC}"
        fi
    done
else
    echo -e "${YELLOW}⚠️  PHP not found in PATH${NC}"
fi

echo ""
echo "================================================"
echo -e "${GREEN}  ✅ Installation Complete!${NC}"
echo "================================================"
echo ""
echo "Next steps:"
echo ""
echo "📦 Manual Installation:"
echo "  1. Edit data/config.ini with your database credentials"
echo "  2. Import database: mysql -u root -p blog < app/db/mysql/01_schema.sql"
echo "  3. Configure your web server to point to this directory"
echo "  4. Visit: http://localhost/admin/"
echo "  5. Login: admin / admin123"
echo "  6. ⚠️  CHANGE THE DEFAULT PASSWORD IMMEDIATELY!"
echo ""
echo "🐳 Docker Installation:"
echo "  1. Run: ./docker-install.sh"
echo "  2. Visit: http://localhost:8080/admin/"
echo ""
echo "📖 Documentation: https://github.com/el-choco/blog-advanced"
echo ""