# PKKI ITERA Laravel Deployment Guide

This comprehensive guide covers three deployment methods for the PKKI ITERA Laravel application:

1. **VPS Deployment** - Deploy to a Virtual Private Server with full control
2. **cPanel Deployment** - Deploy to shared hosting with cPanel interface
3. **Docker Deployment** - Containerized deployment for any environment

## Table of Contents

- [Prerequisites](#prerequisites)
- [Project Requirements](#project-requirements)
- [Choose Your Deployment Method](#-choose-your-deployment-method)
- [Quick Setup Commands](#quick-setup-commands)
- [Next Steps](#next-steps)
- [Common Post-Deployment Tasks](#common-post-deployment-tasks)
- [Troubleshooting](#troubleshooting)
- [Security Best Practices](#security-best-practices)
- [Performance Optimization](#performance-optimization)
- [Backup & Monitoring](#backup--monitoring)

## Prerequisites

Before deploying, ensure you have:
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm/yarn
- MySQL 8.0+ or MariaDB 10.4+
- Git
- A domain name (recommended)
- SSL certificate (recommended for production)

## Project Requirements

Based on the composer.json, this project requires:
- Laravel 11.9+
- Filament 3.2+
- Inertia.js with React
- Spatie packages for permissions and media
- Various Filament plugins

---

## Quick Setup Commands

For quick reference, here are the essential commands for each deployment method:

### Laravel Application Setup (All Methods)
```bash
# Clone and install dependencies
git clone https://github.com/your-repo/pkki-itera.git
cd pkki-itera
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Setup environment and database
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan shield:generate --all
php artisan storage:link

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

---

## 🚀 Choose Your Deployment Method

| Method | Difficulty | Cost | Control | Scalability | Best For |
|--------|------------|------|---------|-------------|----------|
| **VPS** | Medium | Medium | High | High | Production, Custom needs |
| **cPanel** | Easy | Low | Low | Limited | Small projects, Quick setup |
| **Docker** | Hard | Variable | High | Very High | Development, CI/CD, Enterprise |

---

## Next Steps

1. **For VPS Deployment**: See [VPS_DEPLOYMENT.md](./VPS_DEPLOYMENT.md)
2. **For cPanel Deployment**: See [CPANEL_DEPLOYMENT.md](./CPANEL_DEPLOYMENT.md)  
3. **For Docker Deployment**: See [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md)

## Common Post-Deployment Tasks

After successful deployment with any method:

1. **Verify Installation**
   ```bash
   php artisan about
   php artisan route:list | grep admin
   ```

2. **Create Admin User**
   ```bash
   php artisan make:filament-user
   ```

3. **Assign Super Admin Role**
   ```bash
   php artisan shield:super-admin --user=admin@example.com
   ```

4. **Test Features**
   - Login to admin panel at `/admin`
   - Create test submission
   - Upload documents
   - Test email notifications

5. **Setup Monitoring**
   - Configure log monitoring
   - Setup uptime monitoring
   - Configure backup system

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

2. **Memory Limit Issues**
   ```ini
   memory_limit = 512M
   upload_max_filesize = 100M
   post_max_size = 100M
   ```

3. **Database Connection Issues**
   - Verify database credentials
   - Check firewall rules
   - Ensure MySQL is running

4. **Asset Loading Issues**
   ```bash
   npm run build
   php artisan storage:link
   php artisan config:cache
   ```

---

## Security Best Practices

### Environment Security
- ✅ Use strong passwords for all accounts
- ✅ Enable two-factor authentication where possible
- ✅ Keep `.env` file secure and never commit to version control
- ✅ Use HTTPS with valid SSL certificates
- ✅ Regular security updates

### Application Security
```bash
# Security headers (add to .htaccess or Nginx config)
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

### Database Security
- ✅ Use dedicated database user with minimal privileges
- ✅ Regular database backups
- ✅ Enable MySQL slow query log for monitoring
- ✅ Use database connection encryption if available

---

## Performance Optimization

### Application Performance
```bash
# Production optimizations
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
composer install --optimize-autoloader --no-dev

# Enable OPcache (add to php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Database Performance
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_submissions_status ON submissions(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_tracking_submission_created ON tracking_histories(submission_id, created_at);
```

### Web Server Performance
```nginx
# Nginx gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/javascript application/json;

# Browser caching
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

---

## Backup & Monitoring

### Automated Backups
```bash
#!/bin/bash
# Daily backup script
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/backups/pkki-itera"

# Database backup
mysqldump -u pkki_user -p pkki_itera_prod > "$BACKUP_DIR/database_$DATE.sql"

# Application files backup
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/pkki-itera --exclude=node_modules --exclude=vendor

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### Log Monitoring
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor system logs
sudo journalctl -f -u nginx
sudo journalctl -f -u php8.2-fpm
sudo journalctl -f -u mysql
```

### Health Checks
```bash
# Create health check endpoint
php artisan make:controller HealthController
```

Add to routes/web.php:
```php
Route::get('/health', [HealthController::class, 'check']);
```

---

## Support & Documentation

- **Main Documentation**: [README.md](../README.md)
- **API Documentation**: Available at `/admin` after deployment
- **Issue Tracking**: GitHub Issues
- **Security Issues**: Contact admin@pkki-itera.ac.id

---

**Last Updated**: May 31, 2025
**Laravel Version**: 11.9+
**Filament Version**: 3.2+
   npm run build
   php artisan storage:link
   ```

For detailed troubleshooting for each deployment method, see the respective deployment guides.
