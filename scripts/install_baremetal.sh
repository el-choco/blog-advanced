#!/bin/bash

# Blog Advanced - Bare Metal Installation Script
# Version 2.1.0
# This script sets up directories with secure permissions for bare-metal installations

set -e

echo "================================================"
echo "  📝 Blog Advanced - Bare Metal Installation"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    echo -e "${YELLOW}⚠️  WARNING: Running as root. Will set ownership to web server user.${NC}"
    echo ""
    RUNNING_AS_ROOT=true
else
    echo -e "${BLUE}ℹ️  Running as regular user${NC}"
    RUNNING_AS_ROOT=false
fi

# Detect web server user
echo -e "${GREEN}🔍 Detecting web server user...${NC}"
WEB_USER=""
if id www-data &>/dev/null; then
    WEB_USER="www-data"
    echo -e "${GREEN}✅ Found user: www-data${NC}"
elif id apache &>/dev/null; then
    WEB_USER="apache"
    echo -e "${GREEN}✅ Found user: apache${NC}"
elif id nginx &>/dev/null; then
    WEB_USER="nginx"
    echo -e "${GREEN}✅ Found user: nginx${NC}"
else
    echo -e "${YELLOW}⚠️  No standard web server user found${NC}"
fi

# Create necessary directories
echo ""
echo -e "${GREEN}📁 Creating directories...${NC}"
mkdir -p uploads
mkdir -p data
mkdir -p data/backups
mkdir -p logs
mkdir -p sessions

echo -e "${GREEN}✅ Directories created${NC}"

# Set ownership and permissions
echo ""
echo -e "${GREEN}🔐 Setting secure permissions...${NC}"

if [ "$RUNNING_AS_ROOT" = true ] && [ -n "$WEB_USER" ]; then
    # Running as root and web user detected - set ownership and secure permissions
    echo -e "${GREEN}Setting ownership to ${WEB_USER}...${NC}"
    chown -R "$WEB_USER:$WEB_USER" uploads/ data/ logs/ sessions/ 2>/dev/null || true
    
    # Set secure permissions (0755 for directories, 0644 for files)
    echo -e "${GREEN}Setting permissions to 0755...${NC}"
    chmod -R 0755 uploads/ data/ logs/ sessions/
    
    # Verify permissions
    echo -e "${GREEN}✅ Ownership set to ${WEB_USER}, permissions set to 0755${NC}"
    
elif [ "$RUNNING_AS_ROOT" = true ] && [ -z "$WEB_USER" ]; then
    # Running as root but no web user detected - try ACL fallback
    echo -e "${YELLOW}⚠️  No web server user detected${NC}"
    
    if command -v setfacl &>/dev/null; then
        echo -e "${BLUE}ℹ️  Using ACL for permissions...${NC}"
        # Set ACL for common web users
        for user in www-data apache nginx; do
            if id "$user" &>/dev/null; then
                setfacl -R -m u:${user}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null || true
                setfacl -R -d -m u:${user}:rwx uploads/ data/ logs/ sessions/ 2>/dev/null || true
                echo -e "${GREEN}✅ ACL set for user: ${user}${NC}"
            fi
        done
        chmod -R 0755 uploads/ data/ logs/ sessions/
    else
        # Last resort - 0777 with warning
        echo -e "${RED}⚠️  WARNING: ACL not available, using 0777 permissions${NC}"
        echo -e "${RED}⚠️  SECURITY RISK: Please manually set proper ownership after installation${NC}"
        chmod -R 0777 uploads/ data/ logs/ sessions/
    fi
    
else
    # Not running as root - check if current user can write
    echo -e "${BLUE}Setting permissions for current user...${NC}"
    
    # Set permissions for current user
    if chmod -R 0755 uploads/ data/ logs/ sessions/ 2>/dev/null; then
        echo -e "${GREEN}✅ Permissions set to 0755${NC}"
    else
        echo -e "${YELLOW}⚠️  Cannot set 0755, trying ACL...${NC}"
        
        if command -v setfacl &>/dev/null; then
            setfacl -R -m u:$(whoami):rwx uploads/ data/ logs/ sessions/ 2>/dev/null && \
            setfacl -R -d -m u:$(whoami):rwx uploads/ data/ logs/ sessions/ 2>/dev/null
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✅ ACL set for current user${NC}"
            else
                echo -e "${RED}⚠️  WARNING: ACL failed, unable to set permissions${NC}"
                echo -e "${RED}⚠️  Please run with sudo or set permissions manually${NC}"
            fi
        else
            echo -e "${RED}⚠️  WARNING: Unable to set permissions (no sudo, no ACL)${NC}"
            echo -e "${RED}⚠️  Please run with sudo or set permissions manually${NC}"
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
echo -e "${GREEN}  ✅ Installation Complete!${NC}"
echo "================================================"
echo ""
echo "Next steps:"
echo ""
echo "1. Verify directory permissions above"
if [ "$RUNNING_AS_ROOT" = false ]; then
    echo "2. Consider re-running with sudo for proper ownership"
fi
echo "3. Configure your web server to serve from this directory"
echo "4. Import database schema"
echo "5. Configure config.ini with database credentials"
echo ""
echo "📖 Documentation: https://github.com/el-choco/blog-advanced"
echo ""
