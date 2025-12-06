#!/bin/bash

# Blog Advanced - Docker Quick Install
# Version 2.0.0

echo "================================================"
echo "  🐳 Blog Advanced - Docker Installation"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed!${NC}"
    echo "Please install Docker first: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed!${NC}"
    echo "Please install Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

echo -e "${GREEN}✅ Docker found${NC}"
echo -e "${GREEN}✅ Docker Compose found${NC}"
echo ""

# Run installation script first
if [ -f "./install.sh" ]; then
    echo -e "${GREEN}📦 Running installation script...${NC}"
    ./install.sh
else
    echo -e "${YELLOW}⚠️  install.sh not found, skipping...${NC}"
fi

# Set ownership and permissions for Docker
echo ""
echo -e "${GREEN}👤 Setting ownership for Docker...${NC}"
if command -v chown &> /dev/null; then
    # Set ownership (directories should already exist from install.sh)
    chown -R www-data:www-data data uploads 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set ownership (may need root/sudo)${NC}"
else
    echo -e "${YELLOW}⚠️  chown command not found, skipping ownership change${NC}"
fi

echo -e "${GREEN}🔐 Setting permissions for Docker...${NC}"
# Set permissions
chmod -R 0775 data 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set permissions on data${NC}"
chmod -R 0775 uploads 2>/dev/null || echo -e "${YELLOW}⚠️  Could not set permissions on uploads${NC}"

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo ""
        echo -e "${GREEN}📝 Creating .env file...${NC}"
        cp .env.example .env
        echo -e "${GREEN}✅ .env created${NC}"
    fi
fi

echo ""
echo -e "${GREEN}🐳 Starting Docker containers...${NC}"

# Use docker-compose or docker compose depending on what's available
if command -v docker-compose &> /dev/null; then
    docker-compose up -d
else
    docker compose up -d
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to start Docker containers${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}⏳ Waiting for database to initialize (30 seconds)...${NC}"
sleep 30

echo ""
echo -e "${GREEN}📊 Checking database connection...${NC}"

# Try to import schema
if docker exec blog-advanced-db mysql -u bloguser -pblogpass123 blog -e "SHOW TABLES;" &> /dev/null; then
    echo -e "${GREEN}✅ Database is ready${NC}"
    
    # Check if tables exist
    TABLE_COUNT=$(docker exec blog-advanced-db mysql -u bloguser -pblogpass123 blog -e "SHOW TABLES;" 2>/dev/null | wc -l)
    
    if [ "$TABLE_COUNT" -lt 2 ]; then
        echo ""
        echo -e "${GREEN}📊 Importing database schema...${NC}"
        if docker exec -i blog-advanced-db mysql -u bloguser -pblogpass123 blog < app/db/mysql/01_schema.sql; then
            echo -e "${GREEN}✅ Database schema imported successfully${NC}"
        else
            echo -e "${YELLOW}⚠️  Schema import failed (this is OK if already imported)${NC}"
        fi
    else
        echo -e "${GREEN}✅ Database tables already exist${NC}"
    fi
else
    echo -e "${RED}❌ Database connection failed${NC}"
    echo "Try running: docker-compose logs db"
fi

echo ""
echo "================================================"
echo -e "${GREEN}  🎉 Installation Complete!${NC}"
echo "================================================"
echo ""
echo "Access your blog:"
echo -e "  ${GREEN}📱 Frontend:${NC}    http://localhost:3333"
echo -e "  ${GREEN}⚙️  Admin Panel:${NC} http://localhost:3333/admin/"
echo -e "  ${GREEN}🗄️  phpMyAdmin:${NC}  http://localhost:3334"
echo ""
echo "Default credentials:"
echo "  Username: admin"
echo "  Password: admin123"
echo ""
echo -e "${RED}⚠️  IMPORTANT: Change the default password immediately!${NC}"
echo ""
echo "Database credentials (phpMyAdmin):"
echo "  Server: db"
echo "  User: root"
echo "  Password: rootpassword"
echo ""
echo "Port configuration:"
echo "  Web Server:  3333"
echo "  phpMyAdmin:  3334"
echo "  MySQL:       3307 (external access)"
echo ""
echo "Useful commands:"
echo "  Stop:        docker-compose down"
echo "  Restart:     docker-compose restart"
echo "  View logs:   docker-compose logs -f"
echo "  Remove all:  docker-compose down -v"
echo ""
