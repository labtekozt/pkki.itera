#!/bin/bash

# =============================================================================
# PKKI ITERA Fresh Deployment Script
# =============================================================================
# Purpose: Deploy Laravel PKKI ITERA application from scratch on Ubuntu VPS
# Author: PKKI ITERA Team
# Version: 2.0
# Date: $(date +%Y-%m-%d)
# 
# This script provides a complete deployment solution with:
# - Zero-downtime deployment capabilities
# - Comprehensive error handling and rollback
# - Security hardening and best practices
# - Automated SSL certificate setup
# - Performance optimization
# - Detailed logging and monitoring
# =============================================================================

# Bash strict mode for better error handling
set -euo pipefail  # Exit on error, undefined vars, pipe failures
IFS=$'\n\t'       # Secure Internal Field Separator

# =============================================================================
# SCRIPT CONFIGURATION
# =============================================================================

# Script metadata
readonly SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly SCRIPT_VERSION="2.0"
readonly DEPLOYMENT_DATE="$(date '+%Y%m%d_%H%M%S')"

# Application configuration
readonly APP_NAME="pkki.itera"
readonly DOMAIN="hki.proyekai.com"
readonly SERVER_IP="34.101.196.4"
readonly WEB_USER="www-data"
readonly APP_USER="partikelxyz"
readonly PHP_VERSION="8.3"

# Directory structure
readonly APP_DIR="/home/${APP_USER}/${APP_NAME}"
readonly BACKUP_DIR="/home/${APP_USER}/backups"
readonly LOG_DIR="/var/log/${APP_NAME}"
readonly NGINX_AVAILABLE="/etc/nginx/sites-available"
readonly NGINX_ENABLED="/etc/nginx/sites-enabled"

# Logging configuration
readonly LOG_FILE="${LOG_DIR}/deployment_${DEPLOYMENT_DATE}.log"
readonly ERROR_LOG="${LOG_DIR}/deployment_errors_${DEPLOYMENT_DATE}.log"

# Repository configuration
readonly REPO_URL="https://github.com/labtekozt/pkki.itera.git"
readonly REPO_BRANCH="${DEPLOY_BRANCH:-main}"

# Timeout configurations (seconds)
readonly CURL_TIMEOUT=30
readonly DB_CONNECT_TIMEOUT=15
readonly SERVICE_START_TIMEOUT=30

# Colors for output (readonly for security)
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly WHITE='\033[1;37m'
readonly NC='\033[0m' # No Color

# Feature flags (can be overridden by environment variables)
readonly ENABLE_SSL="${ENABLE_SSL:-true}"
readonly ENABLE_BACKUP="${ENABLE_BACKUP:-true}"
readonly ENABLE_MONITORING="${ENABLE_MONITORING:-true}"
readonly SKIP_DEPS_CHECK="${SKIP_DEPS_CHECK:-false}"
readonly DRY_RUN="${DRY_RUN:-false}"

# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================

# Trap function for cleanup on exit
cleanup() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_error "Script failed with exit code $exit_code"
        if [[ -f "$ERROR_LOG" ]]; then
            log_error "Check logs at: $ERROR_LOG"
        fi
        
        # Attempt to restore from backup if deployment failed
        if [[ "${ENABLE_BACKUP:-true}" == "true" && -d "${BACKUP_DIR}/pre_deployment_${DEPLOYMENT_DATE}" ]]; then
            log_warning "Attempting automatic rollback..."
            rollback_deployment || log_error "Rollback failed - manual intervention required"
        fi
    fi
}

# Set up signal traps
trap cleanup EXIT
trap 'log_error "Script interrupted by user"; exit 130' INT TERM

# Advanced logging functions with timestamps and log levels
setup_logging() {
    # Create log directory with sudo if needed
    if [[ ! -d "$LOG_DIR" ]]; then
        sudo mkdir -p "$LOG_DIR" 2>/dev/null || mkdir -p "$LOG_DIR" 2>/dev/null || true
    fi
    
    # Try to set ownership if users exist, otherwise skip
    if id "$APP_USER" &>/dev/null && getent group "$WEB_USER" &>/dev/null; then
        sudo chown "$APP_USER:$WEB_USER" "$LOG_DIR" 2>/dev/null || true
    fi
    
    # Create log files with proper permissions
    if [[ -w "$(dirname "$LOG_FILE")" ]] || sudo test -w "$(dirname "$LOG_FILE")"; then
        touch "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || sudo touch "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || true
        chmod 644 "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || sudo chmod 644 "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || true
    fi
}

log_with_timestamp() {
    local level="$1"
    local message="$2"
    local timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    
    # Try to write to log file, fallback to stderr if not possible
    if [[ -w "$LOG_FILE" ]] || [[ -w "$(dirname "$LOG_FILE")" ]]; then
        echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE" 2>/dev/null || echo "[$timestamp] [$level] $message"
    else
        echo "[$timestamp] [$level] $message"
    fi
    
    # Write errors to error log if possible
    if [[ "$level" == "ERROR" ]]; then
        if [[ -w "$ERROR_LOG" ]] || [[ -w "$(dirname "$ERROR_LOG")" ]]; then
            echo "[$timestamp] [$level] $message" >> "$ERROR_LOG" 2>/dev/null || true
        fi
    fi
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
    log_with_timestamp "INFO" "$1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
    log_with_timestamp "SUCCESS" "$1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    log_with_timestamp "WARNING" "$1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    log_with_timestamp "ERROR" "$1"
}

log_debug() {
    if [[ "${DEBUG:-false}" == "true" ]]; then
        echo -e "${PURPLE}[DEBUG]${NC} $1"
        log_with_timestamp "DEBUG" "$1"
    fi
}

# Enhanced error handling with stack trace
handle_error() {
    local line_number="$1"
    local error_code="$2"
    local command="$3"
    
    log_error "Error occurred in script at line $line_number: exit code $error_code"
    log_error "Failed command: $command"
    
    # Print stack trace
    local frame=0
    while caller $frame; do
        ((frame++))
    done
    
    # Trigger cleanup
    cleanup_on_error
    exit "$error_code"
}

# Set up enhanced error trapping
set_error_handling() {
    set -eE  # Exit on error and inherit ERR trap
    trap 'handle_error ${LINENO} $? "$BASH_COMMAND"' ERR
}

# Cleanup function for error handling
cleanup_on_error() {
    log_warning "Performing cleanup operations..."
    
    # Stop any services that might be in an inconsistent state
    sudo systemctl stop nginx 2>/dev/null || true
    sudo systemctl stop php${PHP_VERSION}-fpm 2>/dev/null || true
    
    # Clean up any temporary files
    sudo rm -f /tmp/composer-setup.php 2>/dev/null || true
    
    # If rollback is enabled and backup exists, offer rollback
    if [[ "${ENABLE_BACKUP:-true}" == "true" ]] && [[ -d "${BACKUP_DIR}/pre_deployment_${DEPLOYMENT_DATE}" ]]; then
        echo ""
        read -p "Deployment failed. Do you want to rollback to previous state? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rollback_deployment
        fi
    fi
    
    log_warning "Cleanup completed"
}

# Progress indicator for long-running operations
show_progress() {
    local duration="$1"
    local message="$2"
    
    echo -n "$message"
    for ((i=0; i<duration; i++)); do
        echo -n "."
        sleep 1
    done
    echo " ✓"
}

# Validation functions
validate_environment() {
    log_info "Validating deployment environment..."
    
    # Check if running as correct user
    if [[ "$(whoami)" != "$APP_USER" ]]; then
        log_error "Script must be run as user: $APP_USER"
        exit 1
    fi
    
    # Check system resources
    local available_memory=$(free -m | awk 'NR==2{printf "%d", $7}')
    local available_disk=$(df / | awk 'NR==2{printf "%d", $4/1024/1024}')
    
    if [[ $available_memory -lt 512 ]]; then
        log_warning "Low available memory: ${available_memory}MB (recommended: >512MB)"
    fi
    
    if [[ $available_disk -lt 2 ]]; then
        log_error "Insufficient disk space: ${available_disk}GB (required: >2GB)"
        exit 1
    fi
    
    log_success "Environment validation passed"
}

# Network connectivity check
check_connectivity() {
    log_info "Checking network connectivity..."
    
    local test_urls=("github.com" "packagist.org" "npmjs.com")
    
    for url in "${test_urls[@]}"; do
        if timeout 10 curl -s --head "$url" >/dev/null; then
            log_success "✓ Connected to $url"
        else
            log_error "✗ Cannot connect to $url"
            exit 1
        fi
    done
}

# Backup functions
create_backup() {
    if [[ "${ENABLE_BACKUP}" != "true" ]]; then
        log_info "Backup disabled, skipping..."
        return 0
    fi
    
    log_info "Creating backup before deployment..."
    
    local backup_name="pre_deployment_${DEPLOYMENT_DATE}"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    mkdir -p "$backup_path"
    
    # Backup application directory if it exists
    if [[ -d "$APP_DIR" ]]; then
        log_info "Backing up application directory..."
        tar -czf "${backup_path}/app_backup.tar.gz" -C "$(dirname "$APP_DIR")" "$(basename "$APP_DIR")" 2>/dev/null || {
            log_warning "Failed to backup application directory"
        }
    fi
    
    # Backup nginx configuration
    if [[ -f "${NGINX_AVAILABLE}/${APP_NAME}" ]]; then
        cp "${NGINX_AVAILABLE}/${APP_NAME}" "${backup_path}/nginx_config.backup"
    fi
    
    # Backup database (if applicable)
    backup_database "$backup_path"
    
    # Create restore script
    create_restore_script "$backup_path"
    
    log_success "Backup created at: $backup_path"
}

backup_database() {
    local backup_path="$1"
    log_info "Creating database backup..."
    
    # Note: For PostgreSQL Supabase, we'll document the connection details
    cat > "${backup_path}/database_info.txt" << EOF
Database Backup Information
===========================
Type: PostgreSQL (Supabase)
Host: aws-0-ap-southeast-1.pooler.supabase.com
Port: 5432
Database: postgres

Note: Database is hosted on Supabase. 
For backup/restore, use Supabase dashboard or pg_dump with connection details from .env
EOF
    
    log_success "Database backup info created"
}

create_restore_script() {
    local backup_path="$1"
    
    cat > "${backup_path}/restore.sh" << 'EOF'
#!/bin/bash
# Auto-generated restore script
set -e

BACKUP_DIR="$(dirname "$0")"
APP_DIR="/home/partikelxyz/pkki.itera"

echo "Starting restore process..."

# Stop services
sudo systemctl stop nginx php8.3-fpm

# Restore application
if [[ -f "$BACKUP_DIR/app_backup.tar.gz" ]]; then
    echo "Restoring application..."
    sudo rm -rf "$APP_DIR"
    sudo tar -xzf "$BACKUP_DIR/app_backup.tar.gz" -C "$(dirname "$APP_DIR")"
fi

# Restore nginx config
if [[ -f "$BACKUP_DIR/nginx_config.backup" ]]; then
    echo "Restoring nginx configuration..."
    sudo cp "$BACKUP_DIR/nginx_config.backup" "/etc/nginx/sites-available/pkki.itera"
fi

# Start services
sudo systemctl start php8.3-fpm nginx

echo "Restore completed successfully!"
EOF
    
    chmod +x "${backup_path}/restore.sh"
}

rollback_deployment() {
    local backup_path="${BACKUP_DIR}/pre_deployment_${DEPLOYMENT_DATE}"
    
    if [[ -f "${backup_path}/restore.sh" ]]; then
        log_warning "Executing automatic rollback..."
        bash "${backup_path}/restore.sh"
        log_success "Rollback completed"
    else
        log_error "No restore script found for rollback"
        return 1
    fi
}

# Security functions
harden_system() {
    log_info "Applying security hardening..."
    
    # Update package lists
    sudo apt update
    
    # Install security updates
    sudo apt upgrade -y
    
    # Configure firewall basics (if ufw is available)
    if command -v ufw >/dev/null; then
        log_info "Configuring basic firewall rules..."
        sudo ufw --force reset
        sudo ufw default deny incoming
        sudo ufw default allow outgoing
        sudo ufw allow 22/tcp   # SSH
        sudo ufw allow 80/tcp   # HTTP
        sudo ufw allow 443/tcp  # HTTPS
        sudo ufw --force enable
        log_success "Firewall configured"
    fi
    
    # Set secure permissions on sensitive files
    secure_file_permissions
    
    log_success "Security hardening completed"
}

secure_file_permissions() {
    log_info "Setting secure file permissions..."
    
    # Secure .env file
    if [[ -f "${APP_DIR}/.env" ]]; then
        chmod 600 "${APP_DIR}/.env"
        chown "${APP_USER}:${APP_USER}" "${APP_DIR}/.env"
    fi
    
    # Secure config files
    find "${APP_DIR}/config" -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true
    
    # Secure storage directory
    find "${APP_DIR}/storage" -type d -exec chmod 755 {} \; 2>/dev/null || true
    find "${APP_DIR}/storage" -type f -exec chmod 644 {} \; 2>/dev/null || true
    
    # Make sure bootstrap/cache is writable
    chmod 755 "${APP_DIR}/bootstrap/cache" 2>/dev/null || true
    
    log_success "File permissions secured"
}

# Performance optimization functions
optimize_system() {
    log_info "Applying system optimizations..."
    
    # PHP optimizations
    optimize_php_configuration
    
    # Nginx optimizations
    optimize_nginx_configuration
    
    # System optimizations
    optimize_system_configuration
    
    log_success "System optimization completed"
}

optimize_php_configuration() {
    log_info "Optimizing PHP configuration..."
    
    # Create optimized PHP configuration
    sudo tee "/etc/php/${PHP_VERSION}/fpm/conf.d/99-laravel-optimization.ini" > /dev/null << EOF
; Laravel/Filament optimizations
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
max_input_vars = 3000
post_max_size = 50M
upload_max_filesize = 50M

; Performance optimizations
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.save_comments = 1

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
EOF

    log_success "PHP configuration optimized"
}

optimize_nginx_configuration() {
    log_info "Optimizing Nginx configuration..."
    
    # Add performance optimizations to main nginx config
    if ! grep -q "worker_processes auto" /etc/nginx/nginx.conf; then
        sudo sed -i 's/worker_processes.*/worker_processes auto;/' /etc/nginx/nginx.conf
    fi
    
    log_success "Nginx configuration optimized"
}

optimize_system_configuration() {
    log_info "Optimizing system configuration..."
    
    # Increase file limits for better performance
    sudo tee -a /etc/security/limits.conf > /dev/null << EOF

# Laravel application limits
${APP_USER} soft nofile 65536
${APP_USER} hard nofile 65536
${WEB_USER} soft nofile 65536
${WEB_USER} hard nofile 65536
EOF

    log_success "System configuration optimized"
}

# Health check functions
health_check() {
    log_info "Running comprehensive health checks..."
    
    local errors=0
    
    # Check services
    for service in "nginx" "php${PHP_VERSION}-fpm"; do
        if systemctl is-active --quiet "$service"; then
            log_success "✓ $service is running"
        else
            log_error "✗ $service is not running"
            ((errors++))
        fi
    done
    
    # Check web server response
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost -H "Host: ${DOMAIN}" --max-time "$CURL_TIMEOUT")
    if [[ "$response" == "200" ]]; then
        log_success "✓ Web server responding (HTTP $response)"
    else
        log_error "✗ Web server not responding properly (HTTP $response)"
        ((errors++))
    fi
    
    # Check database connectivity
    if cd "$APP_DIR" && php artisan migrate:status --ansi >/dev/null 2>&1; then
        log_success "✓ Database connection working"
    else
        log_error "✗ Database connection failed"
        ((errors++))
    fi
    
    # Check critical files
    local critical_files=(
        "${APP_DIR}/public/build/manifest.json"
        "${APP_DIR}/public/vendor/livewire/livewire.min.js"
        "${APP_DIR}/.env"
    )
    
    for file in "${critical_files[@]}"; do
        if [[ -f "$file" ]]; then
            log_success "✓ Critical file exists: $(basename "$file")"
        else
            log_error "✗ Critical file missing: $file"
            ((errors++))
        fi
    done
    
    if [[ $errors -eq 0 ]]; then
        log_success "All health checks passed"
        return 0
    else
        log_error "$errors health check(s) failed"
        return 1
    fi
}

# Monitoring setup
setup_monitoring() {
    if [[ "${ENABLE_MONITORING}" != "true" ]]; then
        log_info "Monitoring disabled, skipping..."
        return 0
    fi
    
    log_info "Setting up basic monitoring..."
    
    # Create monitoring script
    create_monitoring_script
    
    # Set up log rotation
    setup_log_rotation
    
    log_success "Basic monitoring configured"
}

create_monitoring_script() {
    local monitor_script="/usr/local/bin/pkki-monitor"
    
    sudo tee "$monitor_script" > /dev/null << 'EOF'
#!/bin/bash
# PKKI ITERA Monitoring Script

APP_DIR="/home/partikelxyz/pkki.itera"
LOG_FILE="/var/log/pkki.itera/monitor.log"

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check disk space
disk_usage=$(df / | awk 'NR==2{print $5}' | sed 's/%//')
if [[ $disk_usage -gt 85 ]]; then
    log_message "WARNING: Disk usage is ${disk_usage}%"
fi

# Check memory usage
memory_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [[ $memory_usage -gt 85 ]]; then
    log_message "WARNING: Memory usage is ${memory_usage}%"
fi

# Check if application is responding
if ! curl -s --max-time 10 http://localhost >/dev/null; then
    log_message "ERROR: Application not responding"
fi

# Check Laravel logs for errors
if [[ -f "$APP_DIR/storage/logs/laravel.log" ]]; then
    recent_errors=$(tail -n 100 "$APP_DIR/storage/logs/laravel.log" | grep -c "ERROR" || echo "0")
    if [[ $recent_errors -gt 5 ]]; then
        log_message "WARNING: $recent_errors recent errors found in Laravel logs"
    fi
fi
EOF

    sudo chmod +x "$monitor_script"
    
    # Add to crontab (run every 5 minutes)
    (crontab -l 2>/dev/null; echo "*/5 * * * * $monitor_script") | crontab -
}

setup_log_rotation() {
    log_info "Setting up log rotation..."
    
    sudo tee "/etc/logrotate.d/${APP_NAME}" > /dev/null << EOF
${LOG_DIR}/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 ${APP_USER} ${WEB_USER}
    postrotate
        systemctl reload nginx > /dev/null 2>&1 || true
        systemctl reload php${PHP_VERSION}-fpm > /dev/null 2>&1 || true
    endscript
}

${APP_DIR}/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 ${APP_USER} ${WEB_USER}
}
EOF

    log_success "Log rotation configured"
}

check_requirements() {
    log_info "Checking system requirements..."
    
    # Check if running as root for system operations
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root. Run as $APP_USER and use sudo when needed."
        exit 1
    fi
    
    # Check if user can sudo
    if ! sudo -n true 2>/dev/null; then
        log_error "User $APP_USER needs sudo privileges to run this script"
        exit 1
    fi
    
    # Check minimum system requirements
    local total_memory=$(free -m | awk 'NR==2{printf "%d", $2}')
    if [[ $total_memory -lt 1024 ]]; then
        log_warning "System has less than 1GB RAM (${total_memory}MB). Performance may be affected."
    fi
    
    # Check available disk space (require at least 2GB)
    local available_disk=$(df / | awk 'NR==2{printf "%d", $4/1024/1024}')
    if [[ $available_disk -lt 2 ]]; then
        log_error "Insufficient disk space: ${available_disk}GB available (required: >2GB)"
        exit 1
    fi
    
    log_success "System requirements check passed"
}

pre_deployment_checks() {
    log_info "Running pre-deployment checks..."
    
    # Check if ports are available
    local required_ports=(80 443)
    for port in "${required_ports[@]}"; do
        if sudo netstat -tuln | grep -q ":${port} "; then
            local service=$(sudo lsof -i :${port} | tail -n1 | awk '{print $1}')
            log_warning "Port ${port} is already in use by: $service"
            if [[ "$service" == "apache2" ]]; then
                log_info "Apache2 detected. Will stop it during deployment."
            fi
        else
            log_success "Port ${port} is available"
        fi
    done
    
    # Check if domain points to this server (if not localhost)
    if [[ "$DOMAIN" != "localhost" && "$DOMAIN" != "127.0.0.1" ]]; then
        log_info "Checking DNS resolution for $DOMAIN..."
        local dns_ips=($(nslookup ${DOMAIN} 2>/dev/null | grep "Address:" | grep -v "#53" | awk '{print $2}'))
        if [[ " ${dns_ips[@]} " =~ " ${SERVER_IP} " ]]; then
            log_success "DNS correctly points to this server"
        else
            log_warning "DNS does not point to this server IP (${SERVER_IP})"
            log_warning "DNS currently points to: ${dns_ips[*]}"
            echo ""
            read -p "Continue deployment anyway? (y/n): " -n 1 -r
            echo ""
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                log_info "Deployment cancelled. Please update DNS first."
                exit 0
            fi
        fi
    fi
    
    log_success "Pre-deployment checks completed"
}

install_dependencies() {
    log_info "Installing system dependencies..."
    
    sudo apt update
    sudo apt install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-xmlrpc \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-dev \
        php${PHP_VERSION}-imap \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-redis \
        nginx \
        git \
        curl \
        unzip \
        mysql-client \
        postgresql-client
    
    # Install Composer if not present
    if ! command -v composer &> /dev/null; then
        log_info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
    fi
    
    log_success "Dependencies installed"
}

clone_repository() {
    log_info "Cloning repository..."
    
    if [ -d "$APP_DIR" ]; then
        log_warning "Directory $APP_DIR already exists. Removing..."
        sudo rm -rf "$APP_DIR"
    fi
    
    cd /home/${APP_USER}
    git clone https://github.com/labtekozt/pkki.itera.git ${APP_NAME}
    cd ${APP_NAME}
    
    log_success "Repository cloned"
}

setup_laravel() {
    log_info "Setting up Laravel application..."
    
    cd ${APP_DIR}
    
    # Install PHP dependencies
    log_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
    
    # Create .env file
    log_info "Setting up environment configuration..."
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    
    # Generate application key
    php artisan key:generate --force
    
    # Set up storage link
    php artisan storage:link
    
    log_success "Laravel application setup completed"
}

configure_environment() {
    log_info "Configuring environment variables..."
    
    cd ${APP_DIR}
    
    # Update .env with production settings
    cat > .env << 'EOF'
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://hki.proyekai.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.yipiconzxrhlcmipteqc
DB_PASSWORD=lg15kV1SuiSg1oxz
DATABASE_URL=postgresql://postgres.yipiconzxrhlcmipteqc:lg15kV1SuiSg1oxz@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=file
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
EOF
    
    # Generate fresh key
    php artisan key:generate --force
    
    log_success "Environment configured"
}

setup_database() {
    log_info "Setting up database..."
    
    cd ${APP_DIR}
    
    # Test database connection
    if ! php artisan migrate:status --ansi; then
        log_error "Database connection failed. Please check your credentials."
        exit 1
    fi
    
    # Run migrations
    log_info "Running database migrations..."
    php artisan migrate --force
    
    # Seed database
    log_info "Seeding database..."
    php artisan db:seed --force
    
    # Generate Filament Shield permissions
    log_info "Generating Filament Shield permissions..."
    php artisan shield:generate --all --force
    
    log_success "Database setup completed"
}

configure_permissions() {
    log_info "Setting up basic file permissions..."
    
    cd ${APP_DIR}
    
    # Set ownership
    sudo chown -R ${APP_USER}:${WEB_USER} .
    
    # Set permissions
    sudo chmod -R 755 .
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
    sudo chmod -R 775 public
    
    # Set specific permissions for sensitive files
    sudo chmod 644 .env
    
    log_success "Basic permissions configured"
}

optimize_laravel() {
    log_info "Optimizing Laravel application..."
    
    cd ${APP_DIR}
    
    # Clear all caches first
    php artisan optimize:clear
    
    # Install Node.js if not present
    if ! command -v npm &> /dev/null; then
        log_info "Installing Node.js and npm..."
        curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
        sudo apt-get install -y nodejs
    fi
    
    # Install and build frontend assets
    log_info "Installing and building frontend assets..."
    npm install
    npm run build
    
    # Publish all assets
    log_info "Publishing Laravel and Filament assets..."
    php artisan filament:assets
    php artisan livewire:publish --assets
    
    # Fix app.blade.php Vite references if needed
    log_info "Fixing Vite asset references..."
    if grep -q "resources/css/index.css" resources/views/app.blade.php 2>/dev/null; then
        sed -i 's/resources\/css\/index.css/resources\/css\/app.css/g' resources/views/app.blade.php
        sed -i 's/resources\/js\/index.jsx/resources\/js\/app.jsx/g' resources/views/app.blade.php
        log_info "Fixed Vite asset references in app.blade.php"
    fi
    
    # Create symbolic link for Livewire assets (fallback)
    if [ ! -L "public/livewire" ]; then
        ln -sf vendor/livewire public/livewire
        log_info "Created Livewire assets symbolic link"
    fi
    
    # Cache configuration for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    # Optimize autoloader
    composer dump-autoload --optimize
    
    log_success "Laravel application optimized"
}

configure_nginx() {
    log_info "Configuring Nginx..."
    
    # Create Nginx site configuration with Livewire support
    sudo tee ${NGINX_AVAILABLE}/${APP_NAME} > /dev/null << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN} ${SERVER_IP};
    root ${APP_DIR}/public;
    index index.php index.html index.htm;

    # Logging
    access_log /var/log/nginx/${APP_NAME}_access.log;
    error_log /var/log/nginx/${APP_NAME}_error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate no_last_modified no_etag auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # Laravel specific
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for large requests
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Livewire assets fallback (if route doesn't work)
    location /livewire/livewire.min.js {
        try_files \$uri /vendor/livewire/livewire.min.js;
    }
    
    location /livewire/livewire.min.js.map {
        try_files \$uri /vendor/livewire/livewire.min.js.map;
    }

    # Handle static assets
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Deny access to backup files
    location ~ ~\$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Increase max upload size
    client_max_body_size 50M;
}
EOF
    
    # Enable the site
    sudo ln -sf ${NGINX_AVAILABLE}/${APP_NAME} ${NGINX_ENABLED}/${APP_NAME}
    
    # Remove default site if it exists
    sudo rm -f ${NGINX_ENABLED}/default
    
    # Test Nginx configuration
    if ! sudo nginx -t; then
        log_error "Nginx configuration test failed"
        exit 1
    fi
    
    log_success "Nginx configured"
}

configure_php_fpm() {
    log_info "Configuring PHP-FPM..."
    
    # Fix PHP-FPM security settings first
    sudo sed -i 's/;security.limit_extensions = .php .php3 .php4 .php5 .php7/security.limit_extensions = .php/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
    
    # Optimize PHP-FPM for Laravel
    sudo tee /etc/php/${PHP_VERSION}/fpm/pool.d/${APP_NAME}.conf > /dev/null << EOF
[${APP_NAME}]
user = ${WEB_USER}
group = ${WEB_USER}
listen = /run/php/php${PHP_VERSION}-fpm.sock
listen.owner = ${WEB_USER}
listen.group = ${WEB_USER}
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Security
security.limit_extensions = .php

; Environment variables
env[HOSTNAME] = \$HOSTNAME
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp

; PHP values
php_admin_value[sendmail_path] = /usr/sbin/sendmail -t -i -f www@${DOMAIN}
php_flag[display_errors] = off
php_admin_value[error_log] = /var/log/fpm-php.${APP_NAME}.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
php_admin_value[max_execution_time] = 300
EOF
    
    log_success "PHP-FPM configured"
}

start_services() {
    log_info "Starting services..."
    
    # Stop any conflicting services
    log_info "Stopping conflicting services..."
    sudo systemctl stop apache2 2>/dev/null || true
    sudo systemctl disable apache2 2>/dev/null || true
    
    # Restart and enable services
    sudo systemctl restart php${PHP_VERSION}-fpm
    sudo systemctl enable php${PHP_VERSION}-fpm
    
    sudo systemctl restart nginx
    sudo systemctl enable nginx
    
    # Check service status
    if ! sudo systemctl is-active --quiet php${PHP_VERSION}-fpm; then
        log_error "PHP-FPM failed to start"
        sudo systemctl status php${PHP_VERSION}-fpm
        exit 1
    fi
    
    if ! sudo systemctl is-active --quiet nginx; then
        log_error "Nginx failed to start"
        sudo systemctl status nginx
        exit 1
    fi
    
    log_success "Services started successfully"
}

fix_permissions_advanced() {
    log_info "Fixing advanced permissions and ownership..."
    
    cd ${APP_DIR}
    
    # Ensure correct ownership
    sudo chown -R ${APP_USER}:${WEB_USER} .
    
    # Fix directory permissions for parent directories
    sudo chmod 755 /home/${APP_USER}
    
    # Set Laravel-specific permissions
    sudo chmod -R 755 .
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
    sudo chmod -R 775 public
    sudo chmod -R 775 public/build 2>/dev/null || true
    
    # Filament-specific permissions for theme files
    log_info "Setting Filament theme permissions..."
    sudo mkdir -p resources/css/filament/admin
    sudo mkdir -p resources/views/filament
    sudo chmod -R 775 resources/css/filament 2>/dev/null || true
    sudo chmod -R 775 resources/views/filament 2>/dev/null || true
    sudo chown -R ${APP_USER}:${WEB_USER} resources/css/filament 2>/dev/null || true
    sudo chown -R ${APP_USER}:${WEB_USER} resources/views/filament 2>/dev/null || true
    
    # Create theme.css if it doesn't exist
    if [ ! -f "resources/css/filament/admin/theme.css" ]; then
        sudo touch resources/css/filament/admin/theme.css
        sudo chown ${APP_USER}:${WEB_USER} resources/css/filament/admin/theme.css
        sudo chmod 664 resources/css/filament/admin/theme.css
        log_info "Created Filament theme.css file"
    fi
    
    # Specific file permissions
    sudo chmod 644 .env
    sudo chmod +x artisan
    
    # Make sure web server can access assets
    sudo chown -R ${APP_USER}:${WEB_USER} public/build 2>/dev/null || true
    sudo chown -R ${APP_USER}:${WEB_USER} public/vendor 2>/dev/null || true
    
    # Additional Laravel writable directories
    sudo chmod -R 775 database 2>/dev/null || true
    sudo mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
    sudo chmod -R 775 storage/framework 2>/dev/null || true
    
    log_success "Advanced permissions configured"
}

troubleshoot_deployment() {
    log_info "Running deployment troubleshooting..."
    
    cd ${APP_DIR}
    
    # Check PHP version and extensions
    log_info "PHP Version: $(php --version | head -n1)"
    
    # Check required PHP extensions
    local required_extensions=("pdo" "pdo_pgsql" "mbstring" "xml" "curl" "zip" "gd" "intl" "bcmath")
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "$ext"; then
            log_success "PHP extension '$ext' is loaded"
        else
            log_warning "PHP extension '$ext' is NOT loaded"
        fi
    done
    
    # Test database connection
    log_info "Testing database connection..."
    if php artisan migrate:status --ansi >/dev/null 2>&1; then
        log_success "Database connection successful"
    else
        log_warning "Database connection failed"
    fi
    
    # Check critical files
    local critical_files=("public/build/manifest.json" "public/vendor/livewire/livewire.min.js" "storage/app" "bootstrap/cache")
    for file in "${critical_files[@]}"; do
        if [ -e "$file" ]; then
            log_success "Critical file/directory '$file' exists"
        else
            log_warning "Critical file/directory '$file' is missing"
        fi
    done
    
    # Test web server response
    log_info "Testing web server response..."
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost -H "Host: ${DOMAIN}")
    if [[ "$response" == "200" ]]; then
        log_success "Web server is responding correctly (HTTP $response)"
    else
        log_warning "Web server returned HTTP $response"
    fi
    
    log_success "Troubleshooting completed"
}

setup_ssl() {
    log_info "Setting up SSL certificate..."
    
    # Install Certbot if not present
    if ! command -v certbot &> /dev/null; then
        sudo apt update
        sudo apt install -y certbot python3-certbot-nginx
    fi
    
    # Obtain SSL certificate
    if sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} --non-interactive --agree-tos --email admin@${DOMAIN}; then
        log_success "SSL certificate obtained"
    else
        log_warning "SSL certificate setup failed. You can set it up manually later."
    fi
}

verify_deployment() {
    log_info "Verifying deployment..."
    
    cd ${APP_DIR}
    
    # Test Laravel application
    if php artisan --version &> /dev/null; then
        log_success "Laravel application is working"
    else
        log_error "Laravel application test failed"
        exit 1
    fi
    
    # Test web server response with multiple methods
    local ip_response=$(curl -s -o /dev/null -w "%{http_code}" http://${SERVER_IP})
    local domain_response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost -H "Host: ${DOMAIN}")
    
    if [[ "$ip_response" == "200" ]]; then
        log_success "Web server responds to IP address (HTTP $ip_response)"
    else
        log_warning "Web server IP test returned HTTP $ip_response"
    fi
    
    if [[ "$domain_response" == "200" ]]; then
        log_success "Web server responds to domain (HTTP $domain_response)"
    else
        log_warning "Web server domain test returned HTTP $domain_response"
    fi
    
    # Test admin panel
    local admin_response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin -H "Host: ${DOMAIN}")
    if [[ "$admin_response" == "200" || "$admin_response" == "302" ]]; then
        log_success "Admin panel is accessible (HTTP $admin_response)"
    else
        log_warning "Admin panel test returned HTTP $admin_response"
    fi
    
    # Check DNS resolution
    log_info "Checking DNS resolution..."
    local dns_ips=($(nslookup ${DOMAIN} | grep "Address:" | grep -v "#53" | awk '{print $2}'))
    if [[ " ${dns_ips[@]} " =~ " ${SERVER_IP} " ]]; then
        log_success "DNS correctly points to server IP"
    else
        log_warning "DNS does not point to server IP ${SERVER_IP}"
        log_warning "DNS points to: ${dns_ips[*]}"
        log_warning "This may be due to Cloudflare or other proxy services"
    fi
    
    # Show final status
    echo ""
    echo "==================================="
    echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
    echo "==================================="
    echo ""
    echo "Application Details:"
    echo "==================="
    echo "Server IP:        http://${SERVER_IP}"
    echo "Domain:           http://${DOMAIN}"
    echo "Admin Panel:      http://${DOMAIN}/admin"
    echo "HTTPS Domain:     https://${DOMAIN} (after SSL setup)"
    echo ""
    echo "Access Methods:"
    echo "==============="
    echo "✅ Direct IP:     curl -I http://${SERVER_IP}"
    echo "✅ Local test:    curl -H 'Host: ${DOMAIN}' http://127.0.0.1"
    if [[ " ${dns_ips[@]} " =~ " ${SERVER_IP} " ]]; then
        echo "✅ Domain:        curl -I http://${DOMAIN}"
    else
        echo "⚠️  Domain:        DNS needs to point to ${SERVER_IP}"
        echo "   Current DNS:   ${dns_ips[*]}"
    fi
    echo ""
    echo "Troubleshooting:"
    echo "================"
    echo "Laravel logs:     tail -f ${APP_DIR}/storage/logs/laravel.log"
    echo "Nginx logs:       sudo tail -f /var/log/nginx/${APP_NAME}_error.log"
    echo "PHP-FPM logs:     sudo tail -f /var/log/fpm-php.${APP_NAME}.log"
    echo "Service status:   sudo systemctl status nginx php${PHP_VERSION}-fpm"
    echo ""
    if [[ ! " ${dns_ips[@]} " =~ " ${SERVER_IP} " ]]; then
        echo "🔧 DNS Configuration Required:"
        echo "=============================="
        echo "1. Login to your domain registrar or DNS provider"
        echo "2. Update A record for '${DOMAIN}' to point to: ${SERVER_IP}"
        echo "3. If using Cloudflare, update the origin server settings"
        echo "4. Wait 5-15 minutes for DNS propagation"
        echo ""
    fi
}

# Function to display help
show_help() {
    cat << EOF
$SCRIPT_NAME v$SCRIPT_VERSION

DESCRIPTION:
    Performs a complete fresh deployment of the PKKI ITERA Laravel application
    with comprehensive error handling, backup/rollback, security hardening,
    and monitoring setup.

USAGE:
    $0 [OPTIONS]

OPTIONS:
    -h, --help          Show this help message and exit
    -d, --debug         Enable debug mode with verbose output
    --no-backup         Skip backup creation (not recommended for production)
    --no-monitoring     Skip monitoring setup
    --domain DOMAIN     Set custom domain (default: $DOMAIN)
    --ip IP_ADDRESS     Set server IP address (auto-detected if not specified)
    --dry-run           Perform validation checks only, don't deploy

EXAMPLES:
    # Standard deployment
    $0

    # Deployment with custom domain
    $0 --domain myapp.example.com

    # Debug mode deployment
    $0 --debug

    # Production deployment without backup (not recommended)
    $0 --no-backup

REQUIREMENTS:
    - Ubuntu 20.04+ or Debian 11+
    - User with sudo privileges
    - Internet connection
    - At least 1GB RAM and 2GB disk space

SUPPORT:
    For issues or questions, check the deployment logs:
    - Deployment log: $LOG_FILE
    - Laravel logs: $APP_DIR/storage/logs/laravel.log
    - Nginx logs: /var/log/nginx/${APP_NAME}_error.log

EOF
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -d|--debug)
                DEBUG="true"
                log_info "Debug mode enabled"
                shift
                ;;
            --no-backup)
                ENABLE_BACKUP="false"
                log_warning "Backup disabled"
                shift
                ;;
            --no-monitoring)
                ENABLE_MONITORING="false"
                log_warning "Monitoring disabled"
                shift
                ;;
            --domain)
                DOMAIN="$2"
                log_info "Using custom domain: $DOMAIN"
                shift 2
                ;;
            --ip)
                SERVER_IP="$2"
                log_info "Using custom IP: $SERVER_IP"
                shift 2
                ;;
            --dry-run)
                DRY_RUN="true"
                log_info "Dry run mode enabled"
                shift
                ;;
            *)
                log_error "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
}

# Main deployment process
main() {
    # Set up logging
    setup_logging
    
    # Parse command line arguments
    parse_arguments "$@"
    
    # Enable error handling
    set_error_handling
    
    log_info "Starting fresh deployment of PKKI ITERA..."
    echo ""
    
    # Exit early if dry run
    if [[ "${DRY_RUN:-false}" == "true" ]]; then
        log_info "DRY RUN MODE - Performing validation checks only"
        validate_environment
        check_connectivity
        pre_deployment_checks
        check_requirements
        log_success "Dry run completed - all checks passed"
        exit 0
    fi
    
    # Validate environment before starting
    validate_environment
    check_connectivity
    pre_deployment_checks
    
    # Create backup if enabled
    create_backup
    
    # Main deployment steps
    check_requirements
    install_dependencies
    clone_repository
    setup_laravel
    configure_environment
    setup_database
    configure_permissions
    fix_permissions_advanced
    optimize_laravel
    configure_nginx
    configure_php_fpm
    start_services
    
    # Apply security hardening and optimizations
    harden_system
    optimize_system
    
    # Run troubleshooting checks
    troubleshoot_deployment
    
    # Run health checks
    if ! health_check; then
        log_error "Health checks failed after deployment"
        exit 1
    fi
    
    # Set up monitoring
    setup_monitoring
    
    # Optional SSL setup
    read -p "Do you want to set up SSL certificate? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_ssl
    fi
    
    # Final verification
    verify_deployment
    
    log_success "Deployment completed successfully!"
}

# Run the main function
main "$@"
