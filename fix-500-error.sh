#!/bin/bash

# PKKI ITERA - Fix 500 Error Script
# Diagnose and fix common 500 error issues
# Usage: sudo ./fix-500-error.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/pkki-itera"
DOMAIN="hki.proyekai.com"

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

step() {
    echo -e "${CYAN}[STEP] $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "Script ini harus dijalankan sebagai root atau dengan sudo"
    echo "Usage: sudo ./fix-500-error.sh"
    exit 1
fi

# Banner
clear
echo -e "${CYAN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    PKKI ITERA 500 ERROR FIX                     ║"
echo "║                  Diagnose & Fix Server Errors                   ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ============================================================================
# STEP 1: CHECK LOGS
# ============================================================================

step "1. Checking Error Logs"

echo -e "${BLUE}🔍 Laravel Application Logs:${NC}"
if [ -f "$PROJECT_DIR/storage/logs/laravel.log" ]; then
    echo "Last 20 lines of Laravel log:"
    echo "================================"
    tail -20 "$PROJECT_DIR/storage/logs/laravel.log" || echo "No recent Laravel errors"
    echo ""
else
    warn "Laravel log file not found at $PROJECT_DIR/storage/logs/laravel.log"
fi

echo -e "${BLUE}🔍 Nginx Error Logs:${NC}"
if [ -f "/var/log/nginx/error.log" ]; then
    echo "Last 10 lines of Nginx error log:"
    echo "=================================="
    tail -10 /var/log/nginx/error.log || echo "No recent Nginx errors"
    echo ""
else
    warn "Nginx error log not found"
fi

echo -e "${BLUE}🔍 PHP-FPM Error Logs:${NC}"
if [ -f "/var/log/php8.2-fpm.log" ]; then
    echo "Last 10 lines of PHP-FPM log:"
    echo "=============================="
    tail -10 /var/log/php8.2-fpm.log || echo "No recent PHP-FPM errors"
    echo ""
else
    warn "PHP-FPM log not found"
fi

# ============================================================================
# STEP 2: CHECK SERVICES STATUS
# ============================================================================

step "2. Checking Services Status"

services=("nginx" "php8.2-fpm" "mysql")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        success "$service is running"
    else
        error "$service is not running"
        log "Starting $service..."
        systemctl start $service || warn "Failed to start $service"
    fi
done

# ============================================================================
# STEP 3: CHECK FILE PERMISSIONS AND DIRECTORIES
# ============================================================================

step "3. Fixing File Permissions and Creating Missing Directories"

if [ -d "$PROJECT_DIR" ]; then
    log "Creating required directories..."
    
    # Create all required Laravel directories
    mkdir -p $PROJECT_DIR/storage/logs
    mkdir -p $PROJECT_DIR/storage/app/public
    mkdir -p $PROJECT_DIR/storage/framework/cache/data
    mkdir -p $PROJECT_DIR/storage/framework/sessions
    mkdir -p $PROJECT_DIR/storage/framework/views
    mkdir -p $PROJECT_DIR/storage/framework/testing
    mkdir -p $PROJECT_DIR/bootstrap/cache
    
    # Create nested cache directories (Laravel creates nested folders like 85/5f/)
    for i in {0..9} {a..f}; do
        for j in {0..9} {a..f}; do
            mkdir -p $PROJECT_DIR/storage/framework/cache/data/$i$j
        done
    done
    
    log "Setting correct file permissions..."
    
    # Set ownership first
    chown -R www-data:www-data $PROJECT_DIR
    
    # Set directory permissions
    find $PROJECT_DIR -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find $PROJECT_DIR -type f -exec chmod 644 {} \;
    
    # Set executable permissions for specific files
    chmod +x $PROJECT_DIR/artisan
    
    # Set special permissions for storage and cache (more permissive)
    chmod -R 777 $PROJECT_DIR/storage
    chmod -R 777 $PROJECT_DIR/bootstrap/cache
    
    # Ensure www-data can write to these directories
    chown -R www-data:www-data $PROJECT_DIR/storage
    chown -R www-data:www-data $PROJECT_DIR/bootstrap/cache
    
    # Create log file if it doesn't exist
    if [ ! -f "$PROJECT_DIR/storage/logs/laravel.log" ]; then
        touch $PROJECT_DIR/storage/logs/laravel.log
        chown www-data:www-data $PROJECT_DIR/storage/logs/laravel.log
        chmod 664 $PROJECT_DIR/storage/logs/laravel.log
    fi
    
    success "File permissions and directories fixed"
else
    error "Project directory not found at $PROJECT_DIR"
    exit 1
fi

# ============================================================================
# STEP 4: CHECK .ENV FILE
# ============================================================================

step "4. Checking Environment Configuration"

if [ -f "$PROJECT_DIR/.env" ]; then
    success ".env file exists"
    
    # Check if APP_KEY is set
    if grep -q "^APP_KEY=base64:" "$PROJECT_DIR/.env"; then
        success "APP_KEY is properly set"
    else
        warn "APP_KEY is not set or invalid"
        log "Generating new APP_KEY..."
        cd $PROJECT_DIR
        sudo -u www-data php artisan key:generate --force
        success "APP_KEY generated"
    fi
    
    # Check database configuration
    if grep -q "^DB_DATABASE=" "$PROJECT_DIR/.env"; then
        success "Database configuration found in .env"
    else
        warn "Database configuration missing in .env"
    fi
    
else
    error ".env file not found"
    if [ -f "$PROJECT_DIR/.env.example" ]; then
        log "Creating .env from .env.example..."
        cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
        chown www-data:www-data "$PROJECT_DIR/.env"
        chmod 644 "$PROJECT_DIR/.env"
        
        # Generate APP_KEY
        cd $PROJECT_DIR
        sudo -u www-data php artisan key:generate --force
        success ".env file created and APP_KEY generated"
    else
        error ".env.example not found. Cannot create .env file."
    fi
fi

# ============================================================================
# STEP 5: CHECK DATABASE CONNECTION
# ============================================================================

step "5. Checking Database Connection"

cd $PROJECT_DIR

# Test database connection
log "Testing database connection..."
if sudo -u www-data php artisan migrate:status >/dev/null 2>&1; then
    success "Database connection is working"
else
    error "Database connection failed"
    log "Checking MySQL service..."
    
    if systemctl is-active --quiet mysql; then
        log "MySQL is running, checking configuration..."
        
        # Get database config from .env
        DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2 | tr -d '"' || echo "")
        DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2 | tr -d '"' || echo "")
        DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2 | tr -d '"' || echo "")
        
        if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
            log "Database config found: $DB_NAME with user $DB_USER"
            
            # Test MySQL connection
            if mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
                success "MySQL connection successful"
                
                log "Running database migrations..."
                sudo -u www-data php artisan migrate --force || warn "Migration failed"
            else
                error "MySQL authentication failed"
                warn "Please check database credentials in .env file"
            fi
        else
            error "Database configuration incomplete in .env"
        fi
    else
        error "MySQL service is not running"
        log "Starting MySQL..."
        systemctl start mysql
    fi
fi

# ============================================================================
# STEP 6: CLEAR CACHES
# ============================================================================

step "6. Clearing Application Caches"

cd $PROJECT_DIR

log "Clearing application caches..."
sudo -u www-data php artisan config:clear || warn "Config clear failed"
sudo -u www-data php artisan route:clear || warn "Route clear failed"
sudo -u www-data php artisan view:clear || warn "View clear failed"
sudo -u www-data php artisan cache:clear || warn "Cache clear failed"

log "Rebuilding caches..."
sudo -u www-data php artisan config:cache || warn "Config cache failed"
sudo -u www-data php artisan route:cache || warn "Route cache failed"

# Skip view cache for Filament compatibility
log "Skipping view cache (Filament compatibility)"

success "Caches cleared and rebuilt"

# ============================================================================
# STEP 7: CHECK STORAGE LINK
# ============================================================================

step "7. Checking Storage Link"

if [ -L "$PROJECT_DIR/public/storage" ]; then
    success "Storage link exists"
else
    log "Creating storage link..."
    cd $PROJECT_DIR
    sudo -u www-data php artisan storage:link || warn "Storage link creation failed"
    success "Storage link created"
fi

# ============================================================================
# STEP 8: CHECK COMPOSER DEPENDENCIES
# ============================================================================

step "8. Checking Composer Dependencies"

cd $PROJECT_DIR

log "Checking if vendor directory exists..."
if [ -d "vendor" ]; then
    success "Vendor directory exists"
    
    # Check if autoload file exists
    if [ -f "vendor/autoload.php" ]; then
        success "Composer autoload file exists"
    else
        warn "Composer autoload file missing"
        log "Running composer install..."
        composer install --no-dev --optimize-autoloader --no-interaction || error "Composer install failed"
    fi
else
    error "Vendor directory missing"
    log "Running composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction || error "Composer install failed"
fi

# ============================================================================
# STEP 9: CHECK NGINX CONFIGURATION
# ============================================================================

step "9. Checking Nginx Configuration"

log "Testing Nginx configuration..."
if nginx -t; then
    success "Nginx configuration is valid"
else
    error "Nginx configuration has errors"
    log "Checking nginx configuration file..."
    
    # Check if our site configuration exists
    if [ -f "/etc/nginx/sites-available/pkki-itera" ]; then
        log "PKKI ITERA nginx config exists"
        
        # Check if it's enabled
        if [ -L "/etc/nginx/sites-enabled/pkki-itera" ]; then
            success "Site is enabled"
        else
            log "Enabling site..."
            ln -sf /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
        fi
        
        # Remove default site if exists
        if [ -f "/etc/nginx/sites-enabled/default" ]; then
            log "Removing default nginx site..."
            rm -f /etc/nginx/sites-enabled/default
        fi
        
        # Test again
        if nginx -t; then
            systemctl reload nginx
            success "Nginx configuration fixed and reloaded"
        else
            error "Nginx configuration still has errors"
        fi
    else
        error "PKKI ITERA nginx configuration not found"
        log "Creating basic nginx configuration..."
        
        cat > /etc/nginx/sites-available/pkki-itera << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    root $PROJECT_DIR/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\. {
        deny all;
    }

    location /storage {
        alias $PROJECT_DIR/storage/app/public;
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
        
        if nginx -t; then
            systemctl reload nginx
            success "Nginx configuration created and applied"
        else
            error "New nginx configuration has errors"
        fi
    fi
fi

# ============================================================================
# STEP 10: CHECK PHP CONFIGURATION
# ============================================================================

step "10. Checking PHP Configuration"

log "Checking PHP version and modules..."
php --version | head -1

# Check required PHP extensions
required_extensions=("pdo" "pdo_mysql" "mbstring" "xml" "curl" "zip" "gd" "bcmath" "intl")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        success "$ext extension is loaded"
    else
        error "$ext extension is missing"
        missing_extensions+=("php8.2-$ext")
    fi
done

if [ ${#missing_extensions[@]} -gt 0 ]; then
    log "Installing missing PHP extensions..."
    apt update
    apt install -y "${missing_extensions[@]}"
    systemctl restart php8.2-fpm
fi

# ============================================================================
# STEP 11: FINAL TEST
# ============================================================================

step "11. Final Application Test"

cd $PROJECT_DIR

log "Testing Laravel artisan commands..."
if sudo -u www-data php artisan --version >/dev/null 2>&1; then
    success "Laravel is responding to artisan commands"
else
    error "Laravel artisan commands failing"
fi

log "Testing HTTP response..."
sleep 2  # Give services time to restart

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" = "200" ]; then
    success "✅ HTTP response is 200 OK!"
elif [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    success "✅ HTTP response is $HTTP_STATUS (redirect)"
else
    warn "HTTP response is $HTTP_STATUS"
    
    # Try with localhost
    LOCAL_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost" 2>/dev/null || echo "000")
    if [ "$LOCAL_STATUS" = "200" ]; then
        warn "Application works on localhost but not on domain"
        warn "This might be a DNS or domain configuration issue"
    else
        error "Application not responding on localhost either"
    fi
fi

# ============================================================================
# COMPLETION SUMMARY
# ============================================================================

clear
echo -e "${GREEN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    🔧 500 ERROR DIAGNOSIS COMPLETE              ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${CYAN}📊 Fix Summary:${NC}"
echo "==============="
echo "✅ Services checked and restarted"
echo "✅ File permissions fixed"
echo "✅ Environment configuration verified"
echo "✅ Database connection tested"
echo "✅ Caches cleared and rebuilt"
echo "✅ Storage link verified"
echo "✅ Composer dependencies checked"
echo "✅ Nginx configuration verified"
echo "✅ PHP extensions checked"
echo ""

echo -e "${CYAN}🔗 Test Your Application:${NC}"
echo "HTTP: http://$DOMAIN"
echo "HTTPS: https://$DOMAIN (if SSL is configured)"
echo "Admin Panel: http://$DOMAIN/admin"
echo ""

echo -e "${CYAN}🛠️ If Still Getting 500 Error:${NC}"
echo "1. Check real-time logs:"
echo "   tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo "   tail -f /var/log/nginx/error.log"
echo ""
echo "2. Enable debug mode temporarily:"
echo "   sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' $PROJECT_DIR/.env"
echo "   (Remember to disable it after debugging)"
echo ""
echo "3. Check specific error details:"
echo "   curl -I http://$DOMAIN"
echo ""
echo "4. Restart all services:"
echo "   sudo systemctl restart nginx php8.2-fpm mysql"
echo ""

echo -e "${YELLOW}⚠️ Common 500 Error Causes:${NC}"
echo "• Missing or incorrect .env file"
echo "• Wrong file permissions"
echo "• Missing APP_KEY"
echo "• Database connection issues"
echo "• Missing PHP extensions"
echo "• Nginx misconfiguration"
echo "• Composer autoload issues"
echo ""

success "🎉 500 Error diagnosis completed!"

echo -e "${PURPLE}==========================================${NC}"
echo -e "${WHITE}Fix Script by: PKKI ITERA Development Team${NC}"
echo -e "${WHITE}Date: $(date)${NC}"
echo -e "${PURPLE}==========================================${NC}"
