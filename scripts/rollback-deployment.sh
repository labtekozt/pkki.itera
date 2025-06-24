#!/bin/bash

# PKKI ITERA - Deployment Rollback Script
# Rollback PKKI ITERA deployment to previous state
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
readonly SCRIPT_NAME="PKKI ITERA Deployment Rollback"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Configuration
BACKUP_DIR="/var/backups/pkki-itera"
ROLLBACK_TO=""
AUTO_YES=false
DRY_RUN=false
RESTORE_DATABASE=true
RESTORE_FILES=true
SKIP_SERVICES_RESTART=false

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"
}

success() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"
}

# Show help
show_help() {
    echo -e "${CYAN}${SCRIPT_NAME} v${SCRIPT_VERSION}${NC}"
    echo
    echo "Rollback PKKI ITERA deployment to a previous backup state"
    echo
    echo "USAGE:"
    echo "    ./scripts/rollback-deployment.sh [OPTIONS]"
    echo
    echo "OPTIONS:"
    echo "    -h, --help              Show this help message"
    echo "    -y, --yes               Auto-confirm all prompts"
    echo "    --dry-run               Show what would be done without executing"
    echo "    --backup-dir DIR        Backup directory [default: /var/backups/pkki-itera]"
    echo "    --rollback-to BACKUP    Specific backup to rollback to"
    echo "    --list-backups          List available backups"
    echo "    --no-database           Skip database restore"
    echo "    --no-files              Skip file restore"
    echo "    --skip-services         Skip service restart"
    echo
    echo "EXAMPLES:"
    echo "    # List available backups"
    echo "    ./scripts/rollback-deployment.sh --list-backups"
    echo
    echo "    # Interactive rollback"
    echo "    ./scripts/rollback-deployment.sh"
    echo
    echo "    # Rollback to specific backup"
    echo "    ./scripts/rollback-deployment.sh --rollback-to backup-20240624-120000"
    echo
    echo "    # Dry run to see what would be done"
    echo "    ./scripts/rollback-deployment.sh --dry-run"
    echo
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -y|--yes)
                AUTO_YES=true
                shift
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --backup-dir)
                BACKUP_DIR="$2"
                shift 2
                ;;
            --rollback-to)
                ROLLBACK_TO="$2"
                shift 2
                ;;
            --list-backups)
                list_backups
                exit 0
                ;;
            --no-database)
                RESTORE_DATABASE=false
                shift
                ;;
            --no-files)
                RESTORE_FILES=false
                shift
                ;;
            --skip-services)
                SKIP_SERVICES_RESTART=true
                shift
                ;;
            *)
                error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Execute command with dry run support
execute() {
    local cmd="$1"
    local description="$2"
    
    if [ "$DRY_RUN" = "true" ]; then
        info "[DRY RUN] $description"
    else
        log "$description"
        eval "$cmd"
    fi
}

# Confirm action unless auto-yes is enabled
confirm_action() {
    if [ "$AUTO_YES" = "true" ]; then
        return 0
    fi
    
    echo -e "${YELLOW}$1${NC}"
    read -p "Do you want to continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "❌ Operation cancelled by user"
        exit 1
    fi
}

# List available backups
list_backups() {
    echo -e "${CYAN}📦 Available Backups${NC}"
    echo
    
    if [ ! -d "$BACKUP_DIR" ]; then
        error "Backup directory not found: $BACKUP_DIR"
        exit 1
    fi
    
    local backups=($(ls -1 "$BACKUP_DIR" | grep "backup-" | sort -r))
    
    if [ ${#backups[@]} -eq 0 ]; then
        warn "No backups found in $BACKUP_DIR"
        return 1
    fi
    
    echo -e "${BOLD}Available backups:${NC}"
    for i in "${!backups[@]}"; do
        local backup="${backups[$i]}"
        local backup_path="$BACKUP_DIR/$backup"
        
        # Get backup info
        local backup_date=""
        local backup_size=""
        
        if [[ $backup =~ backup-([0-9]{8})-([0-9]{6}) ]]; then
            local date_part="${BASH_REMATCH[1]}"
            local time_part="${BASH_REMATCH[2]}"
            backup_date="${date_part:0:4}-${date_part:4:2}-${date_part:6:2} ${time_part:0:2}:${time_part:2:2}:${time_part:4:2}"
        fi
        
        # Check what files exist for this backup
        local has_files=""
        local has_db=""
        
        if [ -f "$backup_path-files.tar.gz" ]; then
            backup_size=$(du -h "$backup_path-files.tar.gz" 2>/dev/null | cut -f1 || echo "?")
            has_files="📁 Files ($backup_size)"
        fi
        
        if [ -f "$backup_path-database.sql" ]; then
            local db_size=$(du -h "$backup_path-database.sql" 2>/dev/null | cut -f1 || echo "?")
            has_db="🗄️  Database ($db_size)"
        fi
        
        echo -e "  $((i+1)). ${GREEN}$backup${NC}"
        if [ -n "$backup_date" ]; then
            echo -e "     📅 Date: $backup_date"
        fi
        if [ -n "$has_files" ]; then
            echo -e "     $has_files"
        fi
        if [ -n "$has_db" ]; then
            echo -e "     $has_db"
        fi
        echo
    done
}

# Select backup interactively
select_backup() {
    if [ -n "$ROLLBACK_TO" ]; then
        if [ -d "$BACKUP_DIR" ] && ls "$BACKUP_DIR" | grep -q "$ROLLBACK_TO"; then
            return 0
        else
            error "Specified backup not found: $ROLLBACK_TO"
            exit 1
        fi
    fi
    
    echo -e "${BOLD}🔄 Select Backup for Rollback${NC}"
    echo
    
    local backups=($(ls -1 "$BACKUP_DIR" | grep "backup-" | sort -r))
    
    if [ ${#backups[@]} -eq 0 ]; then
        error "No backups found in $BACKUP_DIR"
        error "Create a backup first using the deployment script"
        exit 1
    fi
    
    list_backups
    
    while true; do
        read -p "Select backup number (1-${#backups[@]}): " choice
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le ${#backups[@]} ]; then
            ROLLBACK_TO="${backups[$((choice-1))]}"
            break
        else
            error "Invalid selection. Please choose a number between 1 and ${#backups[@]}"
        fi
    done
    
    success "Selected backup: $ROLLBACK_TO"
}

# Check prerequisites
check_prerequisites() {
    log "🔍 Checking rollback prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        error "Not in Laravel project root directory"
        exit 1
    fi
    
    # Check if backup directory exists
    if [ ! -d "$BACKUP_DIR" ]; then
        error "Backup directory not found: $BACKUP_DIR"
        error "No backups available for rollback"
        exit 1
    fi
    
    # Check if running as appropriate user
    if [ "$EUID" -ne 0 ] && [ "$(whoami)" != "www-data" ]; then
        warn "Running as $(whoami). You may need root privileges for file operations."
    fi
    
    success "Prerequisites check passed"
}

# Create current state backup before rollback
create_pre_rollback_backup() {
    log "💾 Creating pre-rollback backup..."
    
    local backup_name="pre-rollback-$(date +%Y%m%d-%H%M%S)"
    local backup_path="$BACKUP_DIR/$backup_name"
    
    execute "mkdir -p $BACKUP_DIR" "Creating backup directory"
    
    # Backup current files
    if [ "$RESTORE_FILES" = "true" ]; then
        execute "tar -czf $backup_path-files.tar.gz --exclude='vendor' --exclude='node_modules' --exclude='.git' -C $(dirname $PROJECT_ROOT) $(basename $PROJECT_ROOT)" "Backing up current project files"
    fi
    
    # Backup current database
    if [ "$RESTORE_DATABASE" = "true" ]; then
        local env_file="$PROJECT_ROOT/.env"
        if [ -f "$env_file" ]; then
            local db_host=$(grep "^DB_HOST=" "$env_file" | cut -d'=' -f2)
            local db_port=$(grep "^DB_PORT=" "$env_file" | cut -d'=' -f2)
            local db_name=$(grep "^DB_DATABASE=" "$env_file" | cut -d'=' -f2)
            local db_user=$(grep "^DB_USERNAME=" "$env_file" | cut -d'=' -f2)
            local db_pass=$(grep "^DB_PASSWORD=" "$env_file" | cut -d'=' -f2)
            
            if [ -n "$db_host" ] && [ -n "$db_name" ]; then
                if command -v pg_dump &> /dev/null; then
                    execute "PGPASSWORD='$db_pass' pg_dump -h $db_host -p $db_port -U $db_user $db_name > $backup_path-database.sql" "Backing up current database"
                else
                    warn "pg_dump not available, skipping database backup"
                fi
            fi
        fi
    fi
    
    success "Pre-rollback backup created: $backup_name"
}

# Stop services before rollback
stop_services() {
    if [ "$SKIP_SERVICES_RESTART" = "true" ]; then
        return 0
    fi
    
    log "🛑 Stopping services for rollback..."
    
    # Stop web server
    if systemctl is-active --quiet nginx; then
        execute "systemctl stop nginx" "Stopping Nginx"
    fi
    
    # Stop PHP-FPM
    local php_version=$(php -v 2>/dev/null | head -n1 | cut -d' ' -f2 | cut -d'.' -f1-2 || echo "")
    if [ -n "$php_version" ] && systemctl is-active --quiet "php${php_version}-fpm"; then
        execute "systemctl stop php${php_version}-fpm" "Stopping PHP-FPM"
    fi
    
    success "Services stopped"
}

# Restore files from backup
restore_files() {
    if [ "$RESTORE_FILES" = "false" ]; then
        info "Skipping file restore"
        return 0
    fi
    
    log "📁 Restoring files from backup..."
    
    local backup_path="$BACKUP_DIR/$ROLLBACK_TO"
    local files_backup="$backup_path-files.tar.gz"
    
    if [ ! -f "$files_backup" ]; then
        error "Files backup not found: $files_backup"
        return 1
    fi
    
    # Create temporary extraction directory
    local temp_dir="/tmp/pkki-rollback-$$"
    execute "mkdir -p $temp_dir" "Creating temporary directory"
    
    # Extract backup
    execute "tar -xzf $files_backup -C $temp_dir" "Extracting files backup"
    
    # Move current installation (as additional backup)
    local current_backup_dir="$PROJECT_ROOT.rollback-backup-$(date +%Y%m%d-%H%M%S)"
    execute "mv $PROJECT_ROOT $current_backup_dir" "Moving current installation to backup"
    
    # Restore from backup
    execute "mv $temp_dir/$(basename $PROJECT_ROOT) $PROJECT_ROOT" "Restoring files from backup"
    
    # Cleanup
    execute "rm -rf $temp_dir" "Cleaning up temporary files"
    
    success "Files restored from backup"
    info "Previous installation backed up to: $current_backup_dir"
}

# Restore database from backup
restore_database() {
    if [ "$RESTORE_DATABASE" = "false" ]; then
        info "Skipping database restore"
        return 0
    fi
    
    log "🗄️ Restoring database from backup..."
    
    local backup_path="$BACKUP_DIR/$ROLLBACK_TO"
    local db_backup="$backup_path-database.sql"
    
    if [ ! -f "$db_backup" ]; then
        warn "Database backup not found: $db_backup"
        return 0
    fi
    
    # Get database credentials
    local env_file="$PROJECT_ROOT/.env"
    if [ ! -f "$env_file" ]; then
        error ".env file not found after file restore"
        return 1
    fi
    
    local db_host=$(grep "^DB_HOST=" "$env_file" | cut -d'=' -f2)
    local db_port=$(grep "^DB_PORT=" "$env_file" | cut -d'=' -f2 || echo "5432")
    local db_name=$(grep "^DB_DATABASE=" "$env_file" | cut -d'=' -f2)
    local db_user=$(grep "^DB_USERNAME=" "$env_file" | cut -d'=' -f2)
    local db_pass=$(grep "^DB_PASSWORD=" "$env_file" | cut -d'=' -f2)
    
    if [ -z "$db_host" ] || [ -z "$db_name" ]; then
        error "Database configuration not found in .env file"
        return 1
    fi
    
    # Confirm database restore
    warn "⚠️  Database restore will OVERWRITE current data!"
    confirm_action "Restore database from backup?"
    
    # Restore database
    if command -v psql &> /dev/null; then
        execute "PGPASSWORD='$db_pass' psql -h $db_host -p $db_port -U $db_user -d $db_name < $db_backup" "Restoring database from backup"
    else
        error "psql not available, cannot restore database"
        return 1
    fi
    
    success "Database restored from backup"
}

# Start services after rollback
start_services() {
    if [ "$SKIP_SERVICES_RESTART" = "true" ]; then
        return 0
    fi
    
    log "▶️ Starting services after rollback..."
    
    # Start PHP-FPM
    local php_version=$(php -v 2>/dev/null | head -n1 | cut -d' ' -f2 | cut -d'.' -f1-2 || echo "")
    if [ -n "$php_version" ]; then
        execute "systemctl start php${php_version}-fpm" "Starting PHP-FPM"
    fi
    
    # Start web server
    execute "systemctl start nginx" "Starting Nginx"
    
    success "Services started"
}

# Post-rollback tasks
post_rollback_tasks() {
    log "🔧 Running post-rollback tasks..."
    
    cd "$PROJECT_ROOT"
    
    # Clear caches
    execute "php artisan config:clear" "Clearing configuration cache"
    execute "php artisan cache:clear" "Clearing application cache"
    execute "php artisan route:clear" "Clearing route cache"
    execute "php artisan view:clear" "Clearing view cache"
    
    # Fix permissions
    execute "chown -R www-data:www-data $PROJECT_ROOT" "Fixing file ownership"
    execute "chmod -R 775 $PROJECT_ROOT/storage" "Fixing storage permissions"
    execute "chmod -R 775 $PROJECT_ROOT/bootstrap/cache" "Fixing cache permissions"
    
    # Re-cache for production if needed
    local app_env=$(grep "^APP_ENV=" "$PROJECT_ROOT/.env" | cut -d'=' -f2 || echo "production")
    if [ "$app_env" = "production" ]; then
        execute "php artisan config:cache" "Caching configuration"
        execute "php artisan route:cache" "Caching routes"
        execute "php artisan view:cache" "Caching views"
    fi
    
    success "Post-rollback tasks completed"
}

# Run health check after rollback
run_health_check() {
    log "🏥 Running health check after rollback..."
    
    cd "$PROJECT_ROOT"
    
    # Check Laravel application
    if php artisan --version >/dev/null 2>&1; then
        success "Laravel application is responding"
    else
        error "Laravel application is not responding properly"
        return 1
    fi
    
    # Check database connection
    if php artisan migrate:status >/dev/null 2>&1; then
        success "Database connection is working"
    else
        error "Database connection failed"
        return 1
    fi
    
    # Check if web server is responding
    if systemctl is-active --quiet nginx; then
        success "Web server is running"
    else
        warn "Web server is not running"
    fi
    
    success "Health check completed"
}

# Show rollback summary
show_summary() {
    echo
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                       🔄 ROLLBACK SUMMARY                           ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${GREEN}✅ Rollback completed successfully!${NC}"
    echo
    echo -e "${BOLD}Rolled back to:${NC} $ROLLBACK_TO"
    echo -e "${BOLD}Backup location:${NC} $BACKUP_DIR"
    echo -e "${BOLD}Project location:${NC} $PROJECT_ROOT"
    echo
    echo -e "${YELLOW}📝 Next Steps:${NC}"
    echo "1. Test the application thoroughly"
    echo "2. Verify all functionality is working"
    echo "3. Check logs for any issues"
    echo "4. Monitor application performance"
    echo
    echo -e "${BLUE}🔍 Check status: ./scripts/check-deployment-status.sh${NC}"
    echo
}

# Main rollback function
main() {
    # Parse arguments
    parse_arguments "$@"
    
    # Show script header
    echo -e "${CYAN}${SCRIPT_NAME} v${SCRIPT_VERSION}${NC}"
    echo -e "${BLUE}Rollback PKKI ITERA deployment to previous state${NC}"
    echo
    
    if [ "$DRY_RUN" = "true" ]; then
        info "🔍 DRY RUN MODE - No changes will be made"
        echo
    fi
    
    # Check prerequisites
    check_prerequisites
    
    # Select backup to rollback to
    select_backup
    
    # Show rollback plan
    echo -e "${BOLD}📋 Rollback Plan${NC}"
    echo -e "  📦 Backup: $ROLLBACK_TO"
    echo -e "  📁 Files: $([ "$RESTORE_FILES" = "true" ] && echo "Yes" || echo "No")"
    echo -e "  🗄️  Database: $([ "$RESTORE_DATABASE" = "true" ] && echo "Yes" || echo "No")"
    echo -e "  🔄 Services: $([ "$SKIP_SERVICES_RESTART" = "true" ] && echo "Skip restart" || echo "Restart")"
    echo
    
    # Final confirmation
    warn "⚠️  This will rollback your application to a previous state!"
    warn "   Current changes will be lost unless backed up!"
    confirm_action "Continue with rollback?"
    
    # Execute rollback
    log "🚀 Starting rollback process..."
    
    create_pre_rollback_backup
    stop_services
    restore_files
    restore_database
    start_services
    post_rollback_tasks
    run_health_check
    
    # Show summary
    show_summary
    
    success "🎉 Rollback completed successfully!"
}

# Run main function with all arguments
main "$@"
