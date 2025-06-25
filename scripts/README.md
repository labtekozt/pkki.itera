# PKKI ITERA Deployment Scripts

Simple, best-practices deployment scripts for PKKI ITERA Laravel application.

## 🚀 Quick Start

### 1. Server Setup (Run once on new server)
```bash
# Download and run server setup (as root)
curl -fsSL https://raw.githubusercontent.com/your-repo/main/scripts/server-setup.sh | sudo bash

# Or clone repo first
git clone https://github.com/labtekozt/pkki.itera.git
cd pkki.itera
sudo ./scripts/server-setup.sh
```

### 2. Application Setup (Run once per environment)
```bash
# Setup environment and database
./scripts/setup.sh

# Follow the interactive prompts to:
# - Choose environment (production/staging/development)
# - Configure .env file
# - Setup database and permissions
# - Create admin user
```

### 3. Deploy Application (Run for each deployment)
```bash
# Deploy to production (default)
./scripts/deploy.sh

# Deploy to specific environment
./scripts/deploy.sh staging
./scripts/deploy.sh development

# Quick deploy (skip dependencies)
./scripts/deploy.sh --quick

# Deploy without backup
./scripts/deploy.sh --no-backup
```

## 📁 Script Overview

| Script | Purpose | When to Use |
|--------|---------|-------------|
| `server-setup.sh` | Install PHP, Nginx, PostgreSQL, etc. | Once per server |
| `setup.sh` | Configure environment and database | Once per environment |
| `deploy.sh` | Deploy application changes | Every deployment |

## 🔧 What Each Script Does

### server-setup.sh
- ✅ Installs PHP 8.3+ with all required extensions
- ✅ Installs Composer, Node.js, Nginx, PostgreSQL, Redis
- ✅ Configures basic security settings
- ✅ Sets up SSL tools (Certbot)
- ✅ Installs monitoring tools (htop, fail2ban, etc.)
- ✅ Creates example Nginx configuration

### setup.sh
- ✅ Creates environment-specific .env files
- ✅ Generates application key
- ✅ Creates database tables
- ✅ Runs migrations and seeders
- ✅ Sets up permissions and roles
- ✅ Creates admin user

### deploy.sh
- ✅ Pre-deployment checks
- ✅ Creates automatic backups
- ✅ Installs/updates dependencies
- ✅ Runs database migrations
- ✅ Optimizes application for environment
- ✅ Sets proper file permissions
- ✅ Restarts services
- ✅ Runs health checks

## 🛠️ Manual Commands

### Essential Laravel Commands
```bash
# Check application status
php artisan about

# Check database connection
php artisan migrate:status

# Create admin user
php artisan make:filament-user

# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize
```

### Server Management
```bash
# Check service status
sudo systemctl status nginx php8.3-fpm postgresql redis

# Restart services
sudo systemctl restart nginx php8.3-fpm

# View logs
sudo tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log

# Check disk space
df -h

# Monitor processes
htop
```

## 🌍 Environment Configurations

### Production
- `APP_ENV=production`
- `APP_DEBUG=false`
- Cache enabled (config, routes, views)
- Error logging only
- Supabase PostgreSQL database
- SMTP email
- SSL required

### Staging
- `APP_ENV=staging`
- `APP_DEBUG=false`
- Cache enabled
- Debug logging
- Supabase PostgreSQL database
- Mailtrap email (for testing)
- SSL recommended

### Development
- `APP_ENV=local`
- `APP_DEBUG=true`
- Cache disabled
- Debug logging
- Local PostgreSQL or Supabase
- Log email driver
- No SSL required

## 🔒 Security Best Practices

### Server Security
```bash
# Enable firewall
sudo ufw enable

# Configure fail2ban for SSH protection
sudo systemctl enable fail2ban

# Regular updates
sudo apt update && sudo apt upgrade

# Monitor failed logins
sudo tail -f /var/log/auth.log
```

### Application Security
```bash
# Generate strong app key
php artisan key:generate

# Set proper permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache

# Review .env file
# - Change default passwords
# - Use strong database passwords
# - Configure proper mail settings
```

## 🔧 Troubleshooting

### Common Issues

#### "Permission denied"
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/your-app
sudo chmod -R 775 storage bootstrap/cache
```

#### "Database connection failed"
```bash
# Check database credentials in .env
cat .env | grep DB_

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### "502 Bad Gateway"
```bash
# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Check Nginx configuration
sudo nginx -t

# Check logs
sudo tail -f /var/log/nginx/error.log
```

#### "Storage link not working"
```bash
# Recreate storage link
php artisan storage:link

# Check if link exists
ls -la public/storage
```

### Log Files to Check
```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log

# System logs
sudo journalctl -u nginx -f
sudo journalctl -u php8.3-fpm -f
```

## 📋 Deployment Checklist

### Before Deployment
- [ ] Update .env file with correct credentials
- [ ] Test database connection
- [ ] Backup important data
- [ ] Check disk space
- [ ] Verify SSL certificate status

### After Deployment
- [ ] Test application functionality
- [ ] Check admin panel access
- [ ] Verify email sending
- [ ] Test file uploads
- [ ] Monitor error logs
- [ ] Check performance

### Production Only
- [ ] SSL certificate active
- [ ] Firewall configured
- [ ] Backups working
- [ ] Monitoring active
- [ ] Error tracking configured

## 🆘 Emergency Recovery

### Restore from Backup
```bash
# List available backups
ls -la ~/backups/pkki-itera/

# Restore application files
cd /var/www
sudo tar -xzf ~/backups/pkki-itera/app_TIMESTAMP.tar.gz

# Restore database (if backed up)
psql -h your-host -U your-user your-database < ~/backups/pkki-itera/database_TIMESTAMP.sql
```

### Quick Recovery Commands
```bash
# Reset to working state
php artisan migrate:rollback
php artisan migrate
php artisan db:seed --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Fix permissions
sudo ./scripts/deploy.sh --quick
```

## 📞 Support

For issues with deployment:

1. Check the troubleshooting section above
2. Review log files for specific errors
3. Test individual components (database, web server, etc.)
4. Use the emergency recovery procedures if needed

Remember: Always test deployments on staging environment first!
