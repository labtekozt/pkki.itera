#!/bin/bash

# PKKI ITERA - Quick Server Setup for Ubuntu/Debian
# Installs all required packages for Laravel deployment

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"; }
error() { echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"; }

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root (use sudo)"
    fi
}

# Update system
update_system() {
    log "Updating system packages..."
    apt update && apt upgrade -y
    apt install -y curl wget unzip git software-properties-common ca-certificates gnupg lsb-release
}

# Install PHP
install_php() {
    log "Installing PHP..."
    
    # Detect Ubuntu version for compatibility
    local ubuntu_version=$(lsb_release -rs 2>/dev/null || echo "20.04")
    local ubuntu_codename=$(lsb_release -cs 2>/dev/null || echo "focal")
    
    if [[ "$ubuntu_codename" == "oracular" ]] || [[ $(echo "$ubuntu_version" | cut -d. -f1) -ge 24 ]]; then
        warn "Ubuntu $ubuntu_version detected - using system PHP packages"
        apt install -y php php-fpm php-cli php-common php-curl php-zip \
            php-gd php-xml php-mbstring php-intl php-bcmath \
            php-pgsql php-mysql php-redis php-imagick
    else
        info "Adding Ondrej PHP repository..."
        add-apt-repository ppa:ondrej/php -y
        apt update
        apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-curl php8.3-zip \
            php8.3-gd php8.3-xml php8.3-mbstring php8.3-intl php8.3-bcmath \
            php8.3-pgsql php8.3-mysql php8.3-redis php8.3-imagick
    fi
    
    # Configure PHP for Laravel
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_INI="/etc/php/$PHP_VERSION/fpm/php.ini"
    
    sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' "$PHP_INI"
    sed -i 's/post_max_size = .*/post_max_size = 100M/' "$PHP_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    
    systemctl enable php$PHP_VERSION-fpm
    systemctl restart php$PHP_VERSION-fpm
    
    log "PHP $PHP_VERSION installed and configured"
}

# Install Composer
install_composer() {
    log "Installing Composer..."
    
    if ! command -v composer &> /dev/null; then
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi
    
    log "Composer installed: $(composer --version)"
}

# Install Node.js
install_nodejs() {
    log "Installing Node.js..."
    
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
    
    # Install global packages
    npm install -g npm@latest
    
    log "Node.js installed: $(node --version)"
}

# Install Nginx
install_nginx() {
    log "Installing and configuring Nginx..."
    
    apt install -y nginx
    
    # Basic security configuration
    cat > /etc/nginx/nginx.conf << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    # Basic Settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    
    # File Types
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;
    
    # Gzip Settings
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        application/atom+xml
        application/javascript
        application/json
        application/ld+json
        application/manifest+json
        application/rss+xml
        application/vnd.geo+json
        application/vnd.ms-fontobject
        application/x-font-ttf
        application/x-web-app-manifest+json
        application/xhtml+xml
        application/xml
        font/opentype
        image/bmp
        image/svg+xml
        image/x-icon
        text/cache-manifest
        text/css
        text/plain
        text/vcard
        text/vnd.rim.location.xloc
        text/vtt
        text/x-component
        text/x-cross-domain-policy;
    
    # Virtual Host Configs
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF
    
    # Remove default site
    rm -f /etc/nginx/sites-enabled/default
    
    systemctl enable nginx
    systemctl restart nginx
    
    log "Nginx installed and configured"
}

# Install database (PostgreSQL)
install_postgresql() {
    log "Installing PostgreSQL..."
    
    apt install -y postgresql postgresql-contrib
    
    systemctl enable postgresql
    systemctl start postgresql
    
    log "PostgreSQL installed"
    warn "Remember to configure PostgreSQL user and database if using local DB"
}

# Install Redis
install_redis() {
    log "Installing Redis..."
    
    apt install -y redis-server
    
    # Configure Redis
    sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
    sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    
    systemctl enable redis-server
    systemctl restart redis-server
    
    log "Redis installed and configured"
}

# Install SSL tools
install_ssl_tools() {
    log "Installing SSL tools (Certbot)..."
    
    apt install -y certbot python3-certbot-nginx
    
    log "Certbot installed"
    info "Use: certbot --nginx -d your-domain.com to get SSL certificate"
}

# Install monitoring tools
install_monitoring() {
    log "Installing monitoring tools..."
    
    apt install -y htop iotop nethogs fail2ban ufw
    
    # Configure UFW firewall
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 'Nginx Full'
    
    warn "Firewall configured but not enabled. Run 'ufw enable' when ready"
    
    log "Monitoring tools installed"
}

# Setup log rotation
setup_logrotate() {
    log "Setting up log rotation..."
    
    cat > /etc/logrotate.d/laravel << 'EOF'
/var/www/*/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    copytruncate
}
EOF
    
    log "Log rotation configured"
}

# Create example Nginx site
create_nginx_example() {
    log "Creating example Nginx configuration..."
    
    cat > /etc/nginx/sites-available/laravel-example << 'EOF'
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/your-app/public;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Optimizations
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_read_timeout 300;
    }
    
    # Static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.(env|git) {
        deny all;
    }
}
EOF
    
    info "Example Nginx config created at: /etc/nginx/sites-available/laravel-example"
}

# Show summary
show_summary() {
    echo
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🚀 SERVER SETUP COMPLETED                        ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${YELLOW}Installed Software:${NC}"
    echo "• PHP $(php --version | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)"
    echo "• Composer $(composer --version | cut -d' ' -f3)"
    echo "• Node.js $(node --version)"
    echo "• Nginx $(nginx -v 2>&1 | cut -d'/' -f2)"
    echo "• PostgreSQL $(sudo -u postgres psql -c 'SELECT version();' | head -3 | tail -1 | cut -d' ' -f3)"
    echo "• Redis $(redis-server --version | cut -d' ' -f3)"
    echo
    echo -e "${BLUE}Next Steps:${NC}"
    echo "1. Clone your Laravel application to /var/www/your-app"
    echo "2. Configure Nginx site: cp /etc/nginx/sites-available/laravel-example /etc/nginx/sites-available/your-domain"
    echo "3. Enable site: ln -s /etc/nginx/sites-available/your-domain /etc/nginx/sites-enabled/"
    echo "4. Get SSL certificate: certbot --nginx -d your-domain.com"
    echo "5. Run application setup: ./scripts/setup.sh"
    echo "6. Deploy application: ./scripts/deploy.sh production"
    echo
    echo -e "${YELLOW}Security Reminders:${NC}"
    echo "• Enable firewall: ufw enable"
    echo "• Change default passwords"
    echo "• Configure fail2ban"
    echo "• Set up regular backups"
    echo "• Update system regularly"
}

# Main function
main() {
    echo -e "${BLUE}PKKI ITERA Server Setup Script${NC}"
    echo -e "${BLUE}Setting up Ubuntu/Debian server for Laravel deployment${NC}"
    echo
    
    check_root
    
    echo -e "${YELLOW}This will install:${NC}"
    echo "• PHP 8.3+ with extensions"
    echo "• Composer"
    echo "• Node.js 20.x"
    echo "• Nginx"
    echo "• PostgreSQL"
    echo "• Redis"
    echo "• SSL tools (Certbot)"
    echo "• Monitoring tools"
    echo
    
    read -p "Continue with installation? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Installation cancelled"
    fi
    
    # Run installation steps
    update_system
    install_php
    install_composer
    install_nodejs
    install_nginx
    install_postgresql
    install_redis
    install_ssl_tools
    install_monitoring
    setup_logrotate
    create_nginx_example
    
    show_summary
    
    log "Server setup completed successfully! 🎉"
}

main "$@"
