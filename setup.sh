#!/bin/bash

###############################################################################
# Logic IDE Setup Script - FIXED VERSION 2026
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' 

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Helper for cross-platform sed -i
inplace_sed() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "$1" "$2"
    else
        sed -i "$1" "$2"
    fi
}

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Logic IDE Setup Script${NC}"
echo -e "${BLUE}========================================${NC}"

# Detect OS
OS_TYPE="$(uname -s)"
case "$OS_TYPE" in
    Linux*)     OS="linux";;
    Darwin*)    OS="macos";;
    *)          OS="windows";;
esac

echo -e "${YELLOW}Detected OS: $OS${NC}"

# 1. Check for PHP
# if ! command -v php &> /dev/null; then
#     echo -e "${RED}Error: PHP not found!${NC}"
#     exit 1
# fi
# echo -e "${GREEN}✓ PHP found: $(php -r 'echo PHP_VERSION;')${NC}"

# 2. Check for MySQL
# MYSQL_CMD=""
# MYSQL_AVAILABLE=false
# for cmd in mysql mysql.exe; do
#     if command -v $cmd &> /dev/null; then
#         MYSQL_CMD=$cmd
#         MYSQL_AVAILABLE=true
#         break
#     fi
# done

# 3. Handle .env.local
if [ ! -f ".env.local" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env.local
        echo -e "${GREEN}✓ Created .env.local${NC}"
    else
        touch .env.local
    fi
fi

# 4. Database Config (The part where it usually hangs)
if [ "$MYSQL_AVAILABLE" = true ]; then
    echo -e "${YELLOW}Configuring Database...${NC}"
    
    # Check if we are in an interactive terminal
    if [ -t 0 ]; then
        read -p "  MySQL Host [localhost]: " DB_HOST
        DB_HOST=${DB_HOST:-localhost}
        read -p "  MySQL Username [root]: " DB_USER
        DB_USER=${DB_USER:-root}
        read -s -p "  MySQL Password: " DB_PASS
        echo ""
        read -p "  Database Name [logic_ide]: " DB_NAME
        DB_NAME=${DB_NAME:-logic_ide}
    else
        # Default values for non-interactive shells
        DB_HOST="localhost"
        DB_USER="root"
        DB_PASS=""
        DB_NAME="logic_ide"
    fi

    # Update .env.local using the helper function
    inplace_sed "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env.local
    inplace_sed "s|DB_USER=.*|DB_USER=$DB_USER|" .env.local
    inplace_sed "s|DB_PASS=.*|DB_PASS=$DB_PASS|" .env.local
    inplace_sed "s|DB_NAME=.*|DB_NAME=$DB_NAME|" .env.local
fi

# 5. Create Router if missing
if [ ! -f "router.php" ]; then
    cat > router.php << 'EOF'
<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api/') === 0) {
    $phpFile = __DIR__ . '/api/' . substr($uri, 5);
    if (file_exists($phpFile)) { require $phpFile; return; }
}
if (is_file(__DIR__ . '/public' . $uri)) return false;
readfile(__DIR__ . '/public/index.html');
EOF
    echo -e "${GREEN}✓ router.php created${NC}"
fi

echo -e "${GREEN}Setup complete! Starting server...${NC}"
echo -e "Visit: ${BLUE}http://localhost:3699${NC}"

# Start Server
php -S localhost:3699 router.php