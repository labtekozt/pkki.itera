#!/bin/bash

# PKKI ITERA VPS Deployment Script
# This script deploys the application to a VPS server

set -e

# Configuration
SSH_KEY="~/.ssh/psd-new"
SSH_USER="partikelxyz"
SSH_HOST="34.124.214.243"
APP_NAME="pkki-itera"
DEPLOY_PATH="/var/www/pkki-itera"
REPO_URL="https://github.com/labtekozt/pkki.itera.git"
BRANCH="main"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# SSH command wrapper
ssh_exec() {
    ssh -i $SSH_KEY $SSH_USER@$SSH_HOST "$1"
}

# SCP command wrapper
scp_upload() {
    scp -i $SSH_KEY "$1" $SSH_USER@$SSH_HOST:"$2"
}

# Main deployment function
deploy() {
    print_status "Starting deployment to VPS..."
    
    # Test SSH connection
    print_status "Testing SSH connection..."
    if ssh_exec "echo 'SSH connection successful'"; then
        print_success "SSH connection established"
    else
        print_error "SSH connection failed"
        exit 1
    fi
    
    # Install system dependencies
    print_status "Installing system dependencies..."
    ssh_exec "sudo apt update && sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mbstring php8.2-bcmath php8.2-intl php8.2-redis composer nodejs npm git unzip curl"
    
    # Create directory structure
    print_status "Creating directory structure..."
    ssh_exec "sudo mkdir -p $DEPLOY_PATH"
    ssh_exec "sudo chown -R $SSH_USER:$SSH_USER $DEPLOY_PATH"
    
    # Clone or update repository
    print_status "Deploying application code..."
    if ssh_exec "[ -d '$DEPLOY_PATH/.git' ]"; then
        print_status "Updating existing repository..."
        ssh_exec "cd $DEPLOY_PATH && git fetch origin && git reset --hard origin/$BRANCH"
    else
        print_status "Cloning repository..."
        ssh_exec "git clone -b $BRANCH $REPO_URL $DEPLOY_PATH"
    fi
    
    # Set permissions
    print_status "Setting file permissions..."
    ssh_exec "cd $DEPLOY_PATH && sudo chown -R $SSH_USER:www-data ."
    ssh_exec "cd $DEPLOY_PATH && sudo chmod -R 755 ."
    ssh_exec "cd $DEPLOY_PATH && sudo chmod -R 775 storage bootstrap/cache"
    ssh_exec "cd $DEPLOY_PATH && sudo chmod +x setup-dev.sh"
    
    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    ssh_exec "cd $DEPLOY_PATH && composer install --no-dev --optimize-autoloader"
    
    # Install Node dependencies
    print_status "Installing Node dependencies..."
    ssh_exec "cd $DEPLOY_PATH && npm install"
    
    # Copy environment file
    print_status "Setting up environment configuration..."
    if ! ssh_exec "[ -f '$DEPLOY_PATH/.env' ]"; then
        ssh_exec "cd $DEPLOY_PATH && cp .env.example .env"
        print_warning "Please configure your .env file with production settings"
    fi
    
    # Generate application key
    print_status "Generating application key..."
    ssh_exec "cd $DEPLOY_PATH && php artisan key:generate --force"
    
    # Build assets
    print_status "Building frontend assets..."
    ssh_exec "cd $DEPLOY_PATH && npm run build"
    
    # Create storage link
    print_status "Creating storage link..."
    ssh_exec "cd $DEPLOY_PATH && php artisan storage:link"
    
    # Database setup
    setup_database
    
    # Configure Nginx
    configure_nginx
    
    # Configure PHP-FPM
    configure_php_fpm
    
    # Setup SSL (optional)
    read -p "Do you want to setup SSL with Let's Encrypt? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_ssl
    fi
    
    # Final steps
    print_status "Finalizing deployment..."
    ssh_exec "cd $DEPLOY_PATH && php artisan config:cache"
    ssh_exec "cd $DEPLOY_PATH && php artisan route:cache"
    ssh_exec "cd $DEPLOY_PATH && php artisan view:cache"
    ssh_exec "cd $DEPLOY_PATH && php artisan shield:generate --all"
    
    # Restart services
    print_status "Restarting services..."
    ssh_exec "sudo systemctl restart nginx"
    ssh_exec "sudo systemctl restart php8.2-fpm"
    
    print_success "Deployment completed successfully!"
    print_status "Your application should be accessible at: http://$SSH_HOST"
    echo
    print_warning "Important next steps:"
    echo "1. Configure your domain DNS to point to $SSH_HOST"
    echo "2. Update .env file with production database credentials"
    echo "3. Set up SSL certificate for HTTPS"
    echo "4. Configure backup strategy"
    echo "5. Set up monitoring and logging"
}

setup_database() {
    print_status "Setting up database..."
    
    # Secure MySQL installation
    print_status "Securing MySQL installation..."
    ssh_exec "sudo mysql_secure_installation" || print_warning "MySQL secure installation skipped"
    
    # Create database and user
    read -p "Enter database name [pkki_itera]: " DB_NAME
    DB_NAME=${DB_NAME:-pkki_itera}
    
    read -p "Enter database username [pkki_user]: " DB_USER
    DB_USER=${DB_USER:-pkki_user}
    
    read -s -p "Enter database password: " DB_PASS
    echo
    
    ssh_exec "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
    ssh_exec "sudo mysql -e \"CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';\""
    ssh_exec "sudo mysql -e \"GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';\""
    ssh_exec "sudo mysql -e \"FLUSH PRIVILEGES;\""
    
    # Update .env file
    ssh_exec "cd $DEPLOY_PATH && sed -i 's/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/' .env"
    ssh_exec "cd $DEPLOY_PATH && sed -i 's/DB_USERNAME=.*/DB_USERNAME=$DB_USER/' .env"
    ssh_exec "cd $DEPLOY_PATH && sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/' .env"
    
    # Run migrations
    print_status "Running database migrations..."
    ssh_exec "cd $DEPLOY_PATH && php artisan migrate --force"
    
    # Run seeders
    read -p "Do you want to run database seeders? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ssh_exec "cd $DEPLOY_PATH && php artisan db:seed --force"
    fi
    
    print_success "Database setup completed"
}

configure_nginx() {
    print_status "Configuring Nginx..."
    
    # Upload Nginx configuration
    cat > /tmp/pkki-itera.conf << EOF
server {
    listen 80;
    server_name $SSH_HOST;
    root $DEPLOY_PATH/public;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Laravel specific
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    # Client max body size
    client_max_body_size 50M;
}
EOF

    scp_upload "/tmp/pkki-itera.conf" "/tmp/pkki-itera.conf"
    ssh_exec "sudo mv /tmp/pkki-itera.conf /etc/nginx/sites-available/pkki-itera"
    ssh_exec "sudo ln -sf /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/"
    ssh_exec "sudo rm -f /etc/nginx/sites-enabled/default"
    ssh_exec "sudo nginx -t"
    
    print_success "Nginx configuration completed"
}

configure_php_fpm() {
    print_status "Configuring PHP-FPM..."
    
    # Upload PHP configuration
    scp_upload "docker/php.ini" "/tmp/pkki-php.ini"
    ssh_exec "sudo cp /tmp/pkki-php.ini /etc/php/8.2/fpm/conf.d/99-pkki.ini"
    ssh_exec "sudo cp /tmp/pkki-php.ini /etc/php/8.2/cli/conf.d/99-pkki.ini"
    
    # Update PHP-FPM pool configuration
    ssh_exec "sudo sed -i 's/user = www-data/user = $SSH_USER/' /etc/php/8.2/fpm/pool.d/www.conf"
    ssh_exec "sudo sed -i 's/group = www-data/group = www-data/' /etc/php/8.2/fpm/pool.d/www.conf"
    
    print_success "PHP-FPM configuration completed"
}

setup_ssl() {
    print_status "Setting up SSL with Let's Encrypt..."
    
    # Install Certbot
    ssh_exec "sudo apt install -y certbot python3-certbot-nginx"
    
    read -p "Enter your domain name (e.g., pkki.itera.ac.id): " DOMAIN
    if [ ! -z "$DOMAIN" ]; then
        # Update Nginx configuration with domain
        ssh_exec "sudo sed -i 's/server_name $SSH_HOST;/server_name $DOMAIN;/' /etc/nginx/sites-available/pkki-itera"
        ssh_exec "sudo nginx -t && sudo systemctl reload nginx"
        
        # Obtain SSL certificate
        ssh_exec "sudo certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@itera.ac.id"
        
        print_success "SSL certificate installed for $DOMAIN"
    else
        print_warning "Domain not provided, SSL setup skipped"
    fi
}

# Check if we're being called with specific function
if [ "$1" = "deploy" ]; then
    deploy
elif [ "$1" = "setup-database" ]; then
    setup_database
elif [ "$1" = "configure-nginx" ]; then
    configure_nginx
elif [ "$1" = "setup-ssl" ]; then
    setup_ssl
else
    # Show usage
    echo "Usage: $0 [deploy|setup-database|configure-nginx|setup-ssl]"
    echo
    echo "Commands:"
    echo "  deploy          - Full deployment process"
    echo "  setup-database  - Setup database only"
    echo "  configure-nginx - Configure Nginx only"
    echo "  setup-ssl       - Setup SSL certificate only"
    echo
    echo "For full deployment, run: $0 deploy"
    echo
    
    # Ask if user wants to run full deployment
    read -p "Do you want to run full deployment now? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        deploy
    fi
fi
