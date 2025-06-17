#!/bin/bash

# PKKI ITERA - Complete One-Click Deployment Script
# Script deployment lengkap untuk mengatur semuanya dari awal hingga selesai
# Usage: sudo ./deploy-complete.sh

set -e

# Colors untuk output yang lebih jelas
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Banner
clear
echo -e "${CYAN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    PKKI ITERA COMPLETE DEPLOYMENT               ║"
echo "║                        One-Click Setup                          ║"
echo "║                    Domain: hki.proyekai.com                      ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Logging function
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

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "Script ini harus dijalankan sebagai root atau dengan sudo"
    echo "Usage: sudo ./deploy-complete.sh"
    exit 1
fi

# Get server information
SERVER_IP=$(curl -s http://checkip.amazonaws.com || echo "Unknown")
log "Server IP: $SERVER_IP"

# Configuration variables
DOMAIN="hki.proyekai.com"
WWW_DOMAIN="www.hki.proyekai.com"
PROJECT_DIR="/var/www/pkki-itera"
DB_NAME="pkki_itera"
DB_USER="pkki_user"
DB_PASS="PKKIitera2024!"
ADMIN_EMAIL="admin@hki.itera.ac.id"
ADMIN_PASS="admin123"

echo ""
echo -e "${BLUE}📋 Configuration Summary:${NC}"
echo "   Domain: $DOMAIN"
echo "   Project Path: $PROJECT_DIR"
echo "   Database: $DB_NAME"
echo "   Admin Email: $ADMIN_EMAIL"
echo ""

read -p "Apakah konfigurasi ini sudah benar? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment dibatalkan."
    exit 1
fi

# ============================================================================
# PHASE 1: SYSTEM PREPARATION
# ============================================================================

log "🔧 PHASE 1: System Preparation"

# Update system
log "📦 Updating system packages..."
apt update && apt upgrade -y

# Install required packages
log "⚙️ Installing required packages..."
apt install -y software-properties-common ca-certificates lsb-release apt-transport-https

# Add PHP repository
log "📦 Adding PHP 8.2 repository..."
add-apt-repository ppa:ondrej/php -y
apt update

# Install main packages
log "📦 Installing main packages..."
apt install -y \
    nginx \
    mysql-server \
    php8.2 \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-sqlite3 \
    composer \
    git \
    unzip \
    curl \
    wget \
    certbot \
    python3-certbot-nginx \
    ufw \
    htop \
    nano \
    bc

# Verify Node.js and npm installation
log "🔧 Verifying Node.js installation..."
node --version
npm --version

# Fix npm if corrupted
log "🔧 Fixing npm installation if needed..."
npm install -g npm@latest || {
    warn "npm update failed, reinstalling..."
    curl -L https://www.npmjs.com/install.sh | sh
}

# Verify installations
log "✅ Verifying installations..."
php --version | head -1
composer --version | head -1
node --version | head -1
npm --version | head -1

# ============================================================================
# PHASE 2: SERVICES CONFIGURATION
# ============================================================================

log "🔧 PHASE 2: Services Configuration"

# Start and enable services
log "🔧 Starting and enabling services..."
systemctl enable nginx mysql php8.2-fpm
systemctl start nginx mysql php8.2-fpm

# Wait for MySQL to be ready
log "⏳ Waiting for MySQL to start..."
sleep 5

# Function to setup MySQL safely
setup_mysql() {
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        log "🔄 MySQL setup attempt $attempt/$max_attempts"
        
        # Try different authentication methods
        if mysql -u root -e "SELECT 1;" 2>/dev/null; then
            log "✅ MySQL accessible without password"
            return 0
        elif mysql -u root -proot_secure_password -e "SELECT 1;" 2>/dev/null; then
            log "✅ MySQL accessible with set password"
            return 0
        elif sudo mysql -u root -e "SELECT 1;" 2>/dev/null; then
            log "✅ MySQL accessible with sudo"
            return 0
        else
            warn "❌ MySQL connection failed, attempt $attempt"
            systemctl restart mysql
            sleep 5
            ((attempt++))
        fi
    done
    
    error "Failed to connect to MySQL after $max_attempts attempts"
    return 1
}

# Setup MySQL
if ! setup_mysql; then
    error "MySQL setup failed"
    exit 1
fi

# Secure MySQL installation
log "🔒 Securing MySQL installation..."

# Try different connection methods to secure MySQL
if mysql -u root -e "SELECT 1;" 2>/dev/null; then
    log "Securing MySQL with no existing password..."
    mysql -u root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root_secure_password';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF
elif sudo mysql -u root -e "SELECT 1;" 2>/dev/null; then
    log "Securing MySQL with sudo access..."
    sudo mysql -u root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root_secure_password';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF
else
    log "MySQL already secured or password set"
fi

# Create application database
log "🗄️ Creating application database..."

# Try with password first, then fallback methods
if mysql -u root -proot_secure_password -e "SELECT 1;" 2>/dev/null; then
    log "Creating database with password authentication..."
    mysql -u root -proot_secure_password <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
elif mysql -u root -e "SELECT 1;" 2>/dev/null; then
    log "Creating database with no password authentication..."
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root_secure_password';
FLUSH PRIVILEGES;
EOF
elif sudo mysql -u root -e "SELECT 1;" 2>/dev/null; then
    log "Creating database with sudo authentication..."
    sudo mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root_secure_password';
FLUSH PRIVILEGES;
EOF
else
    error "Unable to connect to MySQL with any method"
    exit 1
fi

success "Database created successfully"

# ============================================================================
# PHASE 3: APPLICATION DEPLOYMENT
# ============================================================================

log "🔧 PHASE 3: Application Deployment"

# Prepare project directory
log "📁 Preparing project directory..."
mkdir -p $PROJECT_DIR
chown -R $USER:$USER $PROJECT_DIR

# Clone or copy application
if [ -d "/tmp/pkki-itera" ]; then
    log "📁 Copying application from /tmp..."
    cp -r /tmp/pkki-itera/* $PROJECT_DIR/
elif [ -d "$(pwd)" ] && [ -f "$(pwd)/artisan" ]; then
    log "📁 Copying application from current directory..."
    cp -r $(pwd)/* $PROJECT_DIR/
else
    log "📁 Cloning application from GitHub..."
    git clone https://github.com/labtekozt/pkki.itera.git $PROJECT_DIR
fi

cd $PROJECT_DIR

# Install PHP dependencies
log "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies and build assets
log "📦 Installing Node.js dependencies..."

# Check Node.js/npm availability and fix if needed
if ! command -v node &> /dev/null || ! command -v npm &> /dev/null; then
    error "Node.js or npm not found, reinstalling..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

# Clean npm cache and install dependencies
log "🧹 Cleaning npm cache..."
npm cache clean --force || true

# Remove node_modules if exists and reinstall
if [ -d "node_modules" ]; then
    log "🗑️ Removing existing node_modules..."
    rm -rf node_modules
fi

if [ -f "package-lock.json" ]; then
    log "🗑️ Removing existing package-lock.json..."
    rm -f package-lock.json
fi

# Install dependencies with retry mechanism
log "📦 Installing npm dependencies with retry..."
npm_install_with_retry() {
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        log "📦 npm install attempt $attempt/$max_attempts"
        
        if npm install --production=false --legacy-peer-deps; then
            log "✅ npm install successful"
            return 0
        else
            warn "❌ npm install failed, attempt $attempt"
            if [ $attempt -lt $max_attempts ]; then
                log "🔄 Retrying in 5 seconds..."
                sleep 5
                npm cache clean --force || true
            fi
            ((attempt++))
        fi
    done
    
    error "npm install failed after $max_attempts attempts"
    return 1
}

if ! npm_install_with_retry; then
    error "Failed to install npm dependencies"
    exit 1
fi

log "🎨 Building React Inertia frontend..."
npm run build

# ============================================================================
# PHASE 4: ENVIRONMENT CONFIGURATION
# ============================================================================

log "🔧 PHASE 4: Environment Configuration"

# Create environment file
log "⚙️ Creating environment configuration..."
cp .env.example .env

# Configure environment variables
cat > .env << EOF
# Application Configuration
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://$DOMAIN
APP_LOCALE=id

# Log Configuration
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

# Session & Cache Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail Configuration (Update with your SMTP settings)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@$DOMAIN"
MAIL_FROM_NAME="PKKI ITERA"

# SSO ITERA Configuration
SSO_ITERA_CLIENT_ID=
SSO_ITERA_CLIENT_SECRET=
SSO_ITERA_REDIRECT_URI=https://$DOMAIN/login/sso-itera/callback
SSO_ITERA_URL=https://sso.itera.ac.id
SSO_ITERA_API_URL=https://sso.itera.ac.id/api
SSO_ITERA_SCOPE="profile email"

# Filesystem Configuration
FILESYSTEM_DISK=local

# Broadcasting Configuration
BROADCAST_DRIVER=log

# Vite Configuration
VITE_APP_NAME="\${APP_NAME}"

# Additional Production Configuration
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false

# File Upload Configuration
MAX_FILE_SIZE=10240
ALLOWED_FILE_TYPES=pdf,doc,docx,jpg,jpeg,png

# Security Configuration
SECURE_HEADERS=true
FORCE_HTTPS=true
SANCTUM_STATEFUL_DOMAINS=$DOMAIN
SESSION_DOMAIN=.$DOMAIN

# Shield Configuration
FILAMENT_SHIELD_CACHE_TTL=3600

# Media Library Configuration
MEDIA_DISK=public
EOF

# Generate application key
log "🔑 Generating application key..."
php artisan key:generate --force

# ============================================================================
# PHASE 5: DATABASE SETUP
# ============================================================================

log "🔧 PHASE 5: Database Setup"

# Create required directories
log "📁 Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/public
mkdir -p bootstrap/cache

# Set initial permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run migrations
log "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database
log "🌱 Seeding database..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=SubmissionTypeSeeder --force
php artisan db:seed --class=WorkflowStageSeeder --force
php artisan db:seed --class=UsersTableSeeder --force

# Create storage link
log "🔗 Creating storage link..."
php artisan storage:link

# Generate Filament Shield
log "🛡️ Generating Filament Shield..."
php artisan shield:generate --all

success "Database setup completed"

# ============================================================================
# PHASE 6: WEB SERVER CONFIGURATION
# ============================================================================

log "🔧 PHASE 6: Web Server Configuration"

# Create Nginx configuration
log "🌐 Creating Nginx configuration..."
cat > /etc/nginx/sites-available/pkki-itera << 'EOF'
# HTTP Server (will redirect to HTTPS after SSL setup)
server {
    listen 3003;
    server_name hki.proyekai.com www.hki.proyekai.com;
    root /var/www/pkki-itera/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\. {
        deny all;
    }

    location /storage {
        alias /var/www/pkki-itera/storage/app/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 50M;
    client_body_timeout 60s;
    client_header_timeout 60s;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t && systemctl reload nginx

success "Nginx configuration completed"

# ============================================================================
# PHASE 7: SSL CERTIFICATE SETUP
# ============================================================================

log "🔧 PHASE 7: SSL Certificate Setup"

# Check DNS configuration
log "🔍 Checking DNS configuration..."
echo "Server IP: $SERVER_IP"
echo "Domain should point to this IP address"
echo ""

# Check if domain resolves to this server
DOMAIN_IP=$(dig +short $DOMAIN || echo "")
if [ "$DOMAIN_IP" = "$SERVER_IP" ]; then
    success "DNS correctly configured for $DOMAIN"
else
    warn "DNS might not be configured correctly"
    warn "Domain $DOMAIN resolves to: $DOMAIN_IP"
    warn "Server IP is: $SERVER_IP"
    echo ""
    read -p "Continue with SSL setup anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        warn "Skipping SSL setup. You can run it manually later:"
        warn "sudo certbot --nginx -d $DOMAIN -d $WWW_DOMAIN"
        SSL_SKIPPED=true
    fi
fi

if [ "$SSL_SKIPPED" != "true" ]; then
    # Setup SSL certificate
    log "🔒 Setting up SSL certificate..."
    certbot --nginx -d $DOMAIN -d $WWW_DOMAIN \
        --non-interactive \
        --agree-tos \
        --email admin@$DOMAIN \
        --redirect || {
        warn "SSL certificate setup failed. Continuing without SSL..."
        SSL_FAILED=true
    }

    # Check if SSL was successful
    if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ] && [ "$SSL_FAILED" != "true" ]; then
        success "SSL certificate installed successfully"
        
        # Update app URL to HTTPS
        sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env
        
        # Setup auto-renewal
        (crontab -l 2>/dev/null; echo "0 2 * * * certbot renew --quiet && systemctl reload nginx") | crontab -
        success "SSL auto-renewal configured"
        
        SSL_ENABLED=true
    else
        warn "SSL setup failed, running on HTTP only"
        sed -i "s|APP_URL=.*|APP_URL=http://$DOMAIN:3003|" .env
    fi
else
    warn "SSL setup skipped, running on HTTP only"
    sed -i "s|APP_URL=.*|APP_URL=http://$DOMAIN:3003|" .env
fi

# ============================================================================
# PHASE 8: SECURITY & FIREWALL
# ============================================================================

log "🔧 PHASE 8: Security & Firewall Configuration"

# Configure firewall
log "🛡️ Configuring firewall..."
ufw --force enable
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 3003/tcp
ufw allow 3443/tcp
ufw reload

success "Firewall configured"

# Set final permissions
log "🔒 Setting final permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/storage
chmod -R 775 $PROJECT_DIR/bootstrap/cache
chmod -R 755 $PROJECT_DIR/public

# ============================================================================
# PHASE 9: OPTIMIZATION
# ============================================================================

log "🔧 PHASE 9: Application Optimization"

# Clear caches first
log "🧹 Clearing existing caches..."
sudo -u www-data php artisan config:clear || true
sudo -u www-data php artisan route:clear || true
sudo -u www-data php artisan view:clear || true
sudo -u www-data php artisan cache:clear || true

# Optimize for production (without view cache for Filament compatibility)
log "⚡ Optimizing for production..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

log "ℹ️ Skipping view cache (Filament compatibility)"

# Setup cron job for Laravel scheduler
log "⏰ Setting up cron job..."
(crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

success "Optimization completed"

# ============================================================================
# PHASE 10: ADMIN USER CREATION
# ============================================================================


# ============================================================================
# PHASE 11: VERIFICATION
# ============================================================================

log "🔧 PHASE 11: Deployment Verification"

# Test application
log "🧪 Testing application..."

# Check services
services=("nginx" "php8.2-fpm" "mysql")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        success "$service is running"
    else
        error "$service is not running"
    fi
done

# Test HTTP response
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://$DOMAIN:3003 || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    success "HTTP response: $HTTP_STATUS"
else
    error "HTTP not responding (Status: $HTTP_STATUS)"
fi

# Test HTTPS if enabled
if [ "$SSL_ENABLED" = "true" ]; then
    HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN || echo "000")
    if [ "$HTTPS_STATUS" = "200" ]; then
        success "HTTPS response: $HTTPS_STATUS"
    else
        warn "HTTPS test failed (Status: $HTTPS_STATUS)"
    fi
fi

# Check Laravel
if php artisan --version >/dev/null 2>&1; then
    success "Laravel is working"
else
    error "Laravel is not working"
fi

# Check database
if php artisan migrate:status >/dev/null 2>&1; then
    success "Database connection working"
else
    error "Database connection failed"
fi

# ============================================================================
# DEPLOYMENT COMPLETE
# ============================================================================

clear
echo -e "${GREEN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    🎉 DEPLOYMENT COMPLETED! 🎉                  ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${CYAN}📊 Deployment Summary:${NC}"
echo "===================="
echo "Application: PKKI ITERA"
echo "Domain: $DOMAIN"
echo "Environment: Production"
echo "Database: MySQL ($DB_NAME)"
echo "SSL: $([ "$SSL_ENABLED" = "true" ] && echo "Enabled" || echo "Disabled")"
echo "Server IP: $SERVER_IP"
echo ""

echo -e "${CYAN}🔗 Access URLs:${NC}"
if [ "$SSL_ENABLED" = "true" ]; then
    echo "🔒 Main Site: https://$DOMAIN"
    echo "🔒 Admin Panel: https://$DOMAIN/admin"
    echo "📝 HTTP redirects to HTTPS"
else
    echo "🌐 Main Site: http://$DOMAIN:3003"
    echo "🌐 Admin Panel: http://$DOMAIN:3003/admin"
fi
echo "🔗 Direct IP: http://$SERVER_IP:3003"
echo ""

echo -e "${CYAN}👤 Admin Credentials:${NC}"
echo "Email: $ADMIN_EMAIL"
echo "Password: $ADMIN_PASS"
echo ""

echo -e "${YELLOW}⚠️ Important Next Steps:${NC}"
echo "1. 🔑 Change admin password immediately"
echo "2. 📧 Configure email settings in .env"
echo "3. 🔒 $([ "$SSL_ENABLED" != "true" ] && echo "Setup SSL: sudo certbot --nginx -d $DOMAIN" || echo "SSL is already configured")"
echo "4. 📊 Monitor logs: tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo "5. 🔄 Setup regular backups"
echo ""

echo -e "${CYAN}📋 System Information:${NC}"
echo "PHP Version: $(php -r 'echo PHP_VERSION;')"
echo "Node.js Version: $(node --version)"
echo "Nginx: $(nginx -v 2>&1 | head -1)"
echo "MySQL: $(mysql --version | head -1)"
echo ""

echo -e "${CYAN}🛠️ Useful Commands:${NC}"
echo "View logs: tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo "Restart services: sudo systemctl restart nginx php8.2-fpm"
echo "Update app: cd $PROJECT_DIR && git pull && composer install --no-dev"
echo "Clear cache: cd $PROJECT_DIR && php artisan cache:clear"
echo ""

if [ "$SSL_ENABLED" = "true" ]; then
    success "🔒 Your PKKI ITERA application is now live with SSL encryption!"
else
    success "🌐 Your PKKI ITERA application is now live!"
fi

success "🎉 DEPLOYMENT SELESAI! Aplikasi PKKI ITERA siap digunakan!"

echo ""
echo -e "${PURPLE}==========================================${NC}"
echo -e "${WHITE}Script by: PKKI ITERA Development Team${NC}"
echo -e "${WHITE}Date: $(date)${NC}"
echo -e "${PURPLE}==========================================${NC}"
