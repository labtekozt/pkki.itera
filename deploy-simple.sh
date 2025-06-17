#!/bin/bash

# PKKI ITERA VPS One-Click Deployment Script
# Usage: Run this script on your VPS after SSH connection

set -e

echo "🚀 PKKI ITERA VPS Deployment Starting..."

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "⚙️ Installing required packages..."
sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mbstring php8.2-bcmath php8.2-intl composer git unzip curl


# Verify installations
echo "✅ Verifying installations..."
node --version
npm --version

# Start and enable services
echo "🔧 Starting services..."
sudo systemctl enable nginx mysql php8.2-fpm
sudo systemctl start nginx mysql php8.2-fpm

# Create database
echo "🗄️ Setting up database..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS pkki_itera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'pkki_user'@'localhost' IDENTIFIED BY 'PKKIitera2024!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON pkki_itera.* TO 'pkki_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Clone application
echo "📁 Deploying application..."
sudo mkdir -p /var/www/pkki-itera
sudo chown -R $USER:$USER /var/www/pkki-itera
git clone https://github.com/labtekozt/pkki.itera.git /var/www/pkki-itera
cd /var/www/pkki-itera

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "📦 Installing Node.js dependencies..."
npm ci --production=false

echo "🎨 Building React Inertia frontend..."
npm run build

# Setup environment
echo "⚙️ Configuring environment..."
cp .env.example .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=pkki_itera/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=pkki_user/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=PKKIitera2024!/' .env
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's|APP_URL=.*|APP_URL=http://hki.proyekai.com:3003|' .env

# Generate key and setup
echo "🔑 Setting up Laravel..."
php artisan key:generate --force

# Run migrations and seeders safely
echo "🗄️ Setting up database..."
php artisan migrate --force

echo "🌱 Seeding database..."
# Run specific seeders to avoid errors
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=SubmissionTypeSeeder --force
php artisan db:seed --class=WorkflowStageSeeder --force
php artisan db:seed --class=UsersTableSeeder --force

echo "🔗 Creating storage link..."
php artisan storage:link
php artisan shield:generate --all

# Set permissions BEFORE optimization
echo "🔒 Setting permissions..."

# Ensure proper directory structure first
sudo mkdir -p /var/www/pkki-itera/storage/logs
sudo mkdir -p /var/www/pkki-itera/storage/framework/{cache,sessions,views}
sudo mkdir -p /var/www/pkki-itera/storage/app/public
sudo mkdir -p /var/www/pkki-itera/bootstrap/cache

# Set ownership and permissions
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo chmod -R 755 /var/www/pkki-itera
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache

# Ensure web server can write to critical directories
sudo chown -R www-data:www-data /var/www/pkki-itera/storage
sudo chown -R www-data:www-data /var/www/pkki-itera/bootstrap/cache
sudo chmod -R 755 /var/www/pkki-itera/public

# Configure Nginx
echo "🌐 Configuring Nginx..."
sudo tee /etc/nginx/sites-available/pkki-itera > /dev/null << 'EOF'
server {
    listen 3003;
    server_name hki.proyekai.com;
    root /var/www/pkki-itera/public;
    index index.php index.html;

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

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\. {
        deny all;
    }

    client_max_body_size 50M;
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Final permission check before optimization
echo "🔒 Final permission check..."
sudo chown -R www-data:www-data /var/www/pkki-itera/storage /var/www/pkki-itera/bootstrap/cache
sudo chmod -R 775 /var/www/pkki-itera/storage /var/www/pkki-itera/bootstrap/cache

# Clear any existing cache first
echo "🧹 Clearing existing cache..."
sudo -u www-data php artisan config:clear || true
sudo -u www-data php artisan route:clear || true
sudo -u www-data php artisan view:clear || true

# Optimize for production (run as www-data user)
echo "⚡ Optimizing for production..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

# Don't cache views for Filament - it causes component issues
echo "ℹ️ Skipping view cache (Filament compatibility)"

# Setup cron job
echo "⏰ Setting up cron job..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/pkki-itera && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Create admin user
echo "👤 Creating admin user..."
php artisan make:filament-user --name="Administrator" --email="admin@itera.ac.id" --password="admin123" || {
    echo "⚠️ Admin user creation failed, you can create it manually later with:"
    echo "   php artisan make:filament-user"
}

# Get server IP
SERVER_IP=$(curl -s http://checkip.amazonaws.com)

echo "✅ Deployment completed successfully!"
echo ""
echo "🌐 Your application is now accessible at:"
echo "   Frontend: http://hki.proyekai.com:3003"
echo "   Admin Panel: http://hki.proyekai.com:3003/admin"
echo "   Direct IP: http://$SERVER_IP:3003"
echo ""
echo "👤 Default admin credentials:"
echo "   Email: admin@itera.ac.id"
echo "   Password: admin123"
echo ""
echo "⚠️ Important next steps:"
echo "1. Change the default admin password"
echo "2. Configure your domain name"
echo "3. Setup SSL certificate with: sudo certbot --nginx"
echo "4. Configure email settings in .env file"
echo ""
echo "🎉 PKKI ITERA is now ready to use!"
