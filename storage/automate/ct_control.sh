#!/bin/bash
# ========================================
# CyberTirah Framework Control Center
# Unified Management Script (Linux/Mac)
# ========================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Main Menu
main_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   CyberTirah Framework Control Center${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Apache/MySQL Management"
    echo -e "${GREEN}[2]${NC} Environment Setup"
    echo -e "${GREEN}[3]${NC} Database Management"
    echo -e "${GREEN}[4]${NC} Cache Management"
    echo -e "${GREEN}[5]${NC} Complete System Setup"
    echo -e "${GREEN}[6]${NC} Development Tools"
    echo -e "${GREEN}[7]${NC} Backup & Restore"
    echo -e "${GREEN}[8]${NC} System Information"
    echo -e "${GREEN}[0]${NC} Exit"
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) server_menu ;;
        2) env_menu ;;
        3) db_menu ;;
        4) cache_menu ;;
        5) complete_setup ;;
        6) dev_tools ;;
        7) backup_menu ;;
        8) system_info ;;
        0) exit 0 ;;
        *) echo -e "${RED}Invalid option!${NC}"; sleep 2; main_menu ;;
    esac
}

# Server Management
server_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Apache/MySQL Management${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Start Services"
    echo -e "${GREEN}[2]${NC} Stop Services"
    echo -e "${GREEN}[3]${NC} Restart Services"
    echo -e "${GREEN}[4]${NC} Check Status"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) start_services ;;
        2) stop_services ;;
        3) restart_services ;;
        4) check_services ;;
        0) main_menu ;;
        *) server_menu ;;
    esac
}

start_services() {
    echo ""
    echo -e "${YELLOW}Starting services...${NC}"
    sudo service apache2 start 2>/dev/null || sudo systemctl start httpd 2>/dev/null
    echo -e "${GREEN}✓ Apache started${NC}"
    sudo service mysql start 2>/dev/null || sudo systemctl start mysqld 2>/dev/null
    echo -e "${GREEN}✓ MySQL started${NC}"
    echo ""
    read -p "Press Enter to continue..."
    server_menu
}

stop_services() {
    echo ""
    echo -e "${YELLOW}Stopping services...${NC}"
    sudo service apache2 stop 2>/dev/null || sudo systemctl stop httpd 2>/dev/null
    echo -e "${GREEN}✓ Apache stopped${NC}"
    sudo service mysql stop 2>/dev/null || sudo systemctl stop mysqld 2>/dev/null
    echo -e "${GREEN}✓ MySQL stopped${NC}"
    echo ""
    read -p "Press Enter to continue..."
    server_menu
}

restart_services() {
    stop_services
    sleep 2
    start_services
}

check_services() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Service Status${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Apache:${NC}"
    ps aux | grep -i apache2 | grep -v grep || ps aux | grep -i httpd | grep -v grep
    echo ""
    echo -e "${YELLOW}MySQL:${NC}"
    ps aux | grep -i mysql | grep -v grep
    echo ""
    echo -e "${YELLOW}Listening Ports:${NC}"
    netstat -tuln | grep -E ':80|:3306' 2>/dev/null || ss -tuln | grep -E ':80|:3306'
    echo ""
    read -p "Press Enter to continue..."
    server_menu
}

# Environment Setup
env_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Environment Setup${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Create Local .env (HTTP)"
    echo -e "${GREEN}[2]${NC} Create Production .env (HTTPS)"
    echo -e "${GREEN}[3]${NC} Edit .env File"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) create_local_env ;;
        2) create_prod_env ;;
        3) edit_env ;;
        0) main_menu ;;
        *) env_menu ;;
    esac
}

create_local_env() {
    echo ""
    echo -e "${YELLOW}Creating local .env file...${NC}"
    cd "$ROOT_DIR"
    cat > .env << 'EOF'
# CyberTirah Framework - Local Development
APP_NAME=CT Frame
APP_ENV=development
APP_DEBUG=true
APP_VERSION=2.0.0
FORCE_HTTPS=false
APP_URL=http://localhost
APP_PORT=8080

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=ct_frame
DB_CHARSET=utf8mb4

# Cache and Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=7200

# Logging
LOG_LEVEL=debug
EOF
    echo -e "${GREEN}✓ Local .env file created!${NC}"
    echo ""
    read -p "Press Enter to continue..."
    env_menu
}

create_prod_env() {
    echo ""
    echo -e "${YELLOW}Creating production .env file...${NC}"
    cd "$ROOT_DIR"
    cat > .env << 'EOF'
# CyberTirah Framework - Production
APP_NAME=CT Frame
APP_ENV=production
APP_DEBUG=false
APP_VERSION=2.0.0
FORCE_HTTPS=true
APP_URL=https://yourdomain.com

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
DB_DATABASE=ct_frame
DB_CHARSET=utf8mb4

# Cache and Session
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=3600

# Logging
LOG_LEVEL=error
EOF
    echo -e "${GREEN}✓ Production .env file created!${NC}"
    echo -e "${RED}IMPORTANT: Update database credentials!${NC}"
    echo ""
    read -p "Press Enter to continue..."
    env_menu
}

edit_env() {
    cd "$ROOT_DIR"
    if [ -f .env ]; then
        ${EDITOR:-nano} .env
    else
        echo -e "${RED}✗ .env file not found!${NC}"
        sleep 2
    fi
    env_menu
}

# Database Management
db_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Database Management${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Import Database Schema"
    echo -e "${GREEN}[2]${NC} Setup Login System"
    echo -e "${GREEN}[3]${NC} Backup Database"
    echo -e "${GREEN}[4]${NC} Import Custom SQL"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) import_schema ;;
        2) setup_login ;;
        3) backup_db ;;
        4) import_custom ;;
        0) main_menu ;;
        *) db_menu ;;
    esac
}

import_schema() {
    echo ""
    read -p "MySQL Host (default: localhost): " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    read -p "MySQL Username (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    read -sp "MySQL Password: " DB_PASS
    echo ""
    read -p "Database Name: " DB_NAME
    
    if [ -z "$DB_NAME" ]; then
        echo -e "${RED}Database name required!${NC}"
        sleep 2
        db_menu
        return
    fi
    
    cd "$ROOT_DIR"
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database_schema.sql 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Schema imported successfully!${NC}"
    else
        echo -e "${RED}✗ Import failed${NC}"
    fi
    echo ""
    read -p "Press Enter to continue..."
    db_menu
}

setup_login() {
    import_schema
    echo ""
    echo -e "${GREEN}Test Accounts Created:${NC}"
    echo "  Admin: admin / admin123"
    echo "  Student: john.student / admin123"
    echo "  Teacher: jane.teacher / admin123"
    echo ""
    read -p "Press Enter to continue..."
    db_menu
}

backup_db() {
    echo ""
    read -p "MySQL Host (default: localhost): " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    read -p "MySQL Username (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    read -sp "MySQL Password: " DB_PASS
    echo ""
    read -p "Database Name: " DB_NAME
    
    if [ -z "$DB_NAME" ]; then
        echo -e "${RED}Database name required!${NC}"
        sleep 2
        db_menu
        return
    fi
    
    BACKUP_DIR="$ROOT_DIR/Backups/db"
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_$(date +%Y%m%d_%H%M%S).sql"
    
    mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database backed up to: $BACKUP_FILE${NC}"
    else
        echo -e "${RED}✗ Backup failed${NC}"
    fi
    echo ""
    read -p "Press Enter to continue..."
    db_menu
}

import_custom() {
    read -p "Enter SQL file path: " SQL_FILE
    if [ ! -f "$SQL_FILE" ]; then
        echo -e "${RED}✗ File not found!${NC}"
        sleep 2
        db_menu
        return
    fi
    
    read -p "MySQL Host (default: localhost): " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    read -p "MySQL Username (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    read -sp "MySQL Password: " DB_PASS
    echo ""
    read -p "Database Name: " DB_NAME
    
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SQL imported successfully!${NC}"
    else
        echo -e "${RED}✗ Import failed${NC}"
    fi
    echo ""
    read -p "Press Enter to continue..."
    db_menu
}

# Cache Management
cache_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Cache Management${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Clear All Cache"
    echo -e "${GREEN}[2]${NC} Clear Route Cache"
    echo -e "${GREEN}[3]${NC} Clear View Cache"
    echo -e "${GREEN}[4]${NC} Clear Session Cache"
    echo -e "${GREEN}[5]${NC} Clear Log Files"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) clear_all_cache ;;
        2) clear_route_cache ;;
        3) clear_view_cache ;;
        4) clear_session_cache ;;
        5) clear_logs ;;
        0) main_menu ;;
        *) cache_menu ;;
    esac
}

clear_all_cache() {
    echo ""
    echo -e "${YELLOW}Clearing all cache...${NC}"
    cd "$ROOT_DIR/Storage"
    rm -f cache/*.php cache/*.json temp/* 2>/dev/null
    echo -e "${GREEN}✓ All cache cleared${NC}"
    echo ""
    read -p "Press Enter to continue..."
    cache_menu
}

clear_route_cache() {
    echo ""
    cd "$ROOT_DIR/Storage"
    rm -f cache/routes.php 2>/dev/null
    echo -e "${GREEN}✓ Route cache cleared${NC}"
    echo ""
    read -p "Press Enter to continue..."
    cache_menu
}

clear_view_cache() {
    echo ""
    cd "$ROOT_DIR/Storage"
    rm -f cache/views/* 2>/dev/null
    echo -e "${GREEN}✓ View cache cleared${NC}"
    echo ""
    read -p "Press Enter to continue..."
    cache_menu
}

clear_session_cache() {
    echo ""
    cd "$ROOT_DIR/Storage"
    rm -f cache/sessions/* 2>/dev/null
    echo -e "${GREEN}✓ Session cache cleared${NC}"
    echo ""
    read -p "Press Enter to continue..."
    cache_menu
}

clear_logs() {
    echo ""
    cd "$ROOT_DIR/Storage"
    rm -f logs/*.log 2>/dev/null
    echo -e "${GREEN}✓ Log files cleared${NC}"
    echo ""
    read -p "Press Enter to continue..."
    cache_menu
}

# Complete Setup
complete_setup() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Complete System Setup${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    read -p "Continue with full setup? (y/n): " confirm
    if [ "$confirm" != "y" ]; then
        main_menu
        return
    fi
    
    echo ""
    echo -e "${YELLOW}Step 1: Creating .env file...${NC}"
    create_local_env
    
    echo -e "${YELLOW}Step 2: Database Setup...${NC}"
    import_schema
    
    echo -e "${YELLOW}Step 3: Clearing cache...${NC}"
    clear_all_cache
    
    echo -e "${YELLOW}Step 4: Creating directories...${NC}"
    cd "$ROOT_DIR/Storage"
    mkdir -p logs temp uploads
    [ ! -f logs/ai_requests.json ] && echo "[]" > logs/ai_requests.json
    echo -e "${GREEN}✓ Directories created${NC}"
    
    echo ""
    echo -e "${GREEN}✓ Setup Complete!${NC}"
    echo ""
    read -p "Press Enter to continue..."
    main_menu
}

# Development Tools
dev_tools() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Development Tools${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Run Tests"
    echo -e "${GREEN}[2]${NC} Code Statistics"
    echo -e "${GREEN}[3]${NC} Check Permissions"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) run_tests ;;
        2) code_stats ;;
        3) check_permissions ;;
        0) main_menu ;;
        *) dev_tools ;;
    esac
}

run_tests() {
    echo ""
    cd "$ROOT_DIR"
    if [ -f test_framework.php ]; then
        php test_framework.php
    else
        echo -e "${RED}✗ Test file not found${NC}"
    fi
    echo ""
    read -p "Press Enter to continue..."
    dev_tools
}

code_stats() {
    echo ""
    echo -e "${BLUE}Code Statistics${NC}"
    echo ""
    cd "$ROOT_DIR"
    echo "  PHP Files: $(find . -name "*.php" | wc -l)"
    echo "  View Files (.ct): $(find . -name "*.ct" | wc -l)"
    echo "  JSON Files: $(find . -name "*.json" | wc -l)"
    echo ""
    read -p "Press Enter to continue..."
    dev_tools
}

check_permissions() {
    echo ""
    echo -e "${YELLOW}Checking permissions...${NC}"
    cd "$ROOT_DIR"
    chmod -R 755 Storage
    chmod -R 755 Backups
    echo -e "${GREEN}✓ Permissions set${NC}"
    echo ""
    read -p "Press Enter to continue..."
    dev_tools
}

# Backup & Restore
backup_menu() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   Backup & Restore${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${GREEN}[1]${NC} Backup Database"
    echo -e "${GREEN}[2]${NC} Backup Uploads"
    echo -e "${GREEN}[3]${NC} Full System Backup"
    echo -e "${GREEN}[4]${NC} List Backups"
    echo -e "${GREEN}[0]${NC} Back to Main Menu"
    echo ""
    read -p "Select an option: " choice
    
    case $choice in
        1) backup_db ;;
        2) backup_uploads ;;
        3) full_backup ;;
        4) list_backups ;;
        0) main_menu ;;
        *) backup_menu ;;
    esac
}

backup_uploads() {
    echo ""
    BACKUP_DIR="$ROOT_DIR/Backups/uploads"
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/uploads_$(date +%Y%m%d_%H%M%S).tar.gz"
    cd "$ROOT_DIR"
    tar -czf "$BACKUP_FILE" Storage/uploads 2>/dev/null
    echo -e "${GREEN}✓ Uploads backed up to: $BACKUP_FILE${NC}"
    echo ""
    read -p "Press Enter to continue..."
    backup_menu
}

full_backup() {
    backup_db
    backup_uploads
    echo ""
    echo -e "${GREEN}✓ Full backup complete!${NC}"
    echo ""
    read -p "Press Enter to continue..."
    backup_menu
}

list_backups() {
    echo ""
    echo -e "${BLUE}Available Backups${NC}"
    echo ""
    cd "$ROOT_DIR/Backups"
    echo -e "${YELLOW}Database Backups:${NC}"
    ls -lh db/*.sql 2>/dev/null || echo "  None"
    echo ""
    echo -e "${YELLOW}Upload Backups:${NC}"
    ls -lh uploads/*.tar.gz 2>/dev/null || echo "  None"
    echo ""
    read -p "Press Enter to continue..."
    backup_menu
}

# System Information
system_info() {
    clear
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   System Information${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Framework:${NC} CyberTirah v2.0"
    echo -e "${YELLOW}OS:${NC} $(uname -s)"
    echo ""
    echo -e "${YELLOW}PHP Version:${NC}"
    php -v | head -n 1
    echo ""
    echo -e "${YELLOW}MySQL Version:${NC}"
    mysql --version
    echo ""
    echo -e "${YELLOW}Disk Space:${NC}"
    df -h "$ROOT_DIR" | tail -n 1
    echo ""
    read -p "Press Enter to continue..."
    main_menu
}

# Start the script
main_menu

