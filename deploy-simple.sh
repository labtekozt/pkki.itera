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

# Setup Node.js/npm (detect existing NVM installation and use it, avoid system installation)
setup_nodejs() {
    log "🔧 Setting up Node.js and npm..."
    
    # Check if Node.js is already available in system PATH
    if command -v node &> /dev/null && command -v npm &> /dev/null; then
        local node_version=$(node --version 2>/dev/null || echo "unknown")
        local npm_version=$(npm --version 2>/dev/null || echo "unknown")
        log "✅ Node.js and npm already available in system PATH"
        log "   Node.js: $node_version"
        log "   npm: $npm_version"
        return 0
    fi
    
    # Comprehensive NVM detection - check multiple common locations
    local potential_nvm_paths=(
        "/home/$SUDO_USER/.nvm"
        "/root/.nvm"
        "/partikelxyz/.nvm"
        "/home/partikelxyz/.nvm"
        "/home/ubuntu/.nvm"
        "/home/ec2-user/.nvm"
        "/home/debian/.nvm"
        "/home/centos/.nvm"
        "$HOME/.nvm"
    )
    
    # Add dynamic user home directories
    if [ -n "$SUDO_USER" ] && [ "$SUDO_USER" != "root" ]; then
        potential_nvm_paths+=("/home/$SUDO_USER/.nvm")
    fi
    
    # Add all user home directories that might have NVM
    for user_home in /home/*/; do
        if [ -d "$user_home" ]; then
            local username=$(basename "$user_home")
            potential_nvm_paths+=("${user_home}.nvm")
        fi
    done
    
    local node_found=false
    local best_node_path=""
    local best_npm_path=""
    local best_version=""
    
    log "🔍 Searching for existing NVM installations..."
    
    for nvm_path in "${potential_nvm_paths[@]}"; do
        # Skip if path doesn't exist or is not a directory
        if [ ! -d "$nvm_path" ]; then
            continue
        fi
        
        log "   Checking: $nvm_path"
        
        # Check if this is a valid NVM installation
        if [ ! -d "$nvm_path/versions" ] && [ ! -d "$nvm_path/versions/node" ]; then
            log "   ❌ Not a valid NVM installation (no versions directory)"
            continue
        fi
        
        # Find all Node.js versions in this NVM installation
        local versions_dir="$nvm_path/versions/node"
        if [ ! -d "$versions_dir" ]; then
            log "   ❌ No Node.js versions found"
            continue
        fi
        
        # Find the latest/best Node.js version
        local latest_version=""
        local version_count=0
        
        for version_dir in "$versions_dir"/v*; do
            if [ -d "$version_dir" ] && [ -f "$version_dir/bin/node" ] && [ -f "$version_dir/bin/npm" ]; then
                latest_version="$version_dir"
                ((version_count++))
            fi
        done
        
        if [ $version_count -eq 0 ]; then
            log "   ❌ No valid Node.js installations found"
            continue
        fi
        
        # Get the actual latest version (highest version number)
        latest_version=$(find "$versions_dir" -maxdepth 1 -type d -name "v*" 2>/dev/null | sort -V | tail -1)
        
        if [ -n "$latest_version" ] && [ -f "$latest_version/bin/node" ] && [ -f "$latest_version/bin/npm" ]; then
            local node_version_string=$("$latest_version/bin/node" --version 2>/dev/null || echo "unknown")
            local npm_version_string=$("$latest_version/bin/npm" --version 2>/dev/null || echo "unknown")
            
            log "   ✅ Found valid Node.js installation:"
            log "      Path: $latest_version"
            log "      Node.js: $node_version_string"
            log "      npm: $npm_version_string"
            log "      Total versions: $version_count"
            
            # Use the first valid installation found (could be enhanced to pick the best version)
            if [ "$node_found" = false ]; then
                best_node_path="$latest_version/bin/node"
                best_npm_path="$latest_version/bin/npm"
                best_version="$node_version_string"
                node_found=true
                
                log "   🎯 Selected this installation for use"
            fi
        else
            log "   ❌ Found NVM directory but no valid Node.js installation"
        fi
    done
    
    if [ "$node_found" = true ]; then
        log "🔗 Linking selected NVM Node.js installation to system PATH..."
        log "   Selected Node.js: $best_version"
        log "   Node path: $best_node_path"
        log "   npm path: $best_npm_path"
        
        # Remove any existing symlinks first
        rm -f /usr/local/bin/node /usr/local/bin/npm /usr/bin/node /usr/bin/npm 2>/dev/null || true
        
        # Create symlinks to make them available system-wide
        if ln -sf "$best_node_path" /usr/local/bin/node && ln -sf "$best_npm_path" /usr/local/bin/npm; then
            log "   ✅ Created symlinks in /usr/local/bin/"
        else
            warn "   ❌ Failed to create symlinks in /usr/local/bin/, trying /usr/bin/"
        fi
        
        # Also create in /usr/bin for broader compatibility
        if ln -sf "$best_node_path" /usr/bin/node && ln -sf "$best_npm_path" /usr/bin/npm; then
            log "   ✅ Created symlinks in /usr/bin/"
        else
            warn "   ❌ Failed to create symlinks in /usr/bin/"
        fi
        
        # Fix permissions
        chmod +x /usr/local/bin/node /usr/local/bin/npm /usr/bin/node /usr/bin/npm 2>/dev/null || true
        
        # Verify the symlinks work
        if command -v node &> /dev/null && command -v npm &> /dev/null; then
            success "✅ NVM Node.js installation successfully linked to system PATH"
            log "   System Node.js: $(node --version)"
            log "   System npm: $(npm --version)"
            return 0
        else
            error "❌ Symlinks created but Node.js/npm not accessible in PATH"
            return 1
        fi
    fi
    
    # Only install system-wide if specifically requested or no NVM found
    warn "⚠️  No valid NVM installation found"
    warn "   This script is designed to work with existing NVM installations"
    warn "   to avoid conflicts with your development environment."
    echo ""
    read -p "Do you want to install Node.js system-wide instead? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "❌ Node.js setup aborted by user"
        error "   Please install Node.js using NVM first, then run this script again"
        error "   Or run: curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash"
        return 1
    fi
    
    log "📦 Installing Node.js system-wide as requested..."
    
    # Remove any existing nodejs packages that might conflict
    apt-get remove -y nodejs npm 2>/dev/null || true
    apt-get autoremove -y || true
    
    # Install Node.js 20.x LTS
    log "📦 Installing Node.js 20.x LTS from NodeSource..."
    if curl -fsSL https://deb.nodesource.com/setup_20.x | bash -; then
        log "✅ NodeSource repository added"
    else
        error "❌ Failed to add NodeSource repository"
        return 1
    fi
    
    if apt-get update && apt-get install -y nodejs; then
        log "✅ Node.js installed via apt"
    else
        error "❌ Failed to install Node.js via apt"
        return 1
    fi
    
    # Verify installation
    if command -v node &> /dev/null && command -v npm &> /dev/null; then
        success "✅ Node.js installed successfully"
        log "   Node.js: $(node --version)"
        log "   npm: $(npm --version)"
        return 0
    else
        error "❌ Node.js installation failed - commands not available"
        return 1
    fi
}

# Setup Node.js
if ! setup_nodejs; then
    error "Node.js setup failed"
    exit 1
fi

# Update npm to latest version
log "🔧 Updating npm to latest version..."
npm install -g npm@latest || {
    warn "npm update failed, but continuing..."
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

# Debug: Show current directory and key files
log "📍 Current directory: $(pwd)"
log "📁 Key files check:"
[ -f "artisan" ] && log "   ✅ artisan found" || log "   ❌ artisan missing"
[ -f ".env.example" ] && log "   ✅ .env.example found" || log "   ❌ .env.example missing"
[ -f "composer.json" ] && log "   ✅ composer.json found" || log "   ❌ composer.json missing"
[ -f "package.json" ] && log "   ✅ package.json found" || log "   ❌ package.json missing"

# Install PHP dependencies
log "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies and build assets
log "📦 Installing Node.js dependencies..."

# Robust Node.js/npm setup function for deployment
setup_nodejs_for_deployment() {
    log "🔧 Setting up Node.js for deployment..."
    
    # Check if Node.js and npm are available
    if command -v node &> /dev/null && command -v npm &> /dev/null; then
        log "✅ Node.js and npm are available"
        log "   Node.js: $(node --version)"
        log "   npm: $(npm --version)"
        return 0
    fi
    
    warn "❌ Node.js or npm not found, attempting to use existing NVM installation..."
    
    # Search for NVM installations
    local nvm_paths=(
        "/partikelxyz/.nvm"
        "/home/partikelxyz/.nvm"
        "/home/$SUDO_USER/.nvm"
        "/root/.nvm"
        "/home/ubuntu/.nvm"
        "/home/ec2-user/.nvm"
    )
    
    local node_found=false
    
    for nvm_path in "${nvm_paths[@]}"; do
        if [ -d "$nvm_path" ] && [ -d "$nvm_path/versions/node" ]; then
            log "🔍 Found NVM installation at: $nvm_path"
            
            # Find the latest Node.js version
            local latest_version=$(find "$nvm_path/versions/node" -maxdepth 1 -type d -name "v*" 2>/dev/null | sort -V | tail -1)
            
            if [ -n "$latest_version" ] && [ -f "$latest_version/bin/node" ] && [ -f "$latest_version/bin/npm" ]; then
                log "🔗 Linking NVM Node.js installation..."
                log "   Using: $latest_version"
                
                # Remove existing symlinks
                rm -f /usr/local/bin/node /usr/local/bin/npm /usr/bin/node /usr/bin/npm 2>/dev/null || true
                
                # Create new symlinks
                ln -sf "$latest_version/bin/node" /usr/local/bin/node
                ln -sf "$latest_version/bin/npm" /usr/local/bin/npm
                ln -sf "$latest_version/bin/node" /usr/bin/node
                ln -sf "$latest_version/bin/npm" /usr/bin/npm
                
                # Fix permissions
                chmod +x /usr/local/bin/node /usr/local/bin/npm /usr/bin/node /usr/bin/npm 2>/dev/null || true
                
                # Verify
                if command -v node &> /dev/null && command -v npm &> /dev/null; then
                    node_found=true
                    success "✅ Successfully linked NVM Node.js: $(node --version)"
                    break
                else
                    warn "❌ Failed to link NVM Node.js installation"
                fi
            else
                log "   ❌ No valid Node.js installation found in $nvm_path"
            fi
        fi
    done
    
    if [ "$node_found" = true ]; then
        return 0
    fi
    
    # Only install system-wide as last resort
    warn "⚠️  No valid NVM installation found"
    echo ""
    read -p "Install Node.js system-wide? This may conflict with your development environment. (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "❌ Node.js setup aborted by user"
        return 1
    fi
    
    # Remove any existing Node.js installations
    log "🗑️ Cleaning existing Node.js installations..."
    apt-get remove -y nodejs npm 2>/dev/null || true
    apt-get autoremove -y || true
    
    # Install Node.js
    log "📦 Installing Node.js 20.x LTS..."
    if curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get update && apt-get install -y nodejs; then
        log "✅ Node.js installation successful"
        log "   Node.js: $(node --version)"
        log "   npm: $(npm --version)"
        return 0
    else
        error "❌ Node.js installation failed"
        return 1
    fi
}

# Setup Node.js
if ! setup_nodejs_for_deployment; then
    error "Node.js setup failed"
    exit 1
fi

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

# Fix ownership before building
log "🔧 Fixing ownership before building assets..."
chown -R www-data:www-data $PROJECT_DIR

log "🎨 Building React Inertia frontend..."
# Try building as www-data first, fallback to root if needed
if sudo -u www-data npm run build 2>/dev/null; then
    success "Assets built successfully as www-data"
elif npm run build; then
    success "Assets built successfully as root"
    # Fix ownership of generated files
    chown -R www-data:www-data $PROJECT_DIR
else
    error "Failed to build assets"
    exit 1
fi

# ============================================================================
# PHASE 4: ENVIRONMENT CONFIGURATION
# ============================================================================

log "🔧 PHASE 4: Environment Configuration"

# Create environment file
log "⚙️ Creating environment configuration..."

# Check if .env.example exists, if not create a basic one
if [ ! -f ".env.example" ]; then
    warn "⚠️  .env.example not found, creating a basic template..."
    cat > .env.example << 'ENVEOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
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

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
PUSHER_APP_KEY="${PUSHER_APP_KEY}"
PUSHER_HOST="${PUSHER_HOST}"
PUSHER_PORT="${PUSHER_PORT}"
PUSHER_SCHEME="${PUSHER_SCHEME}"
PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
ENVEOF
fi

# Copy environment file
if [ -f ".env.example" ]; then
    cp .env.example .env
    log "✅ Copied .env.example to .env"
else
    error "❌ Failed to create .env.example file"
    exit 1
fi

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
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.$DOMAIN
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# CSRF Protection Configuration
CSRF_COOKIE="csrf_token"
CSRF_TOKEN_LIFETIME=120
TRUSTED_PROXIES="*"
TRUSTED_HOSTS=$DOMAIN,$WWW_DOMAIN

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

# Create nested cache directories for Laravel cache system
for i in {0..9} {a..f}; do
    for j in {0..9} {a..f}; do
        mkdir -p storage/framework/cache/$i$j 2>/dev/null || true
    done
done

# Set initial permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create sessions table for CSRF protection
log "📋 Creating sessions table for CSRF protection..."
if ! php artisan migrate:status 2>/dev/null | grep -q "create_sessions_table"; then
    log "Creating sessions table migration..."
    php artisan session:table
fi

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
    
    # CSRF Protection Headers
    add_header X-Real-IP $remote_addr always;
    add_header X-Forwarded-For $proxy_add_x_forwarded_for always;
    add_header X-Forwarded-Proto $scheme always;
    add_header X-Forwarded-Host $host always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # CSRF Protection FastCGI Parameters
        fastcgi_param HTTP_X_REAL_IP $remote_addr;
        fastcgi_param HTTP_X_FORWARDED_FOR $proxy_add_x_forwarded_for;
        fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
        fastcgi_param HTTP_X_FORWARDED_HOST $host;
        
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
        warn "sudo certbot --nginx -d $DOMAIN "
        SSL_SKIPPED=true
    fi
fi

if [ "$SSL_SKIPPED" != "true" ]; then
    # Setup SSL certificate
    log "🔒 Setting up SSL certificate..."
    sudo certbot --nginx -d $DOMAIN -d $WWW_DOMAIN \
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

# CSRF Protection Configuration
log "🛡️ Configuring CSRF protection..."

# Ensure sessions directory has proper permissions
log "📁 Setting session directory permissions..."
mkdir -p storage/framework/sessions
chown -R www-data:www-data storage/framework/sessions
chmod -R 775 storage/framework/sessions

# Update trusted proxies configuration if file exists
if [ -f "app/Http/Middleware/TrustProxies.php" ]; then
    log "🔧 Updating TrustProxies middleware..."
    # This is optional - Laravel's default should work with our nginx config
    log "   Using default TrustProxies configuration"
fi

# Test session table exists
log "🔍 Verifying sessions table..."
if php artisan migrate:status 2>/dev/null | grep -q "sessions"; then
    success "Sessions table is ready for CSRF protection"
else
    warn "Sessions table not found - CSRF tokens will use file storage"
fi

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
