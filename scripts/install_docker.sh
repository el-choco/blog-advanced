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

# Set ownership and permissions for Docker
echo -e "${GREEN}🔐 Setting ownership and permissions for Docker...${NC}"

# Try to set ownership to www-data (preferred method for Docker)
if id "www-data" &>/dev/null; then
    echo -e "${GREEN}📝 Setting ownership to www-data:www-data...${NC}"
    chown -R www-data:www-data data 2>/dev/null && echo -e "${GREEN}✅ data/ ownership set${NC}" || echo -e "${YELLOW}⚠️  Could not set ownership for data/ (requires elevated privileges)${NC}"
    chown -R www-data:www-data uploads 2>/dev/null && echo -e "${GREEN}✅ uploads/ ownership set${NC}" || echo -e "${YELLOW}⚠️  Could not set ownership for uploads/ (requires elevated privileges)${NC}"
    
    # Set permissions to 0775 (group-writable)
    echo -e "${GREEN}📝 Setting permissions to 0775...${NC}"
    chmod -R 0775 data uploads data/backups
    chmod -R 0775 logs/
    chmod -R 0775 sessions/
else
    echo -e "${YELLOW}⚠️  www-data user not found on host system${NC}"
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
