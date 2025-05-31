# Docker Deployment Guide for PKKI ITERA Laravel Application

This guide walks you through deploying the PKKI ITERA application using Docker containers for maximum flexibility and scalability.

## Table of Contents

- [Overview](#-overview)
- [Step 1: Docker Environment Setup](#-step-1-docker-environment-setup)
- [Step 2: Create Docker Configuration](#-step-2-create-docker-configuration)
- [Step 3: Build and Deploy](#-step-3-build-and-deploy)
- [Step 4: SSL Configuration](#-step-4-ssl-configuration)
- [Step 5: Monitoring Setup](#-step-5-monitoring-setup)
- [Step 6: Backup Configuration](#-step-6-backup-configuration)
- [Step 7: Production Optimization](#-step-7-production-optimization)
- [Step 8: CI/CD Integration](#-step-8-cicd-integration)
- [Scaling and Load Balancing](#scaling-and-load-balancing)
- [Maintenance and Updates](#maintenance-and-updates)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

**Advantages:**
- Consistent environment across development/production
- Easy scaling and load balancing
- Container orchestration with Docker Compose
- Isolated dependencies
- Easy rollbacks and updates
- CI/CD friendly

**Requirements:**
- Docker 20.10+ and Docker Compose 2.0+
- VPS or cloud server with minimum 4GB RAM
- Basic understanding of Docker concepts
- Domain name (recommended)

---

## 🐳 Step 1: Docker Environment Setup

### 1.1 Install Docker on Your Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### 1.2 Create Project Directory
```bash
mkdir -p /opt/pkki-itera
cd /opt/pkki-itera
```

---

## 📁 Step 2: Create Docker Configuration Files

### 2.1 Create Dockerfile for Laravel Application
```bash
nano Dockerfile
```

Add the following content:
```dockerfile
# Use official PHP 8.2 FPM image
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    supervisor \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
RUN apt-get install -y nodejs

# Create user for Laravel
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www:www . /var/www/html

# Set proper permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

# Install Node dependencies and build assets
RUN npm install && npm run build && npm cache clean --force

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/local.ini $PHP_INI_DIR/conf.d/local.ini
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Configure supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

### 2.2 Create Docker Compose Configuration
```bash
nano docker-compose.yml
```

Add the following content:
```yaml
version: '3.8'

services:
  # Laravel Application
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: pkki-itera-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - pkki-network
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=database
      - DB_DATABASE=pkki_itera_prod
      - DB_USERNAME=pkki_user
      - DB_PASSWORD=pkki_secure_password
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - QUEUE_CONNECTION=redis

  # Nginx Web Server
  webserver:
    image: nginx:alpine
    container_name: pkki-itera-webserver
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
      - ./docker/nginx/ssl/:/etc/nginx/ssl/
      - ./docker/logs/nginx/:/var/log/nginx/
    networks:
      - pkki-network
    depends_on:
      - app

  # MySQL Database
  database:
    image: mysql:8.0
    container_name: pkki-itera-database
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: pkki_itera_prod
      MYSQL_USER: pkki_user
      MYSQL_PASSWORD: pkki_secure_password
      MYSQL_ROOT_PASSWORD: root_secure_password
      SERVICE_TAGS: dev
      SERVICE_NAME: mysql
    volumes:
      - dbdata:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"
    networks:
      - pkki-network

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: pkki-itera-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redisdata:/data
      - ./docker/redis/redis.conf:/etc/redis/redis.conf
    command: redis-server /etc/redis/redis.conf
    networks:
      - pkki-network

  # Queue Worker
  queue:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: pkki-itera-queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./:/var/www/html
    networks:
      - pkki-network
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis

  # Scheduler (Laravel Cron)
  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: pkki-itera-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    volumes:
      - ./:/var/www/html
    networks:
      - pkki-network
    depends_on:
      - database
      - redis
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis

  # PHPMyAdmin (Optional - for database management)
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: pkki-itera-phpmyadmin
    restart: unless-stopped
    environment:
      PMA_HOST: database
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: root_secure_password
    ports:
      - "8080:80"
    networks:
      - pkki-network
    depends_on:
      - database

# Docker Networks
networks:
  pkki-network:
    driver: bridge

# Volumes
volumes:
  dbdata:
    driver: local
  redisdata:
    driver: local
```

### 2.3 Create Docker Environment File
```bash
nano .env.docker
```

Add the following content:
```env
# Application
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=pkki_itera_prod
DB_USERNAME=pkki_user
DB_PASSWORD=pkki_secure_password

# Cache & Sessions
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="PKKI ITERA"

# Filament
FILAMENT_FILESYSTEM_DISK=public

# App Specific
APP_DESCRIPTION="Pusat Kerjasama dan Komersialisasi Inovasi Institut Teknologi Sumatera"
APP_KEYWORDS="pkki,itera,laravel,inertia,react,filament,kerjasama,inovasi,sumatera"
APP_AUTHOR="PKKI ITERA Team"
```

---

## 🔧 Step 3: Create Supporting Configuration Files

### 3.1 Create Docker Directory Structure
```bash
mkdir -p docker/{nginx/conf.d,nginx/ssl,php,mysql,redis,logs/nginx,supervisor}
```

### 3.2 PHP Configuration
```bash
nano docker/php/local.ini
```

Add:
```ini
upload_max_filesize=100M
post_max_size=100M
memory_limit=512M
max_execution_time=300
max_input_vars=3000
date.timezone=Asia/Jakarta
```

```bash
nano docker/php/opcache.ini
```

Add:
```ini
opcache.enable=1
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.max_accelerated_files=10000
opcache.memory_consumption=192
opcache.max_wasted_percentage=10
opcache.interned_strings_buffer=16
opcache.fast_shutdown=1
```

### 3.3 Nginx Configuration
```bash
nano docker/nginx/conf.d/default.conf
```

Add:
```nginx
upstream app {
    server app:9000;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS Server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/html/public;
    index index.php index.html index.htm;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/x-javascript
        application/xml+rss
        application/javascript
        application/json
        application/xml
        image/svg+xml;

    # Laravel Routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP Processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        
        # Increase timeouts
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        
        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Static Files Caching
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Pragma public;
        add_header Vary Accept-Encoding;
        try_files $uri =404;
    }

    # Security: Block access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.(env|log) {
        deny all;
    }

    location ~ ^/(storage|vendor|bootstrap/cache) {
        deny all;
    }

    # File upload limit
    client_max_body_size 100M;

    # Logging
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}
```

### 3.4 MySQL Configuration
```bash
nano docker/mysql/my.cnf
```

Add:
```ini
[mysqld]
general_log = 1
general_log_file = /var/lib/mysql/general.log
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 120
max_allowed_packet = 100M
key_buffer_size = 256M
query_cache_size = 64M
query_cache_limit = 2M
table_open_cache = 2000
thread_cache_size = 8
tmp_table_size = 64M
max_heap_table_size = 64M
```

### 3.5 Redis Configuration
```bash
nano docker/redis/redis.conf
```

Add:
```redis
# Basic configuration
bind 0.0.0.0
port 6379
timeout 0
tcp-keepalive 300

# Memory management
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Logging
loglevel notice
logfile ""

# Security
protected-mode no
```

### 3.6 Supervisor Configuration
```bash
nano docker/supervisor/supervisord.conf
```

Add:
```ini
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=false
startretries=0

[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue.log
```

### 3.7 Entrypoint Script
```bash
nano docker/entrypoint.sh
```

Add:
```bash
#!/bin/bash

# Exit on any error
set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database connection..."
    while ! php artisan migrate:status >/dev/null 2>&1; do
        echo "Database not ready, waiting..."
        sleep 2
    done
    echo "Database is ready!"
}

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public

# Set proper permissions
chown -R www:www /var/www/html/storage
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Wait for database to be ready
wait_for_db

# Run migrations only if we're the main app container
if [ "$1" = "php-fpm" ]; then
    echo "Running Laravel setup..."
    
    # Generate app key if not exists
    if [ -z "$APP_KEY" ]; then
        php artisan key:generate --force
    fi
    
    # Run migrations
    php artisan migrate --force
    
    # Run seeders
    php artisan db:seed --force
    
    # Generate Filament Shield permissions
    php artisan shield:generate --all --force
    
    # Create storage link
    php artisan storage:link --force
    
    # Optimize for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan icons:cache
    
    echo "Laravel setup completed!"
fi

# Execute the main command
exec "$@"
```

---

## 🚀 Step 4: Deploy the Application

### 4.1 Clone Your Project
```bash
cd /opt/pkki-itera
git clone https://github.com/your-username/pkki-itera.git .
```

### 4.2 Copy Environment File
```bash
cp .env.docker .env
```

### 4.3 Generate SSL Certificates
For production, use Let's Encrypt:
```bash
# Create SSL directory
mkdir -p docker/nginx/ssl

# Generate self-signed certificates for testing
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout docker/nginx/ssl/key.pem \
    -out docker/nginx/ssl/cert.pem \
    -subj "/C=ID/ST=Lampung/L=Lampung Selatan/O=ITERA/OU=PKKI/CN=your-domain.com"

# For production, use Certbot with Docker
# docker run -it --rm --name certbot \
#     -v "/opt/pkki-itera/docker/nginx/ssl:/etc/letsencrypt" \
#     -v "/var/lib/letsencrypt:/var/lib/letsencrypt" \
#     certbot/certbot certonly --standalone -d your-domain.com -d www.your-domain.com
```

### 4.4 Build and Start Containers
```bash
# Build images
docker-compose build

# Start services
docker-compose up -d

# Check status
docker-compose ps
```

### 4.5 Generate Application Key
```bash
docker-compose exec app php artisan key:generate
```

### 4.6 Create Admin User
```bash
docker-compose exec app php artisan make:filament-user
```

### 4.7 Assign Super Admin Role
```bash
docker-compose exec app php artisan shield:super-admin --user=admin@example.com
```

---

## 🔧 Step 5: Production Optimizations

### 5.1 Enable Docker Swarm (Optional - for clustering)
```bash
# Initialize swarm
docker swarm init

# Create overlay network
docker network create -d overlay pkki-network-swarm

# Deploy stack
docker stack deploy -c docker-compose.yml pkki-stack
```

### 5.2 Add Monitoring with Portainer
```bash
# Add monitoring service to docker-compose.yml
nano docker-compose.monitoring.yml
```

Add:
```yaml
version: '3.8'

services:
  # Portainer for container management
  portainer:
    image: portainer/portainer-ce:latest
    container_name: pkki-portainer
    restart: unless-stopped
    ports:
      - "9000:9000"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - portainer_data:/data
    networks:
      - pkki-network

  # Watchtower for automatic updates
  watchtower:
    image: containrrr/watchtower
    container_name: pkki-watchtower
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    environment:
      - WATCHTOWER_POLL_INTERVAL=300
      - WATCHTOWER_CLEANUP=true
    networks:
      - pkki-network

volumes:
  portainer_data:
```

Start monitoring:
```bash
docker-compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d
```

### 5.3 Setup Log Management
```bash
# Configure log rotation
sudo nano /etc/logrotate.d/docker-pkki
```

Add:
```
/opt/pkki-itera/docker/logs/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 root root
    postrotate
        docker kill --signal="USR1" pkki-itera-webserver
    endscript
}
```

### 5.4 Performance Monitoring Script
```bash
nano docker/scripts/health-check.sh
```

Add:
```bash
#!/bin/bash

# Health check script for PKKI ITERA Docker deployment
LOG_FILE="/opt/pkki-itera/docker/logs/health-check.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check container health
check_container_health() {
    local container_name=$1
    local status=$(docker inspect --format='{{.State.Health.Status}}' $container_name 2>/dev/null || echo "not_found")
    
    if [ "$status" = "healthy" ] || [ "$status" = "not_found" ]; then
        echo "[$DATE] OK: $container_name is running" >> $LOG_FILE
    else
        echo "[$DATE] ERROR: $container_name is unhealthy" >> $LOG_FILE
        # Restart unhealthy container
        docker restart $container_name
    fi
}

# Check application response
check_app_response() {
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
    if [ $response -eq 200 ]; then
        echo "[$DATE] OK: Application responding (HTTP $response)" >> $LOG_FILE
    else
        echo "[$DATE] ERROR: Application not responding (HTTP $response)" >> $LOG_FILE
    fi
}

# Check database connection
check_database() {
    local db_status=$(docker-compose exec -T database mysqladmin ping -h localhost -u root -proot_secure_password 2>/dev/null)
    if [[ $db_status == *"mysqld is alive"* ]]; then
        echo "[$DATE] OK: Database is responding" >> $LOG_FILE
    else
        echo "[$DATE] ERROR: Database is not responding" >> $LOG_FILE
    fi
}

# Check disk space
check_disk_space() {
    local disk_usage=$(df /opt/pkki-itera | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ $disk_usage -gt 85 ]; then
        echo "[$DATE] WARNING: Disk usage is $disk_usage%" >> $LOG_FILE
    fi
}

# Run checks
check_container_health "pkki-itera-app"
check_container_health "pkki-itera-webserver"
check_container_health "pkki-itera-database"
check_container_health "pkki-itera-redis"
check_app_response
check_database
check_disk_space
```

Make executable and schedule:
```bash
chmod +x docker/scripts/health-check.sh
crontab -e
```

Add:
```bash
*/5 * * * * /opt/pkki-itera/docker/scripts/health-check.sh
```

---

## 🔄 Step 6: Backup and Recovery

### 6.1 Automated Backup Script
```bash
nano docker/scripts/backup.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Docker Backup Script
BACKUP_DIR="/opt/backups/pkki-itera"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
echo "Creating database backup..."
docker-compose exec -T database mysqldump -u root -proot_secure_password pkki_itera_prod > $BACKUP_DIR/database_$DATE.sql

# Redis backup
echo "Creating Redis backup..."
docker-compose exec -T redis redis-cli BGSAVE
docker cp pkki-itera-redis:/data/dump.rdb $BACKUP_DIR/redis_$DATE.rdb

# Application files backup
echo "Creating application files backup..."
tar -czf $BACKUP_DIR/app_files_$DATE.tar.gz \
    -C /opt/pkki-itera \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='docker/logs' \
    --exclude='.git' \
    .

# Docker volumes backup
echo "Creating volumes backup..."
docker run --rm -v pkki-itera_dbdata:/data -v $BACKUP_DIR:/backup alpine tar -czf /backup/dbdata_$DATE.tar.gz -C /data .
docker run --rm -v pkki-itera_redisdata:/data -v $BACKUP_DIR:/backup alpine tar -czf /backup/redisdata_$DATE.tar.gz -C /data .

# Clean old backups (keep 7 days)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.rdb" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

### 6.2 Recovery Script
```bash
nano docker/scripts/restore.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Docker Recovery Script
BACKUP_DIR="/opt/backups/pkki-itera"

if [ -z "$1" ]; then
    echo "Usage: $0 <backup_date>"
    echo "Available backups:"
    ls -la $BACKUP_DIR/database_*.sql | awk '{print $9}' | sed 's/.*database_\(.*\)\.sql/\1/'
    exit 1
fi

BACKUP_DATE=$1

echo "Stopping services..."
docker-compose down

echo "Restoring database..."
docker-compose up -d database
sleep 10
docker-compose exec -T database mysql -u root -proot_secure_password pkki_itera_prod < $BACKUP_DIR/database_$BACKUP_DATE.sql

echo "Restoring Redis data..."
docker cp $BACKUP_DIR/redis_$BACKUP_DATE.rdb pkki-itera-redis:/data/dump.rdb

echo "Restoring application files..."
cd /opt/pkki-itera
tar -xzf $BACKUP_DIR/app_files_$BACKUP_DATE.tar.gz

echo "Starting all services..."
docker-compose up -d

echo "Recovery completed for backup: $BACKUP_DATE"
```

Make scripts executable:
```bash
chmod +x docker/scripts/backup.sh
chmod +x docker/scripts/restore.sh
```

Schedule daily backups:
```bash
crontab -e
```

Add:
```bash
0 2 * * * /opt/pkki-itera/docker/scripts/backup.sh >> /var/log/pkki-backup.log 2>&1
```

---

## 🔧 Step 7: CI/CD Integration

### 7.1 GitHub Actions Workflow
```bash
mkdir -p .github/workflows
nano .github/workflows/deploy.yml
```

Add:
```yaml
name: Deploy PKKI ITERA

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: pkki_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, json, zip
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Run tests
      run: php artisan test
      env:
        DB_HOST: 127.0.0.1
        DB_DATABASE: pkki_test
        DB_USERNAME: root
        DB_PASSWORD: root

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /opt/pkki-itera
          git pull origin main
          docker-compose down
          docker-compose build --no-cache
          docker-compose up -d
          docker-compose exec -T app php artisan migrate --force
          docker-compose exec -T app php artisan config:cache
          docker-compose exec -T app php artisan route:cache
          docker-compose exec -T app php artisan view:cache
```

### 7.2 Deployment Script
```bash
nano docker/scripts/deploy.sh
```

Add:
```bash
#!/bin/bash

# PKKI ITERA Docker Deployment Script
set -e

echo "Starting deployment..."

# Pull latest changes
git pull origin main

# Create backup before deployment
./docker/scripts/backup.sh

# Build new images
docker-compose build --no-cache

# Stop services
docker-compose down

# Start services
docker-compose up -d

# Wait for database
sleep 30

# Run migrations
docker-compose exec -T app php artisan migrate --force

# Optimize application
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Health check
./docker/scripts/health-check.sh

echo "Deployment completed successfully!"
```

Make executable:
```bash
chmod +x docker/scripts/deploy.sh
```

---

## 🔍 Step 8: Monitoring and Logging

### 8.1 Add ELK Stack for Logging (Optional)
```bash
nano docker-compose.logging.yml
```

Add:
```yaml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.8.0
    container_name: pkki-elasticsearch
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    ports:
      - "9200:9200"
    networks:
      - pkki-network

  logstash:
    image: docker.elastic.co/logstash/logstash:8.8.0
    container_name: pkki-logstash
    volumes:
      - ./docker/logstash/config:/usr/share/logstash/config
      - ./docker/logs:/logs
    ports:
      - "5044:5044"
    networks:
      - pkki-network
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.8.0
    container_name: pkki-kibana
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    ports:
      - "5601:5601"
    networks:
      - pkki-network
    depends_on:
      - elasticsearch

volumes:
  elasticsearch_data:
```

### 8.2 Prometheus Monitoring
```bash
nano docker-compose.monitoring.yml
```

Add:
```yaml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: pkki-prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./docker/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
    networks:
      - pkki-network

  grafana:
    image: grafana/grafana:latest
    container_name: pkki-grafana
    ports:
      - "3000:3000"
    volumes:
      - grafana_data:/var/lib/grafana
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin123
    networks:
      - pkki-network

  node-exporter:
    image: prom/node-exporter:latest
    container_name: pkki-node-exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($$|/)'
    networks:
      - pkki-network

volumes:
  prometheus_data:
  grafana_data:
```

---

## 🧪 Step 9: Testing and Verification

### 9.1 Container Health Checks
```bash
# Check all containers are running
docker-compose ps

# Check logs
docker-compose logs app
docker-compose logs webserver
docker-compose logs database

# Test application
curl -I http://localhost
curl -I https://localhost
```

### 9.2 Performance Testing
```bash
# Install testing tools
docker run --rm -it alpine/curl curl -I http://localhost

# Load testing with Apache Bench
docker run --rm httpd:2.4-alpine ab -n 100 -c 10 http://your-domain.com/

# Database performance
docker-compose exec database mysql -u root -proot_secure_password -e "SHOW PROCESSLIST;"
```

### 9.3 Security Testing
```bash
# Check open ports
nmap -p- localhost

# SSL test
docker run --rm -it drwetter/testssl.sh https://your-domain.com

# Container security scan
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy image pkki-itera_app
```

---

## 🔒 Step 10: Security Hardening

### 10.1 Docker Security Configuration
```bash
nano docker/security/docker-daemon.json
```

Add:
```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "storage-driver": "overlay2",
  "userns-remap": "default",
  "no-new-privileges": true,
  "seccomp-profile": "/etc/docker/seccomp.json"
}
```

### 10.2 Network Security
```bash
# Create custom network with isolation
docker network create --driver bridge --subnet=172.20.0.0/16 pkki-secure-network

# Update docker-compose.yml to use secure network
# Add to all services:
networks:
  - pkki-secure-network
```

### 10.3 Secrets Management
```bash
# Initialize Docker Swarm for secrets
docker swarm init

# Create secrets
echo "pkki_secure_password" | docker secret create db_password -
echo "root_secure_password" | docker secret create db_root_password -

# Update docker-compose.yml to use secrets
```

---

## 📋 Step 11: Production Checklist

### 11.1 Pre-Production Checklist
- [ ] All containers build successfully
- [ ] Database migrations complete
- [ ] SSL certificates installed
- [ ] Admin user created
- [ ] Email configuration tested
- [ ] File uploads working
- [ ] Backup system configured
- [ ] Monitoring setup complete
- [ ] Security hardening applied
- [ ] Performance optimized

### 11.2 Go-Live Checklist
- [ ] DNS pointed to server
- [ ] SSL certificate valid
- [ ] All services running
- [ ] Health checks passing
- [ ] Logs being collected
- [ ] Backups scheduled
- [ ] Monitoring alerts configured
- [ ] Documentation updated

---

## 🛠️ Troubleshooting

### Common Docker Issues

**Container won't start:**
```bash
# Check logs
docker-compose logs service-name

# Check container status
docker inspect container-name
```

**Database connection issues:**
```bash
# Test database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

**Memory issues:**
```bash
# Check container resource usage
docker stats

# Increase memory limits in docker-compose.yml
```

**Network issues:**
```bash
# Check network connectivity
docker network ls
docker network inspect pkki-network
```

### Performance Issues

**High CPU usage:**
```bash
# Check container processes
docker-compose exec app top

# Optimize PHP-FPM
# Update docker/php/local.ini
```

**High memory usage:**
```bash
# Check memory usage
docker-compose exec app free -m

# Optimize cache settings
# Update redis configuration
```

**Slow database:**
```bash
# Check MySQL performance
docker-compose exec database mysqladmin processlist
docker-compose exec database mysqladmin extended-status
```

---

## 🚀 Advanced Features

### Auto-scaling with Docker Swarm
```bash
# Initialize swarm mode
docker swarm init

# Create overlay network
docker network create -d overlay pkki-overlay

# Deploy as stack
docker stack deploy -c docker-compose.yml pkki-stack

# Scale services
docker service scale pkki-stack_app=3
```

### Load Balancing with Traefik
```bash
nano docker-compose.traefik.yml
```

Add Traefik configuration for automatic load balancing and SSL management.

### Container Registry
Set up private registry for storing custom images:
```bash
docker run -d -p 5000:5000 --name registry registry:2
```

**🎉 Congratulations! Your PKKI ITERA Laravel application is now successfully deployed using Docker with enterprise-grade features including monitoring, backup, security, and scalability!**

This Docker setup provides maximum flexibility for development, staging, and production environments with easy scaling and maintenance capabilities.
