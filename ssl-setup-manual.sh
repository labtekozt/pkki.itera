#!/bin/bash

# PKKI ITERA - Manual SSL Setup Script
# Setup SSL certificate for hki.proyekai.com step by step
# Usage: sudo ./ssl-setup-manual.sh

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
DOMAIN="hki.proyekai.com"
WWW_DOMAIN="www.hki.proyekai.com"
PROJECT_DIR="/var/www/pkki-itera"
ADMIN_EMAIL="admin@hki.itera.ac.id"

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
    echo "Usage: sudo ./ssl-setup-manual.sh"
    exit 1
fi

# Banner
clear
echo -e "${CYAN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    PKKI ITERA SSL SETUP                         ║"
echo "║                  Manual Step-by-Step Guide                      ║"
echo "║                    Domain: hki.proyekai.com                      ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Get server IP
SERVER_IP=$(curl -s http://checkip.amazonaws.com 2>/dev/null || curl -s http://icanhazip.com 2>/dev/null || echo "Unable to detect")
log "Server IP: $SERVER_IP"

echo ""
echo -e "${BLUE}📋 SSL Setup Configuration:${NC}"
echo "   Domain: $DOMAIN"
echo "   WWW Domain: $WWW_DOMAIN"
echo "   Admin Email: $ADMIN_EMAIL"
echo "   Server IP: $SERVER_IP"
echo ""

# ============================================================================
# STEP 1: VERIFY CURRENT SETUP
# ============================================================================

step "1. Verifying Current Setup"

# Check if nginx is running
if systemctl is-active --quiet nginx; then
    success "Nginx is running"
else
    error "Nginx is not running"
    log "Starting Nginx..."
    systemctl start nginx || {
        error "Failed to start Nginx"
        exit 1
    }
fi

# Check current nginx configuration
if [ -f "/etc/nginx/sites-available/pkki-itera" ]; then
    success "PKKI ITERA nginx configuration exists"
else
    error "PKKI ITERA nginx configuration not found"
    log "Creating basic nginx configuration..."
    
    cat > /etc/nginx/sites-available/pkki-itera << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name hki.proyekai.com www.hki.proyekai.com;
    
    root /var/www/pkki-itera/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\. {
        deny all;
    }
    
    client_max_body_size 50M;
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    nginx -t && systemctl reload nginx
    success "Basic nginx configuration created"
fi

# Check if application is accessible
log "Testing HTTP access..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    success "HTTP site is accessible (Status: $HTTP_STATUS)"
else
    warn "HTTP site not accessible (Status: $HTTP_STATUS)"
    log "Trying with port 3003..."
    HTTP_STATUS_PORT=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN:3003" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS_PORT" = "200" ]; then
        warn "Site accessible on port 3003, need to update nginx config"
        # Update nginx to listen on port 80 instead of 3003
        sed -i 's/listen 3003;/listen 80;/' /etc/nginx/sites-available/pkki-itera
        nginx -t && systemctl reload nginx
    fi
fi

# ============================================================================
# STEP 2: DNS VERIFICATION
# ============================================================================

step "2. DNS Verification"

log "Checking DNS resolution for $DOMAIN..."
DOMAIN_IP=$(dig +short $DOMAIN 2>/dev/null || nslookup $DOMAIN 2>/dev/null | grep -A1 "Name:" | tail -1 | awk '{print $2}' || echo "")

if [ -n "$DOMAIN_IP" ]; then
    log "Domain $DOMAIN resolves to: $DOMAIN_IP"
    if [ "$DOMAIN_IP" = "$SERVER_IP" ]; then
        success "✅ DNS correctly points to this server"
        DNS_OK=true
    else
        warn "⚠️ DNS points to different IP"
        warn "Domain IP: $DOMAIN_IP"
        warn "Server IP: $SERVER_IP"
        echo ""
        echo -e "${YELLOW}DNS Configuration Instructions:${NC}"
        echo "1. Go to your domain registrar (where you bought hki.proyekai.com)"
        echo "2. Find DNS management or nameserver settings"
        echo "3. Add/Update these DNS records:"
        echo "   - A Record: @ → $SERVER_IP"
        echo "   - A Record: www → $SERVER_IP"
        echo "4. Wait 5-30 minutes for DNS propagation"
        echo ""
        read -p "Continue SSL setup anyway? The certificate request might fail. (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Please configure DNS first, then run this script again"
            exit 1
        fi
    fi
else
    error "❌ Domain does not resolve"
    echo ""
    echo -e "${RED}DNS Setup Required:${NC}"
    echo "Your domain hki.proyekai.com does not resolve to any IP address."
    echo ""
    echo "Please configure DNS with these settings:"
    echo "1. A Record: @ → $SERVER_IP"
    echo "2. A Record: www → $SERVER_IP"
    echo ""
    echo "Then wait for DNS propagation (5-30 minutes) and run this script again."
    exit 1
fi

# ============================================================================
# STEP 3: INSTALL/UPDATE CERTBOT
# ============================================================================

step "3. Installing/Updating Certbot"

# Check if certbot is installed
if command -v certbot &> /dev/null; then
    success "Certbot is already installed"
    certbot --version
else
    log "Installing Certbot..."
    apt update
    apt install -y certbot python3-certbot-nginx
    success "Certbot installed"
fi

# ============================================================================
# STEP 4: FIREWALL CONFIGURATION
# ============================================================================

step "4. Configuring Firewall"

log "Configuring UFW firewall..."
ufw --force enable
ufw allow ssh
ufw allow 'Nginx Full'  # This allows both HTTP (80) and HTTPS (443)
ufw allow 80/tcp
ufw allow 443/tcp
ufw reload

success "Firewall configured to allow HTTP and HTTPS"

# ============================================================================
# STEP 5: OBTAIN SSL CERTIFICATE
# ============================================================================

step "5. Obtaining SSL Certificate"

log "Preparing to obtain SSL certificate..."

# Stop nginx temporarily to avoid conflicts
log "Temporarily stopping nginx..."
systemctl stop nginx

# Try standalone mode first (more reliable)
log "Attempting to obtain SSL certificate using standalone mode..."
if certbot certonly \
    --standalone \
    --non-interactive \
    --agree-tos \
    --email "$ADMIN_EMAIL" \
    -d "$DOMAIN" \
    -d "$WWW_DOMAIN" \
    --rsa-key-size 2048 \
    --force-renewal; then
    
    success "✅ SSL certificate obtained successfully!"
    SSL_SUCCESS=true
else
    warn "Standalone mode failed, trying webroot mode..."
    
    # Start nginx back up
    systemctl start nginx
    
    # Create webroot directory
    mkdir -p /var/www/pkki-itera/public/.well-known/acme-challenge
    chown -R www-data:www-data /var/www/pkki-itera/public/.well-known
    
    # Update nginx config to serve .well-known
    cat > /etc/nginx/sites-available/pkki-itera << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name hki.proyekai.com www.hki.proyekai.com;
    
    root /var/www/pkki-itera/public;
    index index.php index.html;

    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root /var/www/pkki-itera/public;
        allow all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\. {
        deny all;
    }
    
    client_max_body_size 50M;
}
EOF
    
    nginx -t && systemctl reload nginx
    
    # Try webroot mode
    if certbot certonly \
        --webroot \
        --webroot-path=/var/www/pkki-itera/public \
        --non-interactive \
        --agree-tos \
        --email "$ADMIN_EMAIL" \
        -d "$DOMAIN" \
        -d "$WWW_DOMAIN" \
        --rsa-key-size 2048; then
        
        success "✅ SSL certificate obtained using webroot mode!"
        SSL_SUCCESS=true
    else
        error "❌ Both standalone and webroot modes failed"
        SSL_SUCCESS=false
    fi
fi

# Start nginx if it's not running
systemctl start nginx

# ============================================================================
# STEP 6: CONFIGURE NGINX WITH SSL
# ============================================================================

if [ "$SSL_SUCCESS" = "true" ]; then
    step "6. Configuring Nginx with SSL"
    
    log "Creating SSL-enabled nginx configuration..."
    
    cat > /etc/nginx/sites-available/pkki-itera << EOF
# HTTP - Redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN $WWW_DOMAIN;
    
    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root /var/www/pkki-itera/public;
        allow all;
    }
    
    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

# HTTPS - Main application
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $DOMAIN $WWW_DOMAIN;
    
    root /var/www/pkki-itera/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    
    # SSL Security Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_timeout 10m;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Application
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

    # Test and reload nginx
    if nginx -t; then
        systemctl reload nginx
        success "✅ Nginx SSL configuration applied"
    else
        error "❌ Nginx configuration test failed"
        exit 1
    fi

    # ============================================================================
    # STEP 7: UPDATE APPLICATION CONFIGURATION
    # ============================================================================

    step "7. Updating Application Configuration"
    
    if [ -f "$PROJECT_DIR/.env" ]; then
        log "Updating APP_URL to use HTTPS..."
        sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" "$PROJECT_DIR/.env"
        success "Application configuration updated"
    else
        warn "Application .env file not found at $PROJECT_DIR/.env"
    fi

    # ============================================================================
    # STEP 8: SETUP AUTO-RENEWAL
    # ============================================================================

    step "8. Setting up SSL Auto-Renewal"
    
    log "Creating auto-renewal cron job..."
    (crontab -l 2>/dev/null; echo "0 2 * * * certbot renew --quiet && systemctl reload nginx") | crontab -
    success "Auto-renewal cron job created"
    
    # Test renewal
    log "Testing certificate renewal..."
    if certbot renew --dry-run; then
        success "✅ Auto-renewal test passed"
    else
        warn "⚠️ Auto-renewal test failed, but certificate is still valid"
    fi

else
    step "6. SSL Setup Failed - Troubleshooting"
    
    error "SSL certificate could not be obtained"
    echo ""
    echo -e "${YELLOW}Troubleshooting Steps:${NC}"
    echo "1. Verify DNS configuration:"
    echo "   dig $DOMAIN"
    echo "   Should return: $SERVER_IP"
    echo ""
    echo "2. Check if port 80 is accessible:"
    echo "   curl -I http://$DOMAIN"
    echo ""
    echo "3. Check nginx error logs:"
    echo "   tail -f /var/log/nginx/error.log"
    echo ""
    echo "4. Check certbot logs:"
    echo "   tail -f /var/log/letsencrypt/letsencrypt.log"
    echo ""
    echo "5. Try manual certificate request:"
    echo "   sudo certbot --nginx -d $DOMAIN -d $WWW_DOMAIN"
    echo ""
fi

# ============================================================================
# STEP 9: FINAL VERIFICATION
# ============================================================================

step "9. Final Verification"

# Test HTTP (should redirect to HTTPS)
log "Testing HTTP redirect..."
HTTP_RESPONSE=$(curl -s -I "http://$DOMAIN" | head -1 || echo "Failed")
if echo "$HTTP_RESPONSE" | grep -q "301\|302"; then
    success "✅ HTTP correctly redirects to HTTPS"
else
    warn "⚠️ HTTP redirect not working: $HTTP_RESPONSE"
fi

# Test HTTPS
if [ "$SSL_SUCCESS" = "true" ]; then
    log "Testing HTTPS access..."
    HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN" 2>/dev/null || echo "000")
    if [ "$HTTPS_STATUS" = "200" ]; then
        success "✅ HTTPS site is accessible"
    else
        warn "⚠️ HTTPS not accessible (Status: $HTTPS_STATUS)"
    fi
    
    # Test SSL certificate
    log "Verifying SSL certificate..."
    if echo | timeout 10 openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | grep -q "Verify return code: 0"; then
        success "✅ SSL certificate is valid"
    else
        warn "⚠️ SSL certificate verification failed"
    fi
fi

# ============================================================================
# COMPLETION SUMMARY
# ============================================================================

clear
echo -e "${GREEN}${WHITE}"
echo "╔══════════════════════════════════════════════════════════════════╗"
if [ "$SSL_SUCCESS" = "true" ]; then
    echo "║                    🔒 SSL SETUP COMPLETED! 🔒                   ║"
else
    echo "║                    ⚠️  SSL SETUP INCOMPLETE ⚠️                  ║"
fi
echo "╚══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${CYAN}📊 SSL Setup Summary:${NC}"
echo "===================="
echo "Domain: $DOMAIN"
echo "WWW Domain: $WWW_DOMAIN"
echo "Server IP: $SERVER_IP"
echo "SSL Certificate: $([ "$SSL_SUCCESS" = "true" ] && echo "✅ Installed" || echo "❌ Failed")"
echo "Auto-Renewal: $([ "$SSL_SUCCESS" = "true" ] && echo "✅ Configured" || echo "❌ Not Set")"
echo ""

if [ "$SSL_SUCCESS" = "true" ]; then
    echo -e "${CYAN}🔗 Access URLs:${NC}"
    echo "🔒 Secure Site: https://$DOMAIN"
    echo "🔒 Admin Panel: https://$DOMAIN/admin"
    echo "📝 HTTP automatically redirects to HTTPS"
    echo ""
    
    echo -e "${CYAN}🛡️ Security Features:${NC}"
    echo "✅ TLS 1.2 & 1.3 enabled"
    echo "✅ HSTS (HTTP Strict Transport Security)"
    echo "✅ Security headers configured"
    echo "✅ SSL certificate auto-renewal"
    echo ""
    
    success "🎉 Your PKKI ITERA application is now secured with SSL!"
else
    echo -e "${YELLOW}🔧 Manual SSL Setup:${NC}"
    echo "If automatic setup failed, try these commands:"
    echo ""
    echo "1. Manual certificate request:"
    echo "   sudo certbot --nginx -d $DOMAIN -d $WWW_DOMAIN"
    echo ""
    echo "2. Or standalone mode:"
    echo "   sudo systemctl stop nginx"
    echo "   sudo certbot certonly --standalone -d $DOMAIN -d $WWW_DOMAIN"
    echo "   sudo systemctl start nginx"
    echo ""
    echo "3. Check logs for errors:"
    echo "   sudo tail -f /var/log/letsencrypt/letsencrypt.log"
    echo ""
fi

echo -e "${CYAN}🛠️ Useful SSL Commands:${NC}"
echo "Check certificate status: sudo certbot certificates"
echo "Renew certificates: sudo certbot renew"
echo "Test auto-renewal: sudo certbot renew --dry-run"
echo "Check SSL rating: https://www.ssllabs.com/ssltest/"
echo ""

echo -e "${PURPLE}==========================================${NC}"
echo -e "${WHITE}SSL Setup by: PKKI ITERA Development Team${NC}"
echo -e "${WHITE}Date: $(date)${NC}"
echo -e "${PURPLE}==========================================${NC}"
