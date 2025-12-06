#!/bin/bash

# Blog Advanced - Docker Installation Script
# Version 2.1.0
# This script sets up directories with secure permissions for Docker installations

set -e

echo "================================================"
echo "  🐳 Blog Advanced - Docker Installation"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create necessary directories
echo -e "${GREEN}📁 Creating directories...${NC}"
mkdir -p uploads
mkdir -p data
mkdir -p data/backups
mkdir -p logs
mkdir -p sessions

echo -e "${GREEN}✅ Directories created${NC}"

# Detect or use PUID/PGID from environment
echo ""
echo -e "${GREEN}🔍 Detecting user for Docker permissions...${NC}"

if [ -n "$PUID" ] && [ -n "$PGID" ]; then
    echo -e "${GREEN}✅ Using PUID=${PUID} and PGID=${PGID} from environment${NC}"
    TARGET_UID=$PUID
    TARGET_GID=$PGID
else
    # Try to detect container user
    WEB_USER=""
    if id www-data &>/dev/null; then
        WEB_USER="www-data"
        TARGET_UID=$(id -u www-data)
        TARGET_GID=$(id -g www-data)
        echo -e "${GREEN}✅ Found user: www-data (UID=${TARGET_UID}, GID=${TARGET_GID})${NC}"
    elif id apache &>/dev/null; then
        WEB_USER="apache"
        TARGET_UID=$(id -u apache)
        TARGET_GID=$(id -g apache)
        echo -e "${GREEN}✅ Found user: apache (UID=${TARGET_UID}, GID=${TARGET_GID})${NC}"
    elif id nginx &>/dev/null; then
        WEB_USER="nginx"
        TARGET_UID=$(id -u nginx)
        TARGET_GID=$(id -g nginx)
        echo -e "${GREEN}✅ Found user: nginx (UID=${TARGET_UID}, GID=${TARGET_GID})${NC}"
    else
        # Use current user as fallback
        TARGET_UID=$(id -u)
        TARGET_GID=$(id -g)
        echo -e "${YELLOW}⚠️  Using current user (UID=${TARGET_UID}, GID=${TARGET_GID})${NC}"
    fi
fi

# Set ownership and permissions
echo ""
echo -e "${GREEN}🔐 Setting secure permissions...${NC}"

# Check if we can chown (running as root or have permissions)
if [ "$EUID" -eq 0 ] || [ "$(id -u)" = "0" ]; then
    # Running as root - can set ownership
    echo -e "${GREEN}Setting ownership to UID=${TARGET_UID}, GID=${TARGET_GID}...${NC}"
    if chown -R "${TARGET_UID}:${TARGET_GID}" uploads/ data/ logs/ sessions/ 2>/dev/null; then
        # Set secure permissions (0755)
        echo -e "${GREEN}Setting permissions to 0755...${NC}"
        chmod -R 0755 uploads/ data/ logs/ sessions/
        echo -e "${GREEN}✅ Ownership and permissions set successfully${NC}"
    else
        echo -e "${YELLOW}⚠️  chown failed, trying ACL fallback...${NC}"
        if command -v setfacl &>/dev/null; then
            setfacl -R -m u:${TARGET_UID}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null && \
            setfacl -R -d -m u:${TARGET_UID}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null
            if [ $? -eq 0 ]; then
                chmod -R 0755 uploads/ data/ logs/ sessions/
                echo -e "${GREEN}✅ ACL set for UID ${TARGET_UID} with 0755 permissions${NC}"
            else
                echo -e "${RED}⚠️  WARNING: ACL failed, using 0777 permissions${NC}"
                echo -e "${RED}⚠️  SECURITY RISK: Please manually set proper ownership${NC}"
                chmod -R 0777 uploads/ data/ logs/ sessions/
            fi
        else
            echo -e "${RED}⚠️  WARNING: ACL not available, using 0777 permissions${NC}"
            echo -e "${RED}⚠️  SECURITY RISK: Please manually set proper ownership${NC}"
            chmod -R 0777 uploads/ data/ logs/ sessions/
        fi
    fi
    
else
    # Not running as root - try permissions only
    echo -e "${BLUE}ℹ️  Not running as root, setting permissions only...${NC}"
    
    if chmod -R 0755 uploads/ data/ logs/ sessions/ 2>/dev/null; then
        echo -e "${GREEN}✅ Permissions set to 0755${NC}"
    else
        # Try ACL as fallback
        if command -v setfacl &>/dev/null; then
            echo -e "${BLUE}ℹ️  Using ACL for permissions...${NC}"
            setfacl -R -m u:${TARGET_UID}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null || true
            setfacl -R -d -m u:${TARGET_UID}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null || true
            echo -e "${GREEN}✅ ACL set for UID ${TARGET_UID}${NC}"
        else
            # Last resort - 0777 with warning
            echo -e "${RED}⚠️  WARNING: Unable to set ownership or ACL${NC}"
            echo -e "${RED}⚠️  Using 0777 permissions - SECURITY RISK${NC}"
            echo -e "${RED}⚠️  Please ensure Docker container runs with appropriate user${NC}"
            chmod -R 0777 uploads/ data/ logs/ sessions/ 2>/dev/null || true
        fi
    fi
fi

# Summary
echo ""
echo "================================================"
echo -e "${GREEN}  📊 Directory Status Summary${NC}"
echo "================================================"
echo ""

for dir in uploads data data/backups logs sessions; do
    if [ -d "$dir" ]; then
        PERMS=$(stat -c "%a" "$dir" 2>/dev/null || stat -f "%OLp" "$dir" 2>/dev/null || echo "unknown")
        OWNER=$(stat -c "%U:%G" "$dir" 2>/dev/null || stat -f "%Su:%Sg" "$dir" 2>/dev/null || echo "unknown")
        echo -e "  ${dir}/"
        echo -e "    Permissions: ${PERMS}"
        echo -e "    Owner: ${OWNER}"
        echo ""
    fi
done

echo "================================================"
echo -e "${GREEN}  ✅ Docker Installation Complete!${NC}"
echo "================================================"
echo ""
echo "Docker-specific notes:"
echo ""
if [ -n "$PUID" ] && [ -n "$PGID" ]; then
    echo "✅ Using PUID/PGID from environment"
else
    echo "ℹ️  Set PUID/PGID environment variables for custom user mapping"
fi
echo ""
echo "Example usage in docker-compose.yml:"
echo "  environment:"
echo "    - PUID=1000"
echo "    - PGID=1000"
echo ""
echo "Next steps:"
echo ""
echo "1. Verify directory permissions above"
echo "2. Start Docker containers with appropriate user mapping"
echo "3. Import database schema"
echo "4. Configure config.ini"
echo ""
echo "📖 Documentation: https://github.com/el-choco/blog-advanced"
echo ""
