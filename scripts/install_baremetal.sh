#!/bin/bash

# Blog Advanced Installation Script - Bare Metal
# Version 2.0.0

echo "================================================"
echo "  📝 Blog Advanced - Bare Metal Installation"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

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

# Copy config file if it doesn't exist
if [ ! -f "data/config.ini" ]; then
    if [ -f "data/config.ini.example" ]; then
        echo -e "${GREEN}⚙️  Creating config.ini from example...${NC}"
        cp data/config.ini.example data/config.ini
        echo -e "${GREEN}✅ config.ini created${NC}"
    else
        echo -e "${RED}❌ config.ini.example not found!${NC}"
    fi
else
    echo -e "${YELLOW}ℹ️  config.ini already exists, skipping...${NC}"
fi

# Set ownership and permissions
echo -e "${GREEN}🔐 Setting ownership and permissions...${NC}"

# Try to set ownership to www-data (preferred method)
if id "www-data" &>/dev/null; then
    echo -e "${GREEN}📝 Setting ownership to www-data:www-data...${NC}"
    chown -R www-data:www-data data 2>/dev/null && echo -e "${GREEN}✅ data/ ownership set${NC}" || echo -e "${YELLOW}⚠️  Could not set ownership for data/ (requires elevated privileges)${NC}"
    chown -R www-data:www-data uploads 2>/dev/null && echo -e "${GREEN}✅ uploads/ ownership set${NC}" || echo -e "${YELLOW}⚠️  Could not set ownership for uploads/ (requires elevated privileges)${NC}"
    
    # Set permissions to 0775 (group-writable)
    echo -e "${GREEN}📝 Setting permissions to 0775...${NC}"
    chmod -R 0775 data uploads
    chmod -R 0775 logs/
    chmod -R 0775 sessions/
else
    echo -e "${YELLOW}⚠️  www-data user not found${NC}"
    echo -e "${YELLOW}⚠️  Falling back to standard permissions${NC}"
    
    # Fallback: Try ACL if available
    if command -v setfacl &> /dev/null; then
        echo -e "${GREEN}📝 Setting ACL permissions...${NC}"
        setfacl -R -m u:www-data:rwx data uploads logs sessions 2>/dev/null || echo -e "${YELLOW}⚠️  ACL not available${NC}"
    fi
    
    # Final fallback: 0777 permissions (last resort)
    echo -e "${YELLOW}⚠️  Using 0777 permissions as fallback${NC}"
    chmod -R 0777 data/
    chmod -R 0777 uploads/
    chmod -R 0777 logs/
    chmod -R 0777 sessions/
fi

if [ -f "data/config.ini" ]; then
    chmod 666 data/config.ini
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
echo "  1. Run: ./scripts/install_docker.sh"
echo "  2. Visit: http://localhost:3333/admin/"
echo ""
echo "📖 Documentation: https://github.com/el-choco/blog-advanced"
echo ""
