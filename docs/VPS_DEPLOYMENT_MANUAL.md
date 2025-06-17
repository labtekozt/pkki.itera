# PKKI ITERA VPS Deployment Guide

## Step 1: Persiapan VPS

Jalankan perintah berikut di VPS Anda:

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mbstring php8.2-bcmath php8.2-intl composer nodejs npm git unzip curl

# Install Redis (optional, for caching)
sudo apt install -y redis-server

# Start services
sudo systemctl start nginx
sudo systemctl start mysql
sudo systemctl start php8.2-fpm
sudo systemctl enable nginx
sudo systemctl enable mysql
sudo systemctl enable php8.2-fpm
```

## Step 2: Secure MySQL

```bash
sudo mysql_secure_installation
```

## Step 3: Create Database

```bash
sudo mysql -u root -p
```

Dalam MySQL prompt:
```sql
CREATE DATABASE pkki_itera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pkki_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON pkki_itera.* TO 'pkki_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 4: Deploy Application

```bash
# Create directory
sudo mkdir -p /var/www/pkki-itera
sudo chown -R $USER:$USER /var/www/pkki-itera

# Clone repository
git clone https://github.com/labtekozt/pkki.itera.git /var/www/pkki-itera
cd /var/www/pkki-itera

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install

# Setup environment
cp .env.example .env
nano .env  # Edit with your database credentials

# Generate key
php artisan key:generate

# Build assets
npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Create storage link
php artisan storage:link

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force

# Generate Filament resources
php artisan shield:generate --all

# Cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 5: Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/pkki-itera
```

Add this content:

```nginx
server {
    listen 80;
    server_name YOUR_DOMAIN_OR_IP;
    root /var/www/pkki-itera/public;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Laravel specific
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
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

    client_max_body_size 50M;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## Step 6: Configure PHP

```bash
sudo nano /etc/php/8.2/fpm/conf.d/99-pkki.ini
```

Add:

```ini
; PKKI ITERA PHP Configuration
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_file_uploads = 20

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60

; Security
expose_php = Off
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

## Step 7: Setup SSL (Optional)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d YOUR_DOMAIN

# Test renewal
sudo certbot renew --dry-run
```

## Step 8: Setup Cron Jobs

```bash
crontab -e
```

Add:
```bash
* * * * * cd /var/www/pkki-itera && php artisan schedule:run >> /dev/null 2>&1
```

## Step 9: Setup Queue Worker (Optional)

Create systemd service:

```bash
sudo nano /etc/systemd/system/pkki-queue.service
```

Add:
```ini
[Unit]
Description=PKKI ITERA Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/pkki-itera/artisan queue:work --sleep=3 --tries=3 --timeout=60

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable pkki-queue
sudo systemctl start pkki-queue
```

## Step 10: Final Steps

1. Update your `.env` file with production settings:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Configure mail settings
   - Set strong `APP_KEY`

2. Test your application at your domain/IP

3. Create admin user:
```bash
cd /var/www/pkki-itera
php artisan make:filament-user
```

## Maintenance Commands

```bash
# Update application
cd /var/www/pkki-itera
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

## Backup Strategy

```bash
# Database backup
mysqldump -u pkki_user -p pkki_itera > backup_$(date +%Y%m%d_%H%M%S).sql

# Files backup
tar -czf pkki_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/pkki-itera
```

## Monitoring

Check logs:
```bash
# Nginx logs
sudo tail -f /var/log/nginx/error.log

# PHP logs
sudo tail -f /var/log/php8.2-fpm.log

# Laravel logs
tail -f /var/www/pkki-itera/storage/logs/laravel.log
```

## Security Checklist

- [ ] Change default passwords
- [ ] Setup firewall (ufw)
- [ ] Configure fail2ban
- [ ] Regular security updates
- [ ] Monitor logs
- [ ] Setup backup automation
- [ ] SSL certificate installed
- [ ] File permissions set correctly
