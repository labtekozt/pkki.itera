# 🚨 PKKI ITERA Deployment Troubleshooting Guide

## Common Laravel Permission Errors

### Error: Permission denied for storage/logs/laravel.log
```
The stream or file "/var/www/pkki-itera/storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied
```

**🔧 Quick Fix:**
```bash
# On VPS server
sudo ./fix-vps-permissions.sh

# Or manually:
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
```

**🔍 Root Causes:**
1. Web server user (www-data) doesn't own the files
2. Directory permissions are too restrictive
3. SELinux blocking write access
4. Incorrect PHP-FPM user configuration

### Error: Permission denied for bootstrap/cache/config.php
```
file_put_contents(/var/www/pkki-itera/bootstrap/cache/config.php): Failed to open stream: Permission denied
```

**🔧 Quick Fix:**
```bash
# Fix bootstrap cache permissions
sudo chown -R www-data:www-data /var/www/pkki-itera/bootstrap/cache
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache

# Clear and rebuild cache
sudo -u www-data php /var/www/pkki-itera/artisan config:clear
sudo -u www-data php /var/www/pkki-itera/artisan config:cache
```

## Node.js Version Issues

### Error: Node.js version 12.x is too old
```
error This version of npm is compatible with Node.js versions ^16.0.0
```

**🔧 Fix for VPS:**
```bash
# Update Node.js to v18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version  # Should show v18.x
npm --version   # Should show v9.x+
```

**🔧 Alternative using NVM:**
```bash
# Install NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc

# Install and use Node.js 18
nvm install 18
nvm use 18
nvm alias default 18
```

## Database Connection Issues

### Error: SQLite database file not found
```
SQLSTATE[HY000] [14] unable to open database file
```

**🔧 Fix:**
```bash
# Create SQLite database file
touch /var/www/pkki-itera/database/database.sqlite
sudo chown www-data:www-data /var/www/pkki-itera/database/database.sqlite
sudo chmod 664 /var/www/pkki-itera/database/database.sqlite

# Run migrations
sudo -u www-data php /var/www/pkki-itera/artisan migrate --force
```

### Error: MySQL connection refused
```
SQLSTATE[HY000] [2002] Connection refused
```

**🔧 Fix:**
```bash
# Check MySQL service
sudo systemctl status mysql
sudo systemctl start mysql

# Verify MySQL configuration in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pkki_itera_prod
DB_USERNAME=pkki_user
DB_PASSWORD=your_password
```

## Frontend Build Issues

### Error: React build fails
```
npm ERR! code ELIFECYCLE
npm ERR! errno 1
```

**🔧 Fix:**
```bash
# Clean install
rm -rf node_modules package-lock.json
npm install

# Build with increased memory
NODE_OPTIONS="--max-old-space-size=4096" npm run build

# Alternative: Build locally and upload
npm run build  # Run on local machine
scp -r public/build user@vps:/var/www/pkki-itera/public/
```

## Nginx Configuration Issues

### Error: 502 Bad Gateway
```
nginx: [error] connect() to unix:/var/run/php/php8.2-fpm.sock failed
```

**🔧 Fix:**
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm
sudo systemctl start php8.2-fpm

# Verify socket file exists
ls -la /var/run/php/php8.2-fpm.sock

# Check Nginx configuration
sudo nginx -t
sudo systemctl restart nginx
```

### Error: Port 3003 access denied
```
nginx: [emerg] bind() to 0.0.0.0:3003 failed (13: Permission denied)
```

**🔧 Fix:**
```bash
# Check if port is available
sudo netstat -tlnp | grep :3003

# Allow Nginx to bind to port 3003
sudo setsebool -P httpd_can_network_connect 1  # SELinux
sudo ufw allow 3003  # UFW firewall

# Alternative: Use different port
# Edit /etc/nginx/sites-available/pkki-itera
# Change: listen 3003; to listen 8080;
```

## SSL Certificate Issues

### Error: Let's Encrypt fails
```
Challenge failed for domain hki.proyekai.com
```

**🔧 Fix:**
```bash
# Ensure domain points to server
dig hki.proyekai.com
nslookup hki.proyekai.com

# Stop Nginx during certificate generation
sudo systemctl stop nginx
sudo certbot certonly --standalone -d hki.proyekai.com
sudo systemctl start nginx

# Alternative: Use DNS challenge
sudo certbot certonly --manual --preferred-challenges dns -d hki.proyekai.com
```

## Filament Admin Panel Issues

### Error: Admin panel 404
```
The requested URL /admin was not found on this server
```

**🔧 Fix:**
```bash
# Clear and cache routes
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan route:cache

# Check if admin routes are registered
sudo -u www-data php artisan route:list | grep admin

# Verify Filament is properly installed
sudo -u www-data php artisan filament:upgrade
```

### Error: Super admin role not found
```
Role "super_admin" does not exist
```

**🔧 Fix:**
```bash
# Run role seeder
sudo -u www-data php artisan db:seed --class=RolesAndPermissionsSeeder

# Create super admin manually
sudo -u www-data php artisan make:filament-user
sudo -u www-data php artisan shield:super-admin --user=admin@example.com
```

## Performance Issues

### Error: 504 Gateway Timeout
```
nginx: upstream timed out (110: Connection timed out)
```

**🔧 Fix:**
```bash
# Increase PHP-FPM timeout
# Edit /etc/php/8.2/fpm/pool.d/www.conf
request_terminate_timeout = 300

# Increase Nginx timeout
# Edit /etc/nginx/sites-available/pkki-itera
proxy_read_timeout 300;
fastcgi_read_timeout 300;

# Restart services
sudo systemctl restart php8.2-fpm nginx
```

### Error: Out of memory
```
Fatal error: Allowed memory size exhausted
```

**🔧 Fix:**
```bash
# Increase PHP memory limit
# Edit /etc/php/8.2/fpm/php.ini
memory_limit = 512M

# Or set in .env
ini_set('memory_limit', '512M');

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

## Deployment Verification Checklist

### ✅ Pre-Deployment Checks
```bash
# 1. Check system requirements
php --version        # Should be 8.2+
node --version       # Should be 18+
mysql --version      # Should be 8.0+

# 2. Verify domain DNS
dig hki.proyekai.com
ping hki.proyekai.com

# 3. Check firewall
sudo ufw status
sudo iptables -L | grep 3003
```

### ✅ Post-Deployment Checks
```bash
# 1. Test application response
curl -I http://hki.proyekai.com:3003
curl -I https://hki.proyekai.com:3443

# 2. Check admin panel
curl -I http://hki.proyekai.com:3003/admin

# 3. Verify services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql

# 4. Check logs
tail -f /var/log/nginx/error.log
tail -f /var/www/pkki-itera/storage/logs/laravel.log
```

## Emergency Recovery Commands

### 🚨 Complete Reset
```bash
# 1. Stop services
sudo systemctl stop nginx php8.2-fpm

# 2. Fix permissions
sudo ./fix-vps-permissions.sh

# 3. Clear all caches
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear

# 4. Rebuild database
sudo -u www-data php artisan migrate:fresh --seed --force

# 5. Restart services
sudo systemctl start php8.2-fpm nginx
```

### 🚨 Rollback Deployment
```bash
# 1. Backup current state
sudo cp -r /var/www/pkki-itera /var/www/pkki-itera.backup

# 2. Restore from backup
sudo rm -rf /var/www/pkki-itera
sudo cp -r /var/www/pkki-itera.backup /var/www/pkki-itera

# 3. Fix permissions
sudo ./fix-vps-permissions.sh

# 4. Restart services
sudo systemctl restart nginx php8.2-fpm
```

## Contact Support

If these solutions don't resolve your issue:

1. **Check Laravel logs**: `tail -f /var/www/pkki-itera/storage/logs/laravel.log`
2. **Check Nginx logs**: `tail -f /var/log/nginx/error.log`
3. **Check PHP-FPM logs**: `tail -f /var/log/php8.2-fpm.log`
4. **System logs**: `journalctl -u nginx -f`

Include the relevant error messages when seeking help.

---

**Last Updated**: June 17, 2025  
**PKKI ITERA Version**: 1.0.0  
**Target Domain**: hki.proyekai.com:3003
