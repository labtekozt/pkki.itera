# Quick Deployment Commands Reference

This file contains quick reference commands for deploying PKKI ITERA Laravel application using different methods.

## Table of Contents

- [Environment Setup Commands](#-environment-setup-commands)
- [VPS Deployment Quick Commands](#-vps-deployment-quick-commands)
- [cPanel Deployment Quick Commands](#-cpanel-deployment-quick-commands)
- [Docker Deployment Quick Commands](#-docker-deployment-quick-commands)
- [Common Laravel Commands](#-common-laravel-commands)
- [Troubleshooting Commands](#-troubleshooting-commands)
- [Maintenance Commands](#-maintenance-commands)

## 🔧 Environment Setup Commands

### Check Requirements
```bash
# Check PHP version
php -v

# Check required PHP extensions
php -m | grep -E "(mysql|pdo|mbstring|xml|zip|gd|curl|intl|bcmath)"

# Check Composer
composer --version

# Check Node.js and npm
node --version
npm --version
```

## 🚀 VPS Deployment Quick Commands

### Server Setup (Ubuntu 22.04)
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-gd php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt-get install -y nodejs
```

### Application Deployment
```bash
# Clone project
cd /var/www && sudo git clone https://github.com/your-repo/pkki-itera.git

# Set permissions
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo chmod -R 755 /var/www/pkki-itera
sudo chmod -R 775 /var/www/pkki-itera/storage /var/www/pkki-itera/bootstrap/cache

# Install dependencies
cd /var/www/pkki-itera
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install && npm run build

# Setup environment
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan storage:link

# Database setup
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force
sudo -u www-data php artisan shield:generate --all

# Optimize for production
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### SSL with Let's Encrypt
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
sudo certbot renew --dry-run
```

## 🏠 cPanel Deployment Quick Commands

### Local Preparation
```bash
# Install dependencies locally
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Create deployment package
zip -r pkki-deploy.zip . -x "node_modules/*" ".git/*" "tests/*"
```

### Database Setup (via cPanel/phpMyAdmin)
```sql
CREATE DATABASE pkki_itera_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pkki_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON pkki_itera_prod.* TO 'pkki_user'@'localhost';
FLUSH PRIVILEGES;
```

### Quick Setup Scripts (create these files, run via browser, then delete)

**migrate.php**
```php
<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
use Illuminate\Support\Facades\Artisan;
Artisan::call('migrate', ['--force' => true]);
echo "Migrations completed!\n" . Artisan::output();
?>
```

**create-admin.php**
```php
<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::create([
    'fullname' => 'Administrator',
    'email' => 'admin@yourdomain.com',
    'password' => Hash::make('SecurePassword123!'),
    'email_verified_at' => now(),
]);
$admin->assignRole('super_admin');
echo "Admin created: admin@yourdomain.com / SecurePassword123!";
?>
```

## 🐳 Docker Deployment Quick Commands

### Prerequisites
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### Quick Deployment
```bash
# Clone project
git clone https://github.com/your-repo/pkki-itera.git /opt/pkki-itera
cd /opt/pkki-itera

# Setup environment
cp .env.docker .env

# Generate SSL certificates (self-signed for testing)
mkdir -p docker/nginx/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout docker/nginx/ssl/key.pem \
    -out docker/nginx/ssl/cert.pem \
    -subj "/C=ID/ST=Lampung/L=Lampung Selatan/O=ITERA/CN=yourdomain.com"

# Build and start
docker-compose build
docker-compose up -d

# Create admin user
docker-compose exec app php artisan make:filament-user
docker-compose exec app php artisan shield:super-admin --user=admin@example.com
```

### Docker Management Commands
```bash
# View logs
docker-compose logs -f app

# Restart services
docker-compose restart

# Update application
git pull origin main
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Backup database
docker-compose exec database mysqldump -u root -proot_password pkki_itera_prod > backup.sql

# Restore database
docker-compose exec -T database mysql -u root -proot_password pkki_itera_prod < backup.sql
```

## 🔄 Common Post-Deployment Commands

### Create Admin User
```bash
# VPS/Local
php artisan make:filament-user

# Docker
docker-compose exec app php artisan make:filament-user

# cPanel (via browser script - delete after use)
# Use create-admin.php script above
```

### Assign Super Admin Role
```bash
# VPS/Local
php artisan shield:super-admin --user=admin@example.com

# Docker
docker-compose exec app php artisan shield:super-admin --user=admin@example.com
```

### Clear Cache
```bash
# VPS/Local
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Docker
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan cache:clear
```

### Optimize for Production
```bash
# VPS/Local
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Docker
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan icons:cache
```

### Check Application Status
```bash
# VPS/Local
php artisan about
php artisan route:list | grep admin

# Docker
docker-compose exec app php artisan about
docker-compose exec app php artisan route:list | grep admin

# Check containers (Docker only)
docker-compose ps
docker-compose logs app
```

## 🚨 Emergency Commands

### Put Application in Maintenance Mode
```bash
# VPS/Local
php artisan down

# Docker
docker-compose exec app php artisan down

# Bring back up
php artisan up
# or
docker-compose exec app php artisan up
```

### Quick Backup
```bash
# VPS Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Docker database backup
docker-compose exec database mysqldump -u root -ppassword database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Files backup
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/project --exclude=node_modules --exclude=vendor
```

### Fix Permissions (VPS)
```bash
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo find /var/www/pkki-itera -type f -exec chmod 644 {} \;
sudo find /var/www/pkki-itera -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
```

### Restart Services
```bash
# VPS
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mysql

# Docker
docker-compose restart
# or restart specific service
docker-compose restart webserver
docker-compose restart app
```

## 📊 Monitoring Commands

### Check System Resources
```bash
# Memory usage
free -m

# Disk usage
df -h

# CPU usage
top

# Docker specific
docker stats
```

### Check Application Logs
```bash
# VPS/Local
tail -f storage/logs/laravel.log

# Docker
docker-compose logs -f app

# Nginx logs (VPS)
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Docker Nginx logs
docker-compose logs -f webserver
```

### Test Application
```bash
# Test HTTP response
curl -I http://yourdomain.com
curl -I https://yourdomain.com

# Test admin panel
curl -I http://yourdomain.com/admin

# Test with timing
curl -o /dev/null -s -w "Total time: %{time_total}s\n" https://yourdomain.com
```

## 🔧 Troubleshooting Commands

### Database Issues
```bash
# Test database connection
# VPS/Local
php artisan tinker
>>> DB::connection()->getPdo();

# Docker
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();

# Check MySQL status
# VPS
sudo systemctl status mysql

# Docker
docker-compose exec database mysqladmin ping
```

### Permission Issues
```bash
# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Fix common permission issues (VPS)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### SSL Issues
```bash
# Check certificate status (VPS)
sudo certbot certificates

# Test SSL configuration
openssl s_client -connect your-domain.com:443
```

---

## 🧹 Maintenance Commands

### Regular Maintenance (Run Weekly)
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update composer dependencies
composer install --optimize-autoloader --no-dev

# Rebuild frontend assets
npm run build
```

### Database Maintenance
```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Run database cleanup (if needed)
php artisan queue:prune-batches --hours=48
php artisan activitylog:clean

# Optimize database tables
mysql -u username -p -e "OPTIMIZE TABLE submissions, users, tracking_histories;"
```

### Log Management
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Clear old logs (keep last 30 days)
find storage/logs -name "*.log" -mtime +30 -delete

# Monitor system logs (VPS)
sudo journalctl -f -u nginx
sudo journalctl -f -u php8.2-fpm
```

### Security Updates
```bash
# VPS system updates
sudo apt update && sudo apt upgrade -y

# Update PHP packages
composer update --with-dependencies

# Update Node.js packages
npm audit fix
npm update
```

---

## 📋 Quick Checklists

### Pre-Deployment Checklist
- [ ] PHP 8.2+ installed with required extensions
- [ ] MySQL/MariaDB database created
- [ ] Domain name configured
- [ ] SSL certificate ready
- [ ] Backup strategy planned
- [ ] Environment variables configured

### Post-Deployment Checklist
- [ ] Application loads without errors
- [ ] Admin panel accessible at `/admin`
- [ ] Database migrations completed
- [ ] File uploads working
- [ ] Email notifications working
- [ ] Cron jobs scheduled
- [ ] SSL certificate active
- [ ] Monitoring configured
- [ ] Backups automated

### Performance Checklist
- [ ] OPcache enabled
- [ ] Application caches optimized
- [ ] Gzip compression enabled
- [ ] Browser caching configured
- [ ] Database indexes created
- [ ] CDN configured (if applicable)

---

## 📞 Support & Resources

### Getting Help
- **Documentation**: Check the detailed deployment guides in `/docs/`
- **Laravel Documentation**: [https://laravel.com/docs](https://laravel.com/docs)
- **Filament Documentation**: [https://filamentphp.com/docs](https://filamentphp.com/docs)
- **Inertia.js Documentation**: [https://inertiajs.com/](https://inertiajs.com/)

### Useful Commands Reference
```bash
# Check Laravel version
php artisan --version

# Check Filament version
composer show filament/filament

# Check system requirements
php -m | grep -E "(pdo|openssl|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|gd)"
```

---

**Last Updated**: May 31, 2025  
**Compatible with**: Laravel 11.9+, Filament 3.2+, PHP 8.2+

This quick reference should help you deploy and manage your PKKI ITERA Laravel application efficiently across all three deployment methods!
