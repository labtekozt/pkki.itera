#!/bin/bash

# PKKI ITERA - Simple & Best Practices Deployment Script
# Version: 2.0.0
# Usage: ./scripts/deploy.sh [environment]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENVIRONMENT="${1:-production}"
BACKUP_DIR="$HOME/backups/pkki-itera"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Logging functions
log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"; }
error() { echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"; }

# Show help
show_help() {
    echo -e "${BLUE}PKKI ITERA Deployment Script${NC}"
    echo
    echo "Usage: $0 [environment] [options]"
    echo
    echo "Environments:"
    echo "  production   Deploy to production (default)"
    echo "  staging      Deploy to staging"
    echo "  development  Deploy to development"
    echo
    echo "Options:"
    echo "  --help       Show this help"
    echo "  --no-backup  Skip backup creation"
    echo "  --quick      Skip dependency installation"
    echo
    echo "Examples:"
    echo "  $0                    # Deploy to production"
    echo "  $0 staging            # Deploy to staging"
    echo "  $0 --quick            # Quick production deploy"
}

# Parse arguments
parse_arguments() {
    SKIP_BACKUP=false
    QUICK_DEPLOY=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --help)
                show_help
                exit 0
                ;;
            --no-backup)
                SKIP_BACKUP=true
                shift
                ;;
            --quick)
                QUICK_DEPLOY=true
                shift
                ;;
            production|staging|development)
                ENVIRONMENT="$1"
                shift
                ;;
            *)
                error "Unknown option: $1. Use --help for usage."
                ;;
        esac
    done
}

# Pre-deployment checks
pre_deploy_checks() {
    log "Running pre-deployment checks..."
    
    # Check if we're in the right directory
    [[ -f "$PROJECT_ROOT/artisan" ]] || error "Not a Laravel project directory"
    
    # Check required commands
    command -v php >/dev/null 2>&1 || error "PHP not found"
    command -v composer >/dev/null 2>&1 || error "Composer not found"
    command -v npm >/dev/null 2>&1 || error "Node.js/npm not found"
    
    # Check environment file
    [[ -f "$PROJECT_ROOT/.env" ]] || error ".env file not found"
    
    # Check database connection
    cd "$PROJECT_ROOT"
    php artisan migrate:status >/dev/null 2>&1 || error "Database connection failed"
    
    log "Pre-deployment checks passed"
}

# Create backup
create_backup() {
    if [[ "$SKIP_BACKUP" == "true" ]]; then
        warn "Skipping backup creation"
        return 0
    fi
    
    log "Creating backup..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup application files
    tar -czf "$BACKUP_DIR/app_${TIMESTAMP}.tar.gz" \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='.git' \
        --exclude='storage/logs/*' \
        --exclude='bootstrap/cache/*' \
        -C "$(dirname "$PROJECT_ROOT")" \
        "$(basename "$PROJECT_ROOT")"
    
    # Backup database (if possible)
    if [[ -n "${DB_HOST:-}" ]] && command -v pg_dump >/dev/null 2>&1; then
        pg_dump -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" "${DB_DATABASE}" \
            > "$BACKUP_DIR/database_${TIMESTAMP}.sql" 2>/dev/null || warn "Database backup failed"
    fi
    
    log "Backup created: $BACKUP_DIR/app_${TIMESTAMP}.tar.gz"
}

# Install dependencies
install_dependencies() {
    if [[ "$QUICK_DEPLOY" == "true" ]]; then
        warn "Skipping dependency installation (quick deploy)"
        return 0
    fi
    
    log "Installing dependencies..."
    
    cd "$PROJECT_ROOT"
    
    # Install PHP dependencies
    if [[ "$ENVIRONMENT" == "production" ]]; then
        composer install --optimize-autoloader --no-dev --no-interaction --quiet
    else
        composer install --no-interaction --quiet
    fi
    
    # Install Node dependencies and build assets
    if [[ -f "package.json" ]]; then
        npm i --silent
        
        if [[ "$ENVIRONMENT" == "production" ]]; then
            npm run build --silent
        else
            npm run dev --silent
        fi
    fi
    
    log "Dependencies installed successfully"
}

# Update environment configuration
update_environment() {
    log "Updating environment configuration..."
    
    cd "$PROJECT_ROOT"
    
    # Update environment settings based on deployment environment
    case "$ENVIRONMENT" in
        production)
            sed -i.bak 's/APP_ENV=.*/APP_ENV=production/' .env
            sed -i.bak 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
            ;;
        staging)
            sed -i.bak 's/APP_ENV=.*/APP_ENV=staging/' .env
            sed -i.bak 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
            ;;
        development)
            sed -i.bak 's/APP_ENV=.*/APP_ENV=local/' .env
            sed -i.bak 's/APP_DEBUG=.*/APP_DEBUG=true/' .env
            ;;
    esac
    
    # Remove backup file
    rm -f .env.bak
    
    log "Environment updated to: $ENVIRONMENT"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    cd "$PROJECT_ROOT"
    
    # Run migrations
    if [[ "$ENVIRONMENT" == "production" ]]; then
        php artisan migrate --force --no-interaction
    else
        php artisan migrate --no-interaction
    fi
    
    log "Database migrations completed"
}

# Optimize application
optimize_application() {
    log "Optimizing application..."
    
    cd "$PROJECT_ROOT"
    
    if [[ "$ENVIRONMENT" == "production" ]]; then
        # Production optimizations
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
        
        # Optimize Composer autoloader
        composer dump-autoload --optimize --quiet
        
        # Filament optimizations
        php artisan filament:cache-components 2>/dev/null || true
        php artisan icons:cache 2>/dev/null || true
    else
        # Development - clear caches
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear
    fi
    
    log "Application optimized for $ENVIRONMENT"
}

# Fix permissions
fix_permissions() {
    log "Setting file permissions..."
    
    cd "$PROJECT_ROOT"
    
    # Set basic permissions
    find . -type f -exec chmod 644 {} \; 2>/dev/null || true
    find . -type d -exec chmod 755 {} \; 2>/dev/null || true
    
    # Laravel specific permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod +x artisan
    
    # Web server permissions (if running as web user)
    if [[ "$(whoami)" == "www-data" ]] || [[ -n "${SUDO_USER:-}" ]]; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi
    
    log "File permissions set"
}

# Health check
health_check() {
    log "Running health checks..."
    
    cd "$PROJECT_ROOT"
    
    # Check Laravel installation
    php artisan --version >/dev/null || error "Laravel installation check failed"
    
    # Check database connection
    php artisan migrate:status >/dev/null || error "Database connection check failed"
    
    # Check storage link
    php artisan storage:link >/dev/null 2>&1 || true
    
    # Check if storage is writable
    touch storage/logs/deployment.log 2>/dev/null || warn "Storage directory not writable"
    
    log "Health checks passed"
}

# Restart services (if running as root or with sudo)
restart_services() {
    if [[ $EUID -eq 0 ]] || command -v sudo >/dev/null 2>&1; then
        log "Restarting services..."
        
        # Restart common services
        for service in nginx php8.3-fpm php-fpm apache2; do
            if systemctl is-active --quiet "$service" 2>/dev/null; then
                sudo systemctl reload "$service" 2>/dev/null || true
                log "Restarted $service"
            fi
        done
        
        # Restart queue workers if supervisor is available
        if command -v supervisorctl >/dev/null 2>&1; then
            sudo supervisorctl restart all 2>/dev/null || true
            log "Restarted queue workers"
        fi
    else
        warn "Skipping service restart (no root privileges)"
    fi
}

# Show deployment summary
show_summary() {
    echo
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                     🚀 DEPLOYMENT COMPLETED                         ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${YELLOW}Environment:${NC} $ENVIRONMENT"
    echo -e "${YELLOW}Timestamp:${NC} $(date)"
    echo -e "${YELLOW}Backup:${NC} ${SKIP_BACKUP:-false}"
    echo -e "${YELLOW}Quick Deploy:${NC} ${QUICK_DEPLOY:-false}"
    echo
    echo -e "${BLUE}Next Steps:${NC}"
    echo "• Test the application functionality"
    echo "• Monitor application logs: tail -f storage/logs/laravel.log"
    echo "• Check server logs if needed"
    echo
    if [[ "$ENVIRONMENT" == "production" ]]; then
        echo -e "${RED}Production Reminders:${NC}"
        echo "• Monitor application performance"
        echo "• Check SSL certificate status"
        echo "• Verify backup was created"
        echo "• Test critical user flows"
    fi
    echo
}

# Clean up old backups (keep last 5)
cleanup_old_backups() {
    if [[ -d "$BACKUP_DIR" ]]; then
        log "Cleaning up old backups..."
        ls -t "$BACKUP_DIR"/app_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f
        ls -t "$BACKUP_DIR"/database_*.sql 2>/dev/null | tail -n +6 | xargs rm -f
    fi
}

# Main deployment function
main() {
    echo -e "${BLUE}PKKI ITERA Deployment Script - v2.0.0${NC}"
    echo -e "${BLUE}Environment: $ENVIRONMENT${NC}"
    echo
    
    # Parse command line arguments
    parse_arguments "$@"
    
    # Confirm production deployment
    if [[ "$ENVIRONMENT" == "production" ]] && [[ -t 0 ]]; then
        echo -e "${YELLOW}⚠️  You are about to deploy to PRODUCTION${NC}"
        read -p "Are you sure you want to continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "Deployment cancelled"
        fi
    fi
    
    # Run deployment steps
    pre_deploy_checks
    create_backup
    install_dependencies
    update_environment
    run_migrations
    optimize_application
    fix_permissions
    health_check
    restart_services
    cleanup_old_backups
    
    # Show summary
    show_summary
    
    log "Deployment completed successfully! 🎉"
}

# Handle script interruption
trap 'error "Deployment interrupted"' INT TERM

# Run main function with all arguments
main "$@"
