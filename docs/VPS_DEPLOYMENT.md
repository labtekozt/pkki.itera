# VPS Deployment Guide for PKKI ITERA Laravel Application

This guide walks you through deploying the PKKI ITERA application on a Virtual Private Server (VPS) using Ubuntu 22.04 LTS.

## Table of Contents

- [Overview](#-overview)
- [Step 1: Server Setup & Initial Configuration](#-step-1-server-setup--initial-configuration)
- [Step 2: Install PHP 8.2](#-step-2-install-php-82)
- [Step 3: Install MySQL](#-step-3-install-mysql)
- [Step 4: Install and Configure Nginx](#-step-4-install-and-configure-nginx)
- [Step 5: Install Node.js and Composer](#-step-5-install-nodejs-and-composer)
- [Step 6: Configure SSL with Let's Encrypt](#-step-6-configure-ssl-with-lets-encrypt)
- [Step 7: Deploy the Laravel Application](#-step-7-deploy-the-laravel-application)
- [Step 8: Set Up Process Monitoring](#-step-8-set-up-process-monitoring)
- [Step 9: Configure Backups](#-step-9-configure-backups)
- [Step 10: Final Security Hardening](#-step-10-final-security-hardening)
- [Maintenance and Updates](#maintenance-and-updates)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

**Advantages:**
- Full server control
- Custom configurations
- Better performance
- Scalability options
- SSH access

**Requirements:**
- VPS with Ubuntu 22.04 LTS (minimum 2GB RAM, 2 CPU cores, 20GB storage)
- Root or sudo access
- Domain name pointed to your VPS IP
- Basic Linux command line knowledge

---

## 🔧 Step 1: Server Setup & Initial Configuration

### 1.1 Connect to Your VPS
```bash
ssh root@your-server-ip
# or
ssh username@your-server-ip
```

### 1.2 Update System Packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common
```

### 1.3 Install Fail2Ban (Security)
```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 1.4 Configure Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw allow 3306  # MySQL (only if needed externally)
sudo ufw --force enable
```

---

## 🐘 Step 2: Install PHP 8.2

### 2.1 Add PHP Repository
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### 2.2 Install PHP and Extensions
```bash
sudo apt install -y php8.2 php8.2-fpm php8.2-common php8.2-mysql \
    php8.2-xml php8.2-xmlrpc php8.2-curl php8.2-gd \
    php8.2-imagick php8.2-cli php8.2-dev php8.2-imap \
    php8.2-mbstring php8.2-opcache php8.2-soap \
    php8.2-zip php8.2-intl php8.2-bcmath php8.2-redis
```

### 2.3 Configure PHP
```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Update these settings:
```ini
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_vars = 3000
date.timezone = Asia/Jakarta
```

### 2.4 Configure PHP-FPM
```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Update these settings:
```ini
user = www-data
group = www-data
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

### 2.5 Restart PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

---

## 🗄️ Step 3: Install MySQL

### 3.1 Install MySQL Server
```bash
sudo apt install -y mysql-server
```

### 3.2 Secure MySQL Installation
```bash
sudo mysql_secure_installation
```

Answer the prompts:
- Set root password: **Yes** (use a strong password)
- Remove anonymous users: **Yes**
- Disallow root login remotely: **Yes**
- Remove test database: **Yes**
- Reload privilege tables: **Yes**

### 3.3 Create Database and User
```bash
sudo mysql -u root -p
```

In MySQL console:
```sql
CREATE DATABASE pkki_itera_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pkki_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON pkki_itera_prod.* TO 'pkki_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🌐 Step 4: Install and Configure Nginx

### 4.1 Install Nginx
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 4.2 Create Nginx Virtual Host
```bash
sudo nano /etc/nginx/sites-available/pkki-itera
```

Add this configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/pkki-itera/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Security headers
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'";

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Security: Block access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.(env|log) {
        deny all;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security headers for PHP
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for large file uploads
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Security: Block access to Laravel directories
    location ~ ^/(storage|vendor|bootstrap/cache) {
        deny all;
    }

    # Allow larger file uploads
    client_max_body_size 100M;
    
    # Error and access logs
    error_log /var/log/nginx/pkki-itera_error.log;
    access_log /var/log/nginx/pkki-itera_access.log;
}
```

### 4.3 Enable the Site
```bash
sudo ln -s /etc/nginx/sites-available/pkki-itera /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 📦 Step 5: Install Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

---

## 📱 Step 6: Install Node.js and npm

### 6.1 Install Node.js 20 LTS
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 6.2 Verify Installation
```bash
node --version
npm --version
```

---

## 🚀 Step 7: Deploy the Laravel Application

### 7.1 Create Web Directory
```bash
sudo mkdir -p /var/www
cd /var/www
```

### 7.2 Clone the Repository
```bash
# Option 1: From GitHub (recommended)
sudo git clone https://github.com/your-username/pkki-itera.git pkki-itera

# Option 2: Upload via SCP/SFTP
# scp -r /path/to/local/pkki-itera user@server-ip:/var/www/
```

### 7.3 Set Proper Ownership
```bash
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo chmod -R 755 /var/www/pkki-itera
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
```

### 7.4 Navigate to Project Directory
```bash
cd /var/www/pkki-itera
```

### 7.5 Install PHP Dependencies
```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 7.6 Install Node Dependencies and Build Assets
```bash
sudo -u www-data npm install
sudo -u www-data npm run build
```

### 7.7 Create Environment File
```bash
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
```

Configure the .env file:
```env
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pkki_itera_prod
DB_USERNAME=pkki_user
DB_PASSWORD=your_strong_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail Configuration (update with your SMTP settings)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="PKKI ITERA"

# Filament Configuration
FILAMENT_FILESYSTEM_DISK=public

# App specific settings
APP_DESCRIPTION="Pusat Kerjasama dan Komersialisasi Inovasi Institut Teknologi Sumatera"
APP_KEYWORDS="pkki,itera,laravel,inertia,react,filament,kerjasama,inovasi,sumatera"
APP_AUTHOR="PKKI ITERA Team"
```

### 7.8 Generate Application Key
```bash
sudo -u www-data php artisan key:generate
```

### 7.9 Create Storage Link
```bash
sudo -u www-data php artisan storage:link
```

### 7.10 Run Database Migrations and Seeders
```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force
```

### 7.11 Generate Filament Shield Permissions
```bash
sudo -u www-data php artisan shield:generate --all
```

### 7.12 Optimize for Production
```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan icons:cache
```

---

## 🔒 Step 8: SSL Certificate with Let's Encrypt

### 8.1 Install Certbot
```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 8.2 Obtain SSL Certificate
```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 8.3 Test Auto-Renewal
```bash
sudo certbot renew --dry-run
```

### 8.4 Setup Auto-Renewal Cron Job
```bash
sudo crontab -e
```

Add this line:
```bash
0 12 * * * /usr/bin/certbot renew --quiet
```

---

## ⚙️ Step 9: Configure Laravel Queue Worker (Optional)

### 9.1 Create Systemd Service
```bash
sudo nano /etc/systemd/system/pkki-queue.service
```

Add this configuration:
```ini
[Unit]
Description=PKKI ITERA Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/pkki-itera/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/pkki-itera

[Install]
WantedBy=multi-user.target
```

### 9.2 Enable and Start Queue Service
```bash
sudo systemctl daemon-reload
sudo systemctl enable pkki-queue
sudo systemctl start pkki-queue
sudo systemctl status pkki-queue
```

---

## 📊 Step 10: Setup Monitoring and Logging

### 10.1 Install Log Rotation
```bash
sudo nano /etc/logrotate.d/pkki-itera
```

Add:
```
/var/www/pkki-itera/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
}
```

### 10.2 Setup Basic Monitoring Script
```bash
sudo nano /usr/local/bin/pkki-health-check.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Health Check Script
LOG_FILE="/var/log/pkki-health.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check if web server is responding
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://your-domain.com)

if [ $HTTP_STATUS -eq 200 ]; then
    echo "[$DATE] OK: Website is responding (HTTP $HTTP_STATUS)" >> $LOG_FILE
else
    echo "[$DATE] ERROR: Website is not responding (HTTP $HTTP_STATUS)" >> $LOG_FILE
    # You can add notification logic here (email, Slack, etc.)
fi

# Check database connection
cd /var/www/pkki-itera
DB_CHECK=$(sudo -u www-data php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';" 2>/dev/null)

if [[ $DB_CHECK == *"DB OK"* ]]; then
    echo "[$DATE] OK: Database connection successful" >> $LOG_FILE
else
    echo "[$DATE] ERROR: Database connection failed" >> $LOG_FILE
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    echo "[$DATE] WARNING: Disk usage is $DISK_USAGE%" >> $LOG_FILE
fi
```

### 10.3 Make Script Executable and Add to Cron
```bash
sudo chmod +x /usr/local/bin/pkki-health-check.sh
sudo crontab -e
```

Add:
```bash
*/5 * * * * /usr/local/bin/pkki-health-check.sh
```

---

## 🧪 Step 11: Testing and Verification

### 11.1 Create Admin User
```bash
cd /var/www/pkki-itera
sudo -u www-data php artisan make:filament-user
```

Follow prompts to create admin user.

### 11.2 Assign Super Admin Role
```bash
sudo -u www-data php artisan shield:super-admin --user=admin@example.com
```

### 11.3 Test Application Features
1. Visit your domain in browser
2. Access admin panel at `/admin`
3. Login with created admin user
4. Test submission creation
5. Test file uploads
6. Check email functionality

### 11.4 Performance Testing
```bash
# Test page load time
curl -o /dev/null -s -w "Total time: %{time_total}s\n" https://your-domain.com

# Test admin panel
curl -o /dev/null -s -w "Admin panel time: %{time_total}s\n" https://your-domain.com/admin
```

---

## 🔄 Step 12: Backup and Deployment Automation

### 12.1 Create Backup Script
```bash
sudo nano /usr/local/bin/pkki-backup.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Backup Script
BACKUP_DIR="/var/backups/pkki-itera"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_DIR="/var/www/pkki-itera"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u pkki_user -p'your_strong_password_here' pkki_itera_prod > $BACKUP_DIR/database_$DATE.sql

# Files backup (excluding node_modules and vendor)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www pkki-itera \
    --exclude='pkki-itera/node_modules' \
    --exclude='pkki-itera/vendor' \
    --exclude='pkki-itera/storage/logs' \
    --exclude='pkki-itera/storage/framework/cache' \
    --exclude='pkki-itera/storage/framework/sessions' \
    --exclude='pkki-itera/storage/framework/views'

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

### 12.2 Make Backup Script Executable
```bash
sudo chmod +x /usr/local/bin/pkki-backup.sh
```

### 12.3 Schedule Daily Backups
```bash
sudo crontab -e
```

Add:
```bash
0 2 * * * /usr/local/bin/pkki-backup.sh >> /var/log/pkki-backup.log 2>&1
```

### 12.4 Create Deployment Script for Updates
```bash
sudo nano /usr/local/bin/pkki-deploy.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Deployment Script
PROJECT_DIR="/var/www/pkki-itera"
cd $PROJECT_DIR

echo "Starting deployment..."

# Put application in maintenance mode
sudo -u www-data php artisan down

# Pull latest changes
sudo -u www-data git pull origin main

# Install/update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear and cache config
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Take application out of maintenance mode
sudo -u www-data php artisan up

echo "Deployment completed!"
```

### 12.5 Make Deployment Script Executable
```bash
sudo chmod +x /usr/local/bin/pkki-deploy.sh
```

---

## 🛠️ Troubleshooting Common Issues

### Permission Issues
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/pkki-itera

# Fix permissions
sudo find /var/www/pkki-itera -type f -exec chmod 644 {} \;
sudo find /var/www/pkki-itera -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
```

### PHP Memory Issues
```bash
# Increase PHP memory limit
sudo nano /etc/php/8.2/fpm/php.ini
# Set: memory_limit = 1024M

sudo systemctl restart php8.2-fpm
```

### Database Connection Issues
```bash
# Test database connection
mysql -u pkki_user -p pkki_itera_prod

# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate manually
sudo certbot renew

# Check Nginx SSL configuration
sudo nginx -t
```

### Performance Issues
```bash
# Check server resources
htop
df -h
free -m

# Optimize MySQL
sudo mysql_secure_installation

# Enable OPcache
sudo nano /etc/php/8.2/fpm/php.ini
# Uncomment and configure opcache settings
```

---

## 📈 Performance Optimization

### Enable OPcache
```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Add/uncomment:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Configure MySQL for Better Performance
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add:
```ini
[mysqld]
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
query_cache_size = 64M
query_cache_limit = 2M
```

Restart services:
```bash
sudo systemctl restart mysql
sudo systemctl restart php8.2-fpm
```

---

## ✅ Post-Deployment Checklist

- [ ] Application loads correctly at your domain
- [ ] Admin panel accessible at `/admin`
- [ ] SSL certificate is working (https://)
- [ ] Admin user created and can login
- [ ] Database migrations completed
- [ ] File uploads working
- [ ] Email notifications working
- [ ] Queue worker running (if enabled)
- [ ] Backups scheduled and working
- [ ] Monitoring script active
- [ ] All services start on boot

---

## 🔐 Security Hardening (Recommended)

### Disable Root SSH Login
```bash
sudo nano /etc/ssh/sshd_config
```

Set:
```
PermitRootLogin no
PasswordAuthentication no  # If using SSH keys
```

### Install Additional Security Tools
```bash
# Install intrusion detection
sudo apt install -y aide

# Install rootkit scanner
sudo apt install -y rkhunter

# Configure automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

**🎉 Congratulations! Your PKKI ITERA Laravel application is now successfully deployed on your VPS!**

For ongoing maintenance, regularly run backups, monitor logs, and keep your system updated with security patches.
