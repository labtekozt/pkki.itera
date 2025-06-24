#!/bin/bash

# PKKI ITERA - Complete Deployment Script with Supabase Setup
# Comprehensive production-ready deployment for Laravel + Supabase + Filament
# Version: 3.0.0

set -e

# Script information
readonly SCRIPT_VERSION="3.0.0"
readonly SCRIPT_NAME="PKKI ITERA Deployment & Supabase Setup"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Default configuration
ENVIRONMENT="production"
DOMAIN=""
SSL_EMAIL=""
AUTO_YES=false
SKIP_SYSTEM_SETUP=false
SKIP_NGINX=false
SKIP_SSL=false
SKIP_COMPOSER=false
SKIP_NPM=false
SKIP_MIGRATIONS=false
SKIP_SEEDERS=false
SKIP_PERMISSIONS=false
SETUP_NGINX_ONLY=false
SETUP_SSL_ONLY=false
CLEANUP_ONLY=false
DRY_RUN=false
BACKUP_BEFORE_DEPLOY=true
SERVER_IP=""

# Supabase Configuration
SUPABASE_URL=""
SUPABASE_ANON_KEY=""
SUPABASE_SERVICE_ROLE_KEY=""
DB_HOST=""
DB_PORT="5432"
DB_DATABASE=""
DB_USERNAME=""
DB_PASSWORD=""

# System Configuration
PHP_VERSION="8.3"
NODE_VERSION="20"
NGINX_USER="www-data"
PROJECT_USER="www-data"
PROJECT_GROUP="www-data"

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

debug() {
    if [ "${DEBUG:-false}" = "true" ]; then
        echo -e "${PURPLE}[$(date +'%H:%M:%S')] 🐛 $1${NC}"
    fi
}

# Show help message
show_help() {
    echo -e "${CYAN}${SCRIPT_NAME} v${SCRIPT_VERSION}${NC}"
    echo
    echo "Complete production deployment setup for PKKI ITERA with Supabase PostgreSQL"
    echo
    echo "USAGE:"
    echo "    ./scripts/deploy-supabase-setup.sh [OPTIONS]"
    echo
    echo "ENVIRONMENT OPTIONS:"
    echo "    --env ENV               Environment (development|staging|production) [default: production]"
    echo "    --domain DOMAIN         Domain name for SSL and Nginx setup"
    echo "    --ssl-email EMAIL       Email for SSL certificate registration"
    echo
    echo "DEPLOYMENT OPTIONS:"
    echo "    -h, --help              Show this help message"
    echo "    -v, --version           Show version information"
    echo "    -y, --yes               Auto-confirm all prompts"
    echo "    --dry-run               Show what would be done without executing"
    echo "    --no-backup             Skip backup before deployment"
    echo
    echo "SKIP OPTIONS:"
    echo "    --skip-system           Skip system setup (PHP, Node, packages)"
    echo "    --skip-nginx            Skip Nginx configuration"
    echo "    --skip-ssl              Skip SSL certificate setup"
    echo "    --skip-composer         Skip composer install"
    echo "    --skip-npm              Skip npm install and build"
    echo "    --skip-migrations       Skip database migrations"
    echo "    --skip-seeders          Skip database seeders"
    echo "    --skip-permissions      Skip file permissions setup"
    echo
    echo "SETUP ONLY OPTIONS:"
    echo "    --nginx-only            Only setup Nginx configuration"
    echo "    --ssl-only              Only setup SSL certificates"
    echo "    --cleanup-only          Only run cleanup operations"
    echo
    echo "SUPABASE OPTIONS:"
    echo "    --supabase-url URL      Supabase project URL"
    echo "    --supabase-anon KEY     Supabase anonymous key"
    echo "    --supabase-service KEY  Supabase service role key"
    echo "    --db-host HOST          Database host"
    echo "    --db-port PORT          Database port [default: 5432]"
    echo "    --db-name DATABASE      Database name"
    echo "    --db-user USERNAME      Database username"
    echo "    --db-pass PASSWORD      Database password"
    echo
    echo "EXAMPLES:"
    echo "    # Complete production deployment with domain"
    echo "    ./scripts/deploy-supabase-setup.sh --env production --domain pkki.itera.ac.id --ssl-email admin@itera.ac.id"
    echo
    echo "    # Setup with custom Supabase configuration"
    echo "    ./scripts/deploy-supabase-setup.sh --supabase-url https://xxx.supabase.co \\"
    echo "        --db-host xxx.pooler.supabase.com --db-name postgres --db-user postgres.xxx"
    echo
    echo "    # Nginx only setup"
    echo "    ./scripts/deploy-supabase-setup.sh --nginx-only --domain pkki.itera.ac.id"
    echo
    echo "    # Development environment"
    echo "    ./scripts/deploy-supabase-setup.sh --env development --skip-ssl --skip-nginx"
    echo
    echo "    # Dry run to see what would be executed"
    echo "    ./scripts/deploy-supabase-setup.sh --dry-run --env production"
    echo
}

# Show version information
show_version() {
    echo "${SCRIPT_NAME} v${SCRIPT_VERSION}"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -v|--version)
                show_version
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
            --no-backup)
                BACKUP_BEFORE_DEPLOY=false
                shift
                ;;
            --env)
                ENVIRONMENT="$2"
                shift 2
                ;;
            --domain)
                DOMAIN="$2"
                shift 2
                ;;
            --ssl-email)
                SSL_EMAIL="$2"
                shift 2
                ;;
            --skip-system)
                SKIP_SYSTEM_SETUP=true
                shift
                ;;
            --skip-nginx)
                SKIP_NGINX=true
                shift
                ;;
            --skip-ssl)
                SKIP_SSL=true
                shift
                ;;
            --skip-composer)
                SKIP_COMPOSER=true
                shift
                ;;
            --skip-npm)
                SKIP_NPM=true
                shift
                ;;
            --skip-migrations)
                SKIP_MIGRATIONS=true
                shift
                ;;
            --skip-seeders)
                SKIP_SEEDERS=true
                shift
                ;;
            --skip-permissions)
                SKIP_PERMISSIONS=true
                shift
                ;;
            --nginx-only)
                SETUP_NGINX_ONLY=true
                shift
                ;;
            --ssl-only)
                SETUP_SSL_ONLY=true
                shift
                ;;
            --cleanup-only)
                CLEANUP_ONLY=true
                shift
                ;;
            --supabase-url)
                SUPABASE_URL="$2"
                shift 2
                ;;
            --supabase-anon)
                SUPABASE_ANON_KEY="$2"
                shift 2
                ;;
            --supabase-service)
                SUPABASE_SERVICE_ROLE_KEY="$2"
                shift 2
                ;;
            --db-host)
                DB_HOST="$2"
                shift 2
                ;;
            --db-port)
                DB_PORT="$2"
                shift 2
                ;;
            --db-name)
                DB_DATABASE="$2"
                shift 2
                ;;
            --db-user)
                DB_USERNAME="$2"
                shift 2
                ;;
            --db-pass)
                DB_PASSWORD="$2"
                shift 2
                ;;
            *)
                error "Unknown option: $1"
                echo "Use --help for usage information"
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
        debug "Command: $cmd"
    else
        log "$description"
        debug "Executing: $cmd"
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

# Check if running as root
check_root() {
    if [ "$EUID" -eq 0 ]; then
        warn "Running as root. This is not recommended for security reasons."
        if [ "$AUTO_YES" = "false" ]; then
            confirm_action "Continue running as root?"
        fi
    fi
}

# Get server IP address
get_server_ip() {
    if [ -n "$SERVER_IP" ]; then
        return 0
    fi
    
    # Try multiple methods to get external IP
    SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || \
                curl -s ipinfo.io/ip 2>/dev/null || \
                curl -s icanhazip.com 2>/dev/null || \
                dig +short myip.opendns.com @resolver1.opendns.com 2>/dev/null)
    
    if [ -z "$SERVER_IP" ]; then
        warn "Could not automatically detect server IP"
        if [ "$AUTO_YES" = "false" ]; then
            read -p "Please enter your server IP address: " SERVER_IP
        fi
    else
        info "Detected server IP: $SERVER_IP"
    fi
}

# Check system requirements
check_requirements() {
    log "🔍 Checking system requirements..."
    
    # Check if we're in Laravel project root
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        error "❌ Not in Laravel project root directory"
        error "Expected to find artisan file in: $PROJECT_ROOT"
        exit 1
    fi
    
    # Check operating system
    if [[ "$OSTYPE" != "linux-gnu"* ]]; then
        warn "This script is designed for Linux systems"
        confirm_action "Continue on non-Linux system?"
    fi
    
    # Check if required commands exist
    local required_commands=("curl" "wget" "unzip")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            error "❌ Required command '$cmd' not found"
            exit 1
        fi
    done
    
    success "✅ System requirements check passed"
}

# Install system dependencies
install_system_dependencies() {
    if [ "$SKIP_SYSTEM_SETUP" = "true" ]; then
        info "Skipping system dependencies installation"
        return 0
    fi
    
    log "📦 Installing system dependencies..."
    
    # Update package lists
    execute "apt-get update" "Updating package lists"
    
    # Install essential packages
    local packages=(
        "curl" "wget" "unzip" "git" "htop" "nano" "vim"
        "software-properties-common" "apt-transport-https"
        "ca-certificates" "gnupg" "lsb-release"
    )
    
    execute "apt-get install -y ${packages[*]}" "Installing essential packages"
    
    success "✅ System dependencies installed"
}

# Install PHP and extensions
install_php() {
    if [ "$SKIP_SYSTEM_SETUP" = "true" ]; then
        info "Skipping PHP installation"
        return 0
    fi
    
    log "🐘 Installing PHP $PHP_VERSION and extensions..."
    
    # Add PHP repository
    execute "add-apt-repository ppa:ondrej/php -y" "Adding PHP repository"
    execute "apt-get update" "Updating package lists"
    
    # Install PHP and essential extensions
    local php_packages=(
        "php${PHP_VERSION}" "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli"
        "php${PHP_VERSION}-common" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip"
        "php${PHP_VERSION}-gd" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-mbstring"
        "php${PHP_VERSION}-intl" "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-json"
        "php${PHP_VERSION}-pgsql" "php${PHP_VERSION}-redis" "php${PHP_VERSION}-imagick"
    )
    
    execute "apt-get install -y ${php_packages[*]}" "Installing PHP and extensions"
    
    # Configure PHP-FPM
    setup_php_fpm
    
    success "✅ PHP $PHP_VERSION installed and configured"
}

# Setup PHP-FPM configuration
setup_php_fpm() {
    log "⚙️ Configuring PHP-FPM for Laravel..."
    
    local fpm_conf="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    local php_ini="/etc/php/${PHP_VERSION}/fpm/php.ini"
    
    # Backup original configurations
    execute "cp $fpm_conf ${fpm_conf}.backup" "Backing up PHP-FPM configuration"
    execute "cp $php_ini ${php_ini}.backup" "Backing up PHP configuration"
    
    # Configure PHP-FPM pool
    cat > "/tmp/php-fpm-www.conf" << 'EOF'
[www]
user = www-data
group = www-data
listen = /run/php/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

; Performance optimizations for Laravel
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_vars] = 3000
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.revalidate_freq] = 2
EOF
    
    execute "mv /tmp/php-fpm-www.conf $fpm_conf" "Updating PHP-FPM pool configuration"
    
    # Configure PHP settings
    execute "sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' $php_ini" "Securing PHP CGI"
    execute "sed -i 's/memory_limit = .*/memory_limit = 256M/' $php_ini" "Setting PHP memory limit"
    execute "sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' $php_ini" "Setting upload max filesize"
    execute "sed -i 's/post_max_size = .*/post_max_size = 100M/' $php_ini" "Setting post max size"
    execute "sed -i 's/max_execution_time = .*/max_execution_time = 300/' $php_ini" "Setting max execution time"
    
    # Enable and restart PHP-FPM
    execute "systemctl enable php${PHP_VERSION}-fpm" "Enabling PHP-FPM service"
    execute "systemctl restart php${PHP_VERSION}-fpm" "Restarting PHP-FPM service"
    
    success "✅ PHP-FPM configured for Laravel"
}

# Install Composer
install_composer() {
    if command -v composer &> /dev/null; then
        info "Composer already installed"
        return 0
    fi
    
    log "📦 Installing Composer..."
    
    # Download and install Composer
    execute "curl -sS https://getcomposer.org/installer | php" "Downloading Composer installer"
    execute "mv composer.phar /usr/local/bin/composer" "Installing Composer globally"
    execute "chmod +x /usr/local/bin/composer" "Making Composer executable"
    
    success "✅ Composer installed"
}

# Install Node.js and npm
install_nodejs() {
    if [ "$SKIP_SYSTEM_SETUP" = "true" ]; then
        info "Skipping Node.js installation"
        return 0
    fi
    
    log "📦 Installing Node.js $NODE_VERSION..."
    
    # Install NodeSource repository
    execute "curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -" "Adding NodeSource repository"
    execute "apt-get install -y nodejs" "Installing Node.js"
    
    # Install global packages
    execute "npm install -g npm@latest" "Updating npm to latest version"
    
    success "✅ Node.js $NODE_VERSION installed"
}

# Install Nginx
install_nginx() {
    if [ "$SKIP_NGINX" = "true" ] && [ "$SETUP_NGINX_ONLY" = "false" ]; then
        info "Skipping Nginx installation"
        return 0
    fi
    
    log "🌐 Installing Nginx..."
    
    execute "apt-get install -y nginx" "Installing Nginx"
    execute "systemctl enable nginx" "Enabling Nginx service"
    
    success "✅ Nginx installed"
}

# Setup Nginx configuration for Laravel
setup_nginx() {
    if [ "$SKIP_NGINX" = "true" ] && [ "$SETUP_NGINX_ONLY" = "false" ]; then
        info "Skipping Nginx configuration"
        return 0
    fi
    
    log "⚙️ Configuring Nginx for Laravel..."
    
    if [ -z "$DOMAIN" ]; then
        error "❌ Domain name is required for Nginx configuration"
        error "Use --domain option to specify domain name"
        exit 1
    fi
    
    # Remove default Nginx site
    execute "rm -f /etc/nginx/sites-enabled/default" "Removing default Nginx site"
    
    # Create Laravel Nginx configuration
    cat > "/tmp/nginx-laravel.conf" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${PROJECT_ROOT}/public;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Handle PHP files
    location ~ \\.php\$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Laravel optimizations
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~* \\.(env|git|svn|htaccess|htpasswd)$ {
        deny all;
    }

    # Handle static assets
    location ~* \\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # Logging
    access_log /var/log/nginx/${DOMAIN}_access.log;
    error_log /var/log/nginx/${DOMAIN}_error.log;
}
EOF
    
    execute "mv /tmp/nginx-laravel.conf /etc/nginx/sites-available/${DOMAIN}" "Creating Nginx site configuration"
    execute "ln -sf /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/" "Enabling Nginx site"
    
    # Test Nginx configuration
    execute "nginx -t" "Testing Nginx configuration"
    execute "systemctl reload nginx" "Reloading Nginx configuration"
    
    success "✅ Nginx configured for Laravel at $DOMAIN"
}

# Install SSL certificate with Certbot
setup_ssl() {
    if [ "$SKIP_SSL" = "true" ] && [ "$SETUP_SSL_ONLY" = "false" ]; then
        info "Skipping SSL setup"
        return 0
    fi
    
    if [ -z "$DOMAIN" ]; then
        error "❌ Domain name is required for SSL setup"
        error "Use --domain option to specify domain name"
        exit 1
    fi
    
    if [ -z "$SSL_EMAIL" ]; then
        error "❌ Email is required for SSL certificate registration"
        error "Use --ssl-email option to specify email address"
        exit 1
    fi
    
    log "🔒 Setting up SSL certificate with Let's Encrypt..."
    
    # Install Certbot
    execute "apt-get install -y certbot python3-certbot-nginx" "Installing Certbot"
    
    # Check if domain resolves to this server
    get_server_ip
    if [ -n "$SERVER_IP" ]; then
        local domain_ip=$(dig +short "$DOMAIN" 2>/dev/null || echo "")
        if [ "$domain_ip" != "$SERVER_IP" ]; then
            warn "Domain $DOMAIN does not resolve to this server IP ($SERVER_IP)"
            warn "Current DNS points to: $domain_ip"
            if [ "$AUTO_YES" = "false" ]; then
                confirm_action "Continue with SSL setup anyway? (DNS propagation might be in progress)"
            fi
        fi
    fi
    
    # Obtain SSL certificate
    execute "certbot --nginx -d $DOMAIN -d www.$DOMAIN --email $SSL_EMAIL --agree-tos --non-interactive --redirect" "Obtaining SSL certificate"
    
    # Setup auto-renewal
    setup_ssl_autorenewal
    
    success "✅ SSL certificate installed for $DOMAIN"
}

# Setup SSL certificate auto-renewal
setup_ssl_autorenewal() {
    log "⚙️ Setting up SSL certificate auto-renewal..."
    
    # Test renewal process
    execute "certbot renew --dry-run" "Testing SSL certificate renewal"
    
    # Add renewal cron job if not exists
    local cron_job="0 12 * * * /usr/bin/certbot renew --quiet && systemctl reload nginx"
    if ! crontab -l 2>/dev/null | grep -q "certbot renew"; then
        execute "(crontab -l 2>/dev/null; echo '$cron_job') | crontab -" "Adding SSL renewal cron job"
    fi
    
    success "✅ SSL auto-renewal configured"
}

# Configure HTTPS session settings
configure_https_session() {
    if [ "$SKIP_SSL" = "true" ]; then
        return 0
    fi
    
    log "🔒 Configuring HTTPS session settings..."
    
    # Update session configuration for HTTPS
    local session_config="$PROJECT_ROOT/config/session.php"
    if [ -f "$session_config" ]; then
        execute "sed -i \"s/'secure' => env('SESSION_SECURE_COOKIE', false)/'secure' => env('SESSION_SECURE_COOKIE', true)/\" $session_config" "Enabling secure session cookies"
        execute "sed -i \"s/'same_site' => 'lax'/'same_site' => 'strict'/\" $session_config" "Setting strict SameSite policy"
    fi
    
    success "✅ HTTPS session settings configured"
}

# Setup environment configuration
setup_environment() {
    log "⚙️ Setting up environment configuration..."
    
    local env_file="$PROJECT_ROOT/.env"
    local env_example="$PROJECT_ROOT/.env.example"
    
    # Create .env from example if not exists
    if [ ! -f "$env_file" ] && [ -f "$env_example" ]; then
        execute "cp $env_example $env_file" "Creating .env file from .env.example"
    fi
    
    # Update environment settings
    if [ -f "$env_file" ]; then
        # Basic Laravel settings
        execute "sed -i \"s/APP_ENV=.*/APP_ENV=$ENVIRONMENT/\" $env_file" "Setting APP_ENV"
        execute "sed -i \"s/APP_DEBUG=.*/APP_DEBUG=false/\" $env_file" "Setting APP_DEBUG"
        
        if [ -n "$DOMAIN" ]; then
            local app_url="https://$DOMAIN"
            if [ "$SKIP_SSL" = "true" ]; then
                app_url="http://$DOMAIN"
            fi
            execute "sed -i \"s|APP_URL=.*|APP_URL=$app_url|\" $env_file" "Setting APP_URL"
        fi
        
        # Database configuration
        if [ -n "$DB_HOST" ]; then
            execute "sed -i \"s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/\" $env_file" "Setting database connection"
            execute "sed -i \"s/DB_HOST=.*/DB_HOST=$DB_HOST/\" $env_file" "Setting database host"
            execute "sed -i \"s/DB_PORT=.*/DB_PORT=$DB_PORT/\" $env_file" "Setting database port"
            execute "sed -i \"s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/\" $env_file" "Setting database name"
            execute "sed -i \"s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/\" $env_file" "Setting database username"
            execute "sed -i \"s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/\" $env_file" "Setting database password"
        fi
        
        # Supabase configuration
        if [ -n "$SUPABASE_URL" ]; then
            # Remove existing Supabase entries
            execute "sed -i '/SUPABASE_/d' $env_file" "Removing old Supabase configuration"
            
            # Add new Supabase configuration
            cat >> "$env_file" << EOF

# Supabase Configuration
SUPABASE_URL=$SUPABASE_URL
SUPABASE_ANON_KEY=$SUPABASE_ANON_KEY
SUPABASE_SERVICE_ROLE_KEY=$SUPABASE_SERVICE_ROLE_KEY
EOF
        fi
        
        # Cache and session configuration for production
        if [ "$ENVIRONMENT" = "production" ]; then
            execute "sed -i \"s/CACHE_DRIVER=.*/CACHE_DRIVER=redis/\" $env_file" "Setting cache driver to Redis"
            execute "sed -i \"s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/\" $env_file" "Setting session driver to Redis"
            execute "sed -i \"s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/\" $env_file" "Setting queue connection to Redis"
        fi
        
        # Generate application key if not set
        local app_key=$(grep "APP_KEY=" "$env_file" | cut -d '=' -f2)
        if [ -z "$app_key" ] || [ "$app_key" = "" ]; then
            execute "cd $PROJECT_ROOT && php artisan key:generate --force" "Generating application key"
        fi
    fi
    
    success "✅ Environment configuration updated"
}

# Install Redis for caching and sessions
install_redis() {
    if [ "$SKIP_SYSTEM_SETUP" = "true" ]; then
        info "Skipping Redis installation"
        return 0
    fi
    
    log "📦 Installing Redis for caching and sessions..."
    
    execute "apt-get install -y redis-server" "Installing Redis server"
    execute "systemctl enable redis-server" "Enabling Redis service"
    execute "systemctl start redis-server" "Starting Redis service"
    
    # Configure Redis for production
    local redis_conf="/etc/redis/redis.conf"
    if [ -f "$redis_conf" ]; then
        execute "sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' $redis_conf" "Setting Redis max memory"
        execute "sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' $redis_conf" "Setting Redis eviction policy"
        execute "systemctl restart redis-server" "Restarting Redis service"
    fi
    
    success "✅ Redis installed and configured"
}

# Install PHP dependencies
install_php_dependencies() {
    if [ "$SKIP_COMPOSER" = "true" ]; then
        info "Skipping Composer dependencies installation"
        return 0
    fi
    
    log "📦 Installing PHP dependencies with Composer..."
    
    cd "$PROJECT_ROOT"
    
    # Install dependencies based on environment
    if [ "$ENVIRONMENT" = "production" ]; then
        execute "composer install --optimize-autoloader --no-dev --no-interaction" "Installing production PHP dependencies"
    else
        execute "composer install --no-interaction" "Installing PHP dependencies"
    fi
    
    success "✅ PHP dependencies installed"
}

# Install Node.js dependencies and build assets
install_node_dependencies() {
    if [ "$SKIP_NPM" = "true" ]; then
        info "Skipping Node.js dependencies installation"
        return 0
    fi
    
    log "📦 Installing Node.js dependencies..."
    
    cd "$PROJECT_ROOT"
    
    if [ -f "package.json" ]; then
        execute "npm ci" "Installing Node.js dependencies"
        
        # Build assets based on environment
        if [ "$ENVIRONMENT" = "production" ]; then
            execute "npm run build" "Building production assets"
        else
            execute "npm run dev" "Building development assets"
        fi
    else
        warn "No package.json found, skipping Node.js dependencies"
    fi
    
    success "✅ Node.js dependencies installed and assets built"
}

# Setup database and run migrations
setup_database() {
    if [ "$SKIP_MIGRATIONS" = "true" ]; then
        info "Skipping database migrations"
        return 0
    fi
    
    log "🗄️ Setting up database..."
    
    cd "$PROJECT_ROOT"
    
    # Test database connection
    execute "php artisan migrate:status" "Testing database connection"
    
    # Run migrations
    if [ "$ENVIRONMENT" = "production" ]; then
        execute "php artisan migrate --force" "Running database migrations (production)"
    else
        execute "php artisan migrate" "Running database migrations"
    fi
    
    # Run seeders if not skipped
    if [ "$SKIP_SEEDERS" = "false" ]; then
        if [ "$ENVIRONMENT" = "production" ]; then
            execute "php artisan db:seed --force" "Running database seeders (production)"
        else
            execute "php artisan db:seed" "Running database seeders"
        fi
    fi
    
    success "✅ Database setup completed"
}

# Setup Filament admin panel
setup_filament() {
    log "🎛️ Setting up Filament admin panel..."
    
    cd "$PROJECT_ROOT"
    
    # Clear caches before Filament setup
    execute "php artisan config:clear" "Clearing configuration cache"
    execute "php artisan cache:clear" "Clearing application cache"
    
    # Generate Filament resources if needed
    if [ -f "artisan" ]; then
        # Check if shield package is installed and generate permissions
        if composer show | grep -q "bezhansalleh/filament-shield"; then
            execute "php artisan shield:generate --all" "Generating Filament Shield permissions"
        fi
        
        # Install Filament if not already installed
        if ! php artisan list | grep -q "filament:"; then
            execute "php artisan filament:install --panels" "Installing Filament panels"
        fi
    fi
    
    success "✅ Filament admin panel configured"
}

# Create storage link
create_storage_link() {
    log "🔗 Creating storage symbolic link..."
    
    cd "$PROJECT_ROOT"
    
    execute "php artisan storage:link" "Creating storage symbolic link"
    
    success "✅ Storage link created"
}

# Fix file and directory permissions
fix_permissions() {
    if [ "$SKIP_PERMISSIONS" = "true" ]; then
        info "Skipping file permissions setup"
        return 0
    fi
    
    log "🔒 Setting up file permissions..."
    
    # Set ownership
    execute "chown -R $PROJECT_USER:$PROJECT_GROUP $PROJECT_ROOT" "Setting project ownership"
    
    # Set directory permissions
    execute "find $PROJECT_ROOT -type d -exec chmod 755 {} \\;" "Setting directory permissions"
    
    # Set file permissions
    execute "find $PROJECT_ROOT -type f -exec chmod 644 {} \\;" "Setting file permissions"
    
    # Set specific permissions for Laravel directories
    execute "chmod -R 775 $PROJECT_ROOT/storage" "Setting storage permissions"
    execute "chmod -R 775 $PROJECT_ROOT/bootstrap/cache" "Setting bootstrap cache permissions"
    
    # Make artisan executable
    execute "chmod +x $PROJECT_ROOT/artisan" "Making artisan executable"
    
    # Set web server permissions
    execute "chown -R $NGINX_USER:$PROJECT_GROUP $PROJECT_ROOT/storage" "Setting web server storage permissions"
    execute "chown -R $NGINX_USER:$PROJECT_GROUP $PROJECT_ROOT/bootstrap/cache" "Setting web server cache permissions"
    
    success "✅ File permissions configured"
}

# Optimize Laravel application for production
optimize_application() {
    log "⚡ Optimizing Laravel application..."
    
    cd "$PROJECT_ROOT"
    
    if [ "$ENVIRONMENT" = "production" ]; then
        # Production optimizations
        execute "php artisan config:cache" "Caching configuration"
        execute "php artisan route:cache" "Caching routes"
        execute "php artisan view:cache" "Caching views"
        execute "php artisan event:cache" "Caching events"
        
        # Optimize Composer autoloader
        execute "composer dump-autoload --optimize" "Optimizing Composer autoloader"
        
        # Cache Filament components
        if php artisan list | grep -q "filament:"; then
            execute "php artisan filament:cache-components" "Caching Filament components"
            execute "php artisan icons:cache" "Caching icons"
        fi
    else
        # Development - clear caches
        execute "php artisan config:clear" "Clearing configuration cache"
        execute "php artisan route:clear" "Clearing route cache"
        execute "php artisan view:clear" "Clearing view cache"
        execute "php artisan cache:clear" "Clearing application cache"
    fi
    
    success "✅ Laravel application optimized"
}

# Create backup before deployment
create_backup() {
    if [ "$BACKUP_BEFORE_DEPLOY" = "false" ]; then
        info "Skipping backup creation"
        return 0
    fi
    
    log "💾 Creating backup before deployment..."
    
    local backup_dir="/var/backups/pkki-itera"
    local backup_name="backup-$(date +%Y%m%d-%H%M%S)"
    local backup_path="$backup_dir/$backup_name"
    
    execute "mkdir -p $backup_dir" "Creating backup directory"
    
    # Backup project files (excluding vendor and node_modules)
    execute "tar -czf $backup_path-files.tar.gz --exclude='vendor' --exclude='node_modules' --exclude='.git' -C $(dirname $PROJECT_ROOT) $(basename $PROJECT_ROOT)" "Backing up project files"
    
    # Backup database if possible
    if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ]; then
        local db_backup="$backup_path-database.sql"
        if command -v pg_dump &> /dev/null; then
            execute "PGPASSWORD='$DB_PASSWORD' pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USERNAME $DB_DATABASE > $db_backup" "Backing up database"
        else
            warn "pg_dump not available, skipping database backup"
        fi
    fi
    
    success "✅ Backup created: $backup_path"
}

# Run health checks
run_health_check() {
    log "🏥 Running health checks..."
    
    cd "$PROJECT_ROOT"
    
    # Check Laravel configuration
    execute "php artisan config:show app.name" "Checking Laravel configuration"
    
    # Check database connection
    execute "php artisan migrate:status | head -5" "Checking database connection"
    
    # Check storage permissions
    execute "php artisan storage:link" "Verifying storage link"
    
    # Check if web server is running
    if command -v nginx &> /dev/null; then
        execute "systemctl is-active nginx" "Checking Nginx status"
    fi
    
    # Check PHP-FPM
    execute "systemctl is-active php${PHP_VERSION}-fpm" "Checking PHP-FPM status"
    
    # Check Redis if configured
    if systemctl is-active redis-server &> /dev/null; then
        execute "redis-cli ping" "Checking Redis connection"
    fi
    
    # Test application response if domain is configured
    if [ -n "$DOMAIN" ]; then
        local protocol="http"
        if [ "$SKIP_SSL" = "false" ]; then
            protocol="https"
        fi
        
        local response_code=$(curl -s -o /dev/null -w "%{http_code}" "$protocol://$DOMAIN" || echo "000")
        if [ "$response_code" = "200" ]; then
            success "✅ Application is responding correctly"
        else
            warn "Application returned HTTP $response_code"
        fi
    fi
    
    success "✅ Health checks completed"
}

# Cleanup function
cleanup() {
    log "🧹 Running cleanup operations..."
    
    cd "$PROJECT_ROOT"
    
    # Clear all caches
    execute "php artisan config:clear" "Clearing configuration cache"
    execute "php artisan route:clear" "Clearing route cache"
    execute "php artisan view:clear" "Clearing view cache"
    execute "php artisan cache:clear" "Clearing application cache"
    
    # Clear compiled files
    execute "php artisan clear-compiled" "Clearing compiled files"
    
    # Remove temporary files
    execute "rm -rf $PROJECT_ROOT/storage/logs/*.log" "Removing old log files"
    execute "rm -rf /tmp/nginx-*.conf" "Removing temporary nginx configs"
    execute "rm -rf /tmp/php-fpm-*.conf" "Removing temporary php-fpm configs"
    
    success "✅ Cleanup completed"
}

# Show deployment summary
show_summary() {
    echo
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                      🚀 DEPLOYMENT SUMMARY                          ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${GREEN}✅ PKKI ITERA deployment completed successfully!${NC}"
    echo
    echo -e "${BOLD}Environment:${NC} $ENVIRONMENT"
    if [ -n "$DOMAIN" ]; then
        local protocol="http"
        if [ "$SKIP_SSL" = "false" ]; then
            protocol="https"
        fi
        echo -e "${BOLD}Application URL:${NC} $protocol://$DOMAIN"
        echo -e "${BOLD}Admin Panel:${NC} $protocol://$DOMAIN/admin"
    fi
    if [ -n "$SERVER_IP" ]; then
        echo -e "${BOLD}Server IP:${NC} $SERVER_IP"
    fi
    echo
    echo -e "${YELLOW}📝 Next Steps:${NC}"
    echo "1. Test the application in your browser"
    echo "2. Create an admin user: php artisan make:filament-user"
    echo "3. Configure any additional services as needed"
    echo "4. Set up monitoring and log rotation"
    echo "5. Configure backups for production data"
    echo
    if [ "$ENVIRONMENT" = "production" ]; then
        echo -e "${RED}🔒 Security Reminders:${NC}"
        echo "• Change default passwords"
        echo "• Review firewall settings"
        echo "• Enable fail2ban for additional security"
        echo "• Set up regular security updates"
        echo
    fi
    echo -e "${BLUE}📚 Documentation: $PROJECT_ROOT/docs/${NC}"
    echo -e "${BLUE}🐛 Logs: $PROJECT_ROOT/storage/logs/${NC}"
    echo
}

# Main deployment function
main() {
    # Parse command line arguments
    parse_arguments "$@"
    
    # Show script header
    echo -e "${CYAN}${SCRIPT_NAME} v${SCRIPT_VERSION}${NC}"
    echo -e "${BLUE}Complete production deployment for PKKI ITERA${NC}"
    echo
    
    # Check requirements
    check_requirements
    check_root
    
    # Validate configuration
    if [ "$SETUP_NGINX_ONLY" = "true" ] || [ "$SETUP_SSL_ONLY" = "true" ]; then
        if [ -z "$DOMAIN" ]; then
            error "❌ Domain name is required for Nginx/SSL setup"
            exit 1
        fi
    fi
    
    # Show what will be done
    if [ "$DRY_RUN" = "true" ]; then
        info "🔍 DRY RUN MODE - No changes will be made"
        echo
    fi
    
    # Confirm deployment
    if [ "$CLEANUP_ONLY" = "false" ] && [ "$SETUP_NGINX_ONLY" = "false" ] && [ "$SETUP_SSL_ONLY" = "false" ]; then
        confirm_action "This will deploy PKKI ITERA application to $ENVIRONMENT environment."
    fi
    
    # Handle specific operations
    if [ "$CLEANUP_ONLY" = "true" ]; then
        cleanup
        success "🧹 Cleanup completed"
        exit 0
    fi
    
    if [ "$SETUP_NGINX_ONLY" = "true" ]; then
        install_nginx
        setup_nginx
        success "🌐 Nginx setup completed"
        exit 0
    fi
    
    if [ "$SETUP_SSL_ONLY" = "true" ]; then
        setup_ssl
        success "🔒 SSL setup completed"
        exit 0
    fi
    
    # Main deployment process
    log "🚀 Starting deployment process..."
    
    # Create backup
    create_backup
    
    # System setup
    install_system_dependencies
    install_php
    install_composer
    install_nodejs
    install_nginx
    install_redis
    
    # Application setup
    setup_environment
    install_php_dependencies
    install_node_dependencies
    setup_database
    setup_filament
    create_storage_link
    
    # Server configuration
    setup_nginx
    setup_ssl
    configure_https_session
    
    # Optimization and security
    fix_permissions
    optimize_application
    
    # Final checks
    run_health_check
    
    # Show summary
    show_summary
    
    success "🎉 Deployment completed successfully!"
}

# Run main function with all arguments
main "$@"
