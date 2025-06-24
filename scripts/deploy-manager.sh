#!/bin/bash

# PKKI ITERA - Deployment Manager
# Central deployment management tool for PKKI ITERA
# Version: 1.0.0

set -e

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Script information
readonly SCRIPT_VERSION="1.0.0"
readonly SCRIPT_NAME="PKKI ITERA Deployment Manager"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Show main menu
show_main_menu() {
    clear
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                  🚀 PKKI ITERA DEPLOYMENT MANAGER                   ║${NC}"
    echo -e "${CYAN}║                                                                      ║${NC}"
    echo -e "${CYAN}║                    Central Deployment Control                       ║${NC}"
    echo -e "${CYAN}║                         Version $SCRIPT_VERSION                                   ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${BOLD}📋 Available Actions:${NC}"
    echo
    echo -e "${GREEN}🔧 DEPLOYMENT OPTIONS${NC}"
    echo "  1) 🧙  Setup Wizard           - Interactive Supabase configuration"
    echo "  2) 🏠  Local Development      - Setup local development environment"
    echo "  3) 🧪  Staging Deployment     - Deploy to staging server"
    echo "  4) 🏭  Production Deployment  - Deploy to production server"
    echo "  5) 🔄  Update Deployment      - Update existing deployment"
    echo
    echo -e "${BLUE}⚙️  MAINTENANCE OPTIONS${NC}"
    echo "  6) 🌐  Nginx Only Setup       - Configure only web server"
    echo "  7) 🔒  SSL Only Setup         - Configure only SSL certificates"
    echo "  8) 🗄️   Database Migration    - Run database migrations only"
    echo "  9) 🧹  Cleanup               - Clean caches and temporary files"
    echo
    echo -e "${YELLOW}📊 MONITORING OPTIONS${NC}"
    echo " 10) 🔍  Status Check          - Check deployment status"
    echo " 11) 📦  List Backups          - Show available backups"
    echo " 12) 🔄  Rollback              - Rollback to previous version"
    echo " 13) 📜  View Logs             - Show application logs"
    echo
    echo -e "${PURPLE}🛠️  TOOLS${NC}"
    echo " 14) 🔧  Generate Commands     - Create deployment commands"
    echo " 15) 📖  Documentation        - View deployment guide"
    echo " 16) ❓  Help                  - Show detailed help"
    echo
    echo -e "${RED} 0) 🚪  Exit${NC}"
    echo
}

# Get user choice
get_user_choice() {
    while true; do
        read -p "Select an option (0-16): " choice
        case $choice in
            [0-9]|1[0-6])
                return $choice
                ;;
            *)
                echo -e "${RED}Invalid choice. Please select a number between 0 and 16.${NC}"
                ;;
        esac
    done
}

# Setup wizard
run_setup_wizard() {
    echo -e "${CYAN}🧙 Starting Supabase Configuration Wizard...${NC}"
    echo
    "$SCRIPT_DIR/supabase-config-wizard.sh"
}

# Local development setup
setup_local_development() {
    echo -e "${GREEN}🏠 Setting up local development environment...${NC}"
    echo
    "$SCRIPT_DIR/quick-deploy.sh" local
}

# Staging deployment
deploy_staging() {
    echo -e "${YELLOW}🧪 Staging Deployment${NC}"
    echo
    echo "Enter staging server details:"
    read -p "Domain (e.g., staging.pkki.itera.ac.id): " domain
    read -p "SSL Email: " email
    
    if [ -n "$domain" ] && [ -n "$email" ]; then
        "$SCRIPT_DIR/quick-deploy.sh" staging --domain "$domain" --email "$email"
    else
        echo -e "${RED}Domain and email are required for staging deployment.${NC}"
        read -p "Press Enter to continue..."
    fi
}

# Production deployment
deploy_production() {
    echo -e "${RED}🏭 Production Deployment${NC}"
    echo
    echo -e "${YELLOW}⚠️  This will deploy to PRODUCTION environment!${NC}"
    echo "Make sure you have:"
    echo "• Configured Supabase credentials"
    echo "• Verified DNS settings"
    echo "• Backed up existing data"
    echo
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        return 0
    fi
    
    echo "Enter production server details:"
    read -p "Domain (e.g., pkki.itera.ac.id): " domain
    read -p "SSL Email: " email
    
    if [ -n "$domain" ] && [ -n "$email" ]; then
        "$SCRIPT_DIR/quick-deploy.sh" production --domain "$domain" --email "$email"
    else
        echo -e "${RED}Domain and email are required for production deployment.${NC}"
        read -p "Press Enter to continue..."
    fi
}

# Update deployment
update_deployment() {
    echo -e "${CYAN}🔄 Updating existing deployment...${NC}"
    echo
    "$SCRIPT_DIR/quick-deploy.sh" update
}

# Nginx only setup
setup_nginx_only() {
    echo -e "${BLUE}🌐 Nginx Only Setup${NC}"
    echo
    read -p "Domain name: " domain
    
    if [ -n "$domain" ]; then
        "$SCRIPT_DIR/quick-deploy.sh" nginx-only --domain "$domain"
    else
        echo -e "${RED}Domain name is required.${NC}"
        read -p "Press Enter to continue..."
    fi
}

# SSL only setup
setup_ssl_only() {
    echo -e "${GREEN}🔒 SSL Only Setup${NC}"
    echo
    read -p "Domain name: " domain
    read -p "SSL Email: " email
    
    if [ -n "$domain" ] && [ -n "$email" ]; then
        "$SCRIPT_DIR/quick-deploy.sh" ssl-only --domain "$domain" --email "$email"
    else
        echo -e "${RED}Domain and email are required.${NC}"
        read -p "Press Enter to continue..."
    fi
}

# Database migration
run_database_migration() {
    echo -e "${BLUE}🗄️ Running Database Migrations...${NC}"
    echo
    cd "$PROJECT_ROOT"
    
    echo "Available migration commands:"
    echo "1) Run pending migrations"
    echo "2) Rollback last migration"
    echo "3) Reset and re-run all migrations"
    echo "4) Show migration status"
    echo
    
    read -p "Select option (1-4): " migration_choice
    
    case $migration_choice in
        1)
            php artisan migrate
            ;;
        2)
            php artisan migrate:rollback
            ;;
        3)
            echo -e "${YELLOW}⚠️  This will reset ALL data!${NC}"
            read -p "Continue? (y/N): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                php artisan migrate:reset
                php artisan migrate
            fi
            ;;
        4)
            php artisan migrate:status
            ;;
        *)
            echo -e "${RED}Invalid choice.${NC}"
            ;;
    esac
    
    read -p "Press Enter to continue..."
}

# Cleanup
run_cleanup() {
    echo -e "${YELLOW}🧹 Running Cleanup...${NC}"
    echo
    "$SCRIPT_DIR/deploy-supabase-setup.sh" --cleanup-only
    read -p "Press Enter to continue..."
}

# Status check
run_status_check() {
    echo -e "${CYAN}🔍 Checking Deployment Status...${NC}"
    echo
    "$SCRIPT_DIR/check-deployment-status.sh"
    read -p "Press Enter to continue..."
}

# List backups
list_backups() {
    echo -e "${BLUE}📦 Listing Available Backups...${NC}"
    echo
    "$SCRIPT_DIR/rollback-deployment.sh" --list-backups
    read -p "Press Enter to continue..."
}

# Rollback deployment
run_rollback() {
    echo -e "${RED}🔄 Deployment Rollback${NC}"
    echo
    "$SCRIPT_DIR/rollback-deployment.sh"
}

# View logs
view_logs() {
    echo -e "${PURPLE}📜 Application Logs${NC}"
    echo
    echo "Available log options:"
    echo "1) Laravel application log"
    echo "2) Nginx access log"
    echo "3) Nginx error log"
    echo "4) PHP-FPM log"
    echo "5) System log"
    echo
    
    read -p "Select log to view (1-5): " log_choice
    
    case $log_choice in
        1)
            if [ -f "$PROJECT_ROOT/storage/logs/laravel.log" ]; then
                tail -f "$PROJECT_ROOT/storage/logs/laravel.log"
            else
                echo "Laravel log not found."
            fi
            ;;
        2)
            if [ -f "/var/log/nginx/access.log" ]; then
                tail -f /var/log/nginx/access.log
            else
                echo "Nginx access log not found."
            fi
            ;;
        3)
            if [ -f "/var/log/nginx/error.log" ]; then
                tail -f /var/log/nginx/error.log
            else
                echo "Nginx error log not found."
            fi
            ;;
        4)
            local php_version=$(php -v 2>/dev/null | head -n1 | cut -d' ' -f2 | cut -d'.' -f1-2 || echo "8.3")
            if [ -f "/var/log/php${php_version}-fpm.log" ]; then
                tail -f "/var/log/php${php_version}-fpm.log"
            else
                echo "PHP-FPM log not found."
            fi
            ;;
        5)
            tail -f /var/log/syslog
            ;;
        *)
            echo -e "${RED}Invalid choice.${NC}"
            ;;
    esac
}

# Generate commands
generate_commands() {
    echo -e "${CYAN}🔧 Generate Deployment Commands${NC}"
    echo
    echo "This will run the configuration wizard to generate deployment commands."
    echo
    "$SCRIPT_DIR/supabase-config-wizard.sh"
}

# Show documentation
show_documentation() {
    echo -e "${BLUE}📖 Deployment Documentation${NC}"
    echo
    if [ -f "$SCRIPT_DIR/README.md" ]; then
        if command -v less &> /dev/null; then
            less "$SCRIPT_DIR/README.md"
        elif command -v more &> /dev/null; then
            more "$SCRIPT_DIR/README.md"
        else
            cat "$SCRIPT_DIR/README.md"
            read -p "Press Enter to continue..."
        fi
    else
        echo "Documentation not found at $SCRIPT_DIR/README.md"
        read -p "Press Enter to continue..."
    fi
}

# Show help
show_help() {
    echo -e "${CYAN}❓ Deployment Manager Help${NC}"
    echo
    echo -e "${BOLD}Available Scripts:${NC}"
    echo
    echo -e "${GREEN}🧙 supabase-config-wizard.sh${NC}"
    echo "   Interactive wizard for Supabase configuration"
    echo "   Usage: ./scripts/supabase-config-wizard.sh"
    echo
    echo -e "${GREEN}🚀 deploy-supabase-setup.sh${NC}"
    echo "   Complete deployment script with full customization"
    echo "   Usage: ./scripts/deploy-supabase-setup.sh [OPTIONS]"
    echo
    echo -e "${GREEN}⚡ quick-deploy.sh${NC}"
    echo "   Pre-configured deployment scenarios"
    echo "   Usage: ./scripts/quick-deploy.sh SCENARIO [OPTIONS]"
    echo
    echo -e "${GREEN}🔍 check-deployment-status.sh${NC}"
    echo "   Check current deployment status and health"
    echo "   Usage: ./scripts/check-deployment-status.sh"
    echo
    echo -e "${GREEN}🔄 rollback-deployment.sh${NC}"
    echo "   Rollback deployment to previous backup"
    echo "   Usage: ./scripts/rollback-deployment.sh [OPTIONS]"
    echo
    echo -e "${BOLD}Quick Commands:${NC}"
    echo
    echo "# Local development setup"
    echo "./scripts/quick-deploy.sh local"
    echo
    echo "# Production deployment with wizard"
    echo "./scripts/supabase-config-wizard.sh"
    echo
    echo "# Check deployment status"
    echo "./scripts/check-deployment-status.sh"
    echo
    echo "# View detailed help for any script"
    echo "./scripts/SCRIPT_NAME.sh --help"
    echo
    read -p "Press Enter to continue..."
}

# Main function
main() {
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        echo -e "${RED}❌ Error: Not in Laravel project directory${NC}"
        echo "Please run this script from the PKKI ITERA project root."
        exit 1
    fi
    
    while true; do
        show_main_menu
        get_user_choice
        choice=$?
        
        case $choice in
            0)
                echo -e "${GREEN}👋 Thank you for using PKKI ITERA Deployment Manager!${NC}"
                exit 0
                ;;
            1)
                run_setup_wizard
                ;;
            2)
                setup_local_development
                ;;
            3)
                deploy_staging
                ;;
            4)
                deploy_production
                ;;
            5)
                update_deployment
                ;;
            6)
                setup_nginx_only
                ;;
            7)
                setup_ssl_only
                ;;
            8)
                run_database_migration
                ;;
            9)
                run_cleanup
                ;;
            10)
                run_status_check
                ;;
            11)
                list_backups
                ;;
            12)
                run_rollback
                ;;
            13)
                view_logs
                ;;
            14)
                generate_commands
                ;;
            15)
                show_documentation
                ;;
            16)
                show_help
                ;;
        esac
    done
}

# Run main function
main "$@"
