#!/bin/bash

# PKKI ITERA - Deployment Status Checker
# Check the status of PKKI ITERA deployment
# Version: 1.0.0

set -e

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Script information
readonly SCRIPT_VERSION="1.0.0"
readonly SCRIPT_NAME="PKKI ITERA Deployment Status"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Check functions
check_service() {
    local service_name="$1"
    local display_name="$2"
    
    if systemctl is-active --quiet "$service_name"; then
        echo -e "  ✅ $display_name: ${GREEN}Running${NC}"
        return 0
    else
        echo -e "  ❌ $display_name: ${RED}Not Running${NC}"
        return 1
    fi
}

check_file() {
    local file_path="$1"
    local display_name="$2"
    
    if [ -f "$file_path" ]; then
        echo -e "  ✅ $display_name: ${GREEN}Found${NC}"
        return 0
    else
        echo -e "  ❌ $display_name: ${RED}Missing${NC}"
        return 1
    fi
}

check_directory() {
    local dir_path="$1"
    local display_name="$2"
    
    if [ -d "$dir_path" ]; then
        echo -e "  ✅ $display_name: ${GREEN}Found${NC}"
        return 0
    else
        echo -e "  ❌ $display_name: ${RED}Missing${NC}"
        return 1
    fi
}

check_url() {
    local url="$1"
    local display_name="$2"
    
    local status_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
    
    if [ "$status_code" = "200" ]; then
        echo -e "  ✅ $display_name: ${GREEN}Responding (HTTP $status_code)${NC}"
        return 0
    else
        echo -e "  ❌ $display_name: ${RED}Not Responding (HTTP $status_code)${NC}"
        return 1
    fi
}

# Main status check
main() {
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                   🔍 DEPLOYMENT STATUS CHECK                        ║${NC}"
    echo -e "${CYAN}║                        $SCRIPT_NAME v$SCRIPT_VERSION                         ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    
    local issues=0
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        echo -e "${RED}❌ Not in Laravel project directory${NC}"
        echo -e "Expected artisan file at: $PROJECT_ROOT/artisan"
        exit 1
    fi
    
    echo -e "${BOLD}📋 Project Information${NC}"
    if [ -f "$PROJECT_ROOT/.env" ]; then
        local app_name=$(grep "^APP_NAME=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 | tr -d '"' || echo "Unknown")
        local app_env=$(grep "^APP_ENV=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 || echo "Unknown")
        local app_url=$(grep "^APP_URL=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 || echo "Unknown")
        
        echo -e "  📱 Application: $app_name"
        echo -e "  🏷️  Environment: $app_env"
        echo -e "  🌐 URL: $app_url"
    else
        echo -e "  ❌ .env file not found"
        ((issues++))
    fi
    echo
    
    echo -e "${BOLD}🗂️ File System${NC}"
    check_file "$PROJECT_ROOT/.env" ".env configuration" || ((issues++))
    check_file "$PROJECT_ROOT/composer.json" "Composer configuration" || ((issues++))
    check_file "$PROJECT_ROOT/package.json" "NPM configuration" || ((issues++))
    check_directory "$PROJECT_ROOT/vendor" "Composer dependencies" || ((issues++))
    check_directory "$PROJECT_ROOT/node_modules" "NPM dependencies" || ((issues++))
    check_directory "$PROJECT_ROOT/public/build" "Built assets" || ((issues++))
    echo
    
    echo -e "${BOLD}🔒 Permissions${NC}"
    if [ -w "$PROJECT_ROOT/storage" ]; then
        echo -e "  ✅ Storage directory: ${GREEN}Writable${NC}"
    else
        echo -e "  ❌ Storage directory: ${RED}Not Writable${NC}"
        ((issues++))
    fi
    
    if [ -w "$PROJECT_ROOT/bootstrap/cache" ]; then
        echo -e "  ✅ Bootstrap cache: ${GREEN}Writable${NC}"
    else
        echo -e "  ❌ Bootstrap cache: ${RED}Not Writable${NC}"
        ((issues++))
    fi
    echo
    
    echo -e "${BOLD}⚙️ System Services${NC}"
    check_service "nginx" "Nginx Web Server" || ((issues++))
    
    # Detect PHP version
    local php_version=$(php -v 2>/dev/null | head -n1 | cut -d' ' -f2 | cut -d'.' -f1-2 || echo "unknown")
    if [ "$php_version" != "unknown" ]; then
        check_service "php${php_version}-fpm" "PHP-FPM" || ((issues++))
    else
        echo -e "  ❌ PHP: ${RED}Not Found${NC}"
        ((issues++))
    fi
    
    check_service "redis-server" "Redis Server" || ((issues++))
    echo
    
    echo -e "${BOLD}🗄️ Database${NC}"
    cd "$PROJECT_ROOT"
    
    if php artisan migrate:status >/dev/null 2>&1; then
        echo -e "  ✅ Database connection: ${GREEN}Working${NC}"
        
        local pending_migrations=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")
        if [ "$pending_migrations" -gt 0 ]; then
            echo -e "  ⚠️  Pending migrations: ${YELLOW}$pending_migrations${NC}"
        else
            echo -e "  ✅ Migrations: ${GREEN}Up to date${NC}"
        fi
    else
        echo -e "  ❌ Database connection: ${RED}Failed${NC}"
        ((issues++))
    fi
    echo
    
    echo -e "${BOLD}🚀 Application Status${NC}"
    
    # Check if application is responding
    local app_url=$(grep "^APP_URL=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 || echo "")
    if [ -n "$app_url" ] && [ "$app_url" != "http://localhost" ]; then
        check_url "$app_url" "Application URL" || ((issues++))
        check_url "$app_url/admin" "Admin Panel" || ((issues++))
    else
        echo -e "  ℹ️  Application URL: ${BLUE}Not configured for remote access${NC}"
    fi
    
    # Check Laravel caches
    if [ -f "$PROJECT_ROOT/bootstrap/cache/config.php" ]; then
        echo -e "  ✅ Configuration cache: ${GREEN}Cached${NC}"
    else
        echo -e "  ⚠️  Configuration cache: ${YELLOW}Not cached${NC}"
    fi
    
    if [ -f "$PROJECT_ROOT/bootstrap/cache/routes-v7.php" ]; then
        echo -e "  ✅ Route cache: ${GREEN}Cached${NC}"
    else
        echo -e "  ⚠️  Route cache: ${YELLOW}Not cached${NC}"
    fi
    echo
    
    echo -e "${BOLD}🔒 Security${NC}"
    
    # Check APP_KEY
    local app_key=$(grep "^APP_KEY=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 || echo "")
    if [ -n "$app_key" ] && [ "$app_key" != "" ]; then
        echo -e "  ✅ Application key: ${GREEN}Set${NC}"
    else
        echo -e "  ❌ Application key: ${RED}Missing${NC}"
        ((issues++))
    fi
    
    # Check APP_DEBUG
    local app_debug=$(grep "^APP_DEBUG=" "$PROJECT_ROOT/.env" 2>/dev/null | cut -d'=' -f2 || echo "true")
    if [ "$app_debug" = "false" ]; then
        echo -e "  ✅ Debug mode: ${GREEN}Disabled${NC}"
    else
        echo -e "  ⚠️  Debug mode: ${YELLOW}Enabled${NC}"
    fi
    
    # Check if HTTPS is configured
    if [ -n "$app_url" ] && [[ $app_url == https://* ]]; then
        echo -e "  ✅ HTTPS: ${GREEN}Configured${NC}"
    else
        echo -e "  ⚠️  HTTPS: ${YELLOW}Not configured${NC}"
    fi
    echo
    
    echo -e "${BOLD}📊 Log Status${NC}"
    
    # Check log file size
    local log_file="$PROJECT_ROOT/storage/logs/laravel.log"
    if [ -f "$log_file" ]; then
        local log_size=$(du -h "$log_file" | cut -f1)
        echo -e "  📄 Laravel log size: $log_size"
        
        # Check for recent errors
        local recent_errors=$(tail -n 100 "$log_file" | grep -c "ERROR" || echo "0")
        if [ "$recent_errors" -gt 0 ]; then
            echo -e "  ⚠️  Recent errors: ${YELLOW}$recent_errors${NC}"
        else
            echo -e "  ✅ Recent errors: ${GREEN}None${NC}"
        fi
    else
        echo -e "  ℹ️  Laravel log: ${BLUE}No log file yet${NC}"
    fi
    echo
    
    # Summary
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                           📋 SUMMARY                               ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    
    if [ $issues -eq 0 ]; then
        echo -e "${GREEN}🎉 All systems are operational!${NC}"
        echo -e "${GREEN}No critical issues detected.${NC}"
    elif [ $issues -eq 1 ]; then
        echo -e "${YELLOW}⚠️  1 issue detected.${NC}"
        echo -e "${YELLOW}Please review the status above and address any problems.${NC}"
    else
        echo -e "${RED}❌ $issues issues detected.${NC}"
        echo -e "${RED}Please review the status above and address the problems.${NC}"
    fi
    echo
    
    # Recommendations
    if [ $issues -gt 0 ]; then
        echo -e "${BOLD}🔧 Recommendations:${NC}"
        echo "• Check system service status: systemctl status [service-name]"
        echo "• Review application logs: tail -f $PROJECT_ROOT/storage/logs/laravel.log"
        echo "• Verify file permissions: ls -la $PROJECT_ROOT/storage"
        echo "• Test database connection: php artisan migrate:status"
        echo "• Run application update: ./scripts/quick-deploy.sh update"
        echo
    fi
    
    echo -e "${BLUE}For detailed troubleshooting, check: ./scripts/README.md${NC}"
    
    exit $issues
}

# Run main function
main "$@"
