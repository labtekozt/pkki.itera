# PKKI ITERA Deployment Scripts

Comprehensive deployment scripts for PKKI ITERA Laravel application with Supabase PostgreSQL integration.

## 📋 Overview

This collection provides a complete deployment automation suite with the following tools:

1. **🎯 Deployment Manager** (`deploy-manager.sh`) - Interactive main menu interface
2. **🧙 Configuration Wizard** (`supabase-config-wizard.sh`) - Interactive setup
3. **🚀 Full Deployment Script** (`deploy-supabase-setup.sh`) - Complete deployment automation
4. **⚡ Quick Deploy** (`quick-deploy.sh`) - Pre-configured scenarios
5. **🔍 Status Checker** (`check-deployment-status.sh`) - Health monitoring
6. **🔄 Rollback Tool** (`rollback-deployment.sh`) - Deployment rollback

## 🎯 Quick Start

### Option 1: Use the Deployment Manager (Recommended)

```bash
# Run the interactive deployment manager
./scripts/deploy-manager.sh
```

The deployment manager provides a user-friendly menu interface for all deployment operations.

### Option 2: Interactive Wizard (First-time setup)

```bash
# Run the interactive configuration wizard
./scripts/supabase-config-wizard.sh
```

### Option 3: Quick Deploy Scenarios

```bash
# Local development
./scripts/quick-deploy.sh local

# Production deployment
./scripts/quick-deploy.sh production --domain pkki.itera.ac.id --email admin@itera.ac.id

# Check deployment status
./scripts/check-deployment-status.sh
```

### Option 3: Manual Configuration

```bash
# Full production deployment with all options
sudo ./scripts/deploy-supabase-setup.sh \
    --env production \
    --domain pkki.itera.ac.id \
    --ssl-email admin@itera.ac.id \
    --supabase-url "https://your-project.supabase.co" \
    --supabase-anon "your-anon-key" \
    --supabase-service "your-service-key" \
    --db-host "your-db-host.supabase.com" \
    --db-name "postgres" \
    --db-user "postgres.your-username" \
    --db-pass "your-password"
```

## 📚 Script Details

### 🎯 Deployment Manager (`deploy-manager.sh`)

Interactive menu-driven interface for all deployment operations.

**Features:**
- User-friendly main menu
- All deployment operations in one place
- Guided workflows for complex tasks
- Quick access to monitoring and maintenance
- Built-in help and documentation

**Usage:**
```bash
./scripts/deploy-manager.sh
```

### 🧙 Configuration Wizard (`supabase-config-wizard.sh`)

Interactive script that collects all necessary configuration and generates deployment commands.

**Features:**
- Environment selection (development/staging/production)
- Domain and SSL configuration
- Supabase credentials collection
- Database connection testing
- Deployment command generation
- Optional immediate deployment

**Usage:**
```bash
./scripts/supabase-config-wizard.sh
```

### 🚀 Main Deployment Script (`deploy-supabase-setup.sh`)

Comprehensive deployment script with full customization options.

**Features:**
- Complete system setup (PHP, Node.js, Nginx, Redis)
- Supabase PostgreSQL configuration
- SSL certificate automation with Let's Encrypt
- Laravel optimization for production
- File permissions and security setup
- Health checks and monitoring
- Backup creation

**Usage:**
```bash
./scripts/deploy-supabase-setup.sh [OPTIONS]
```

**Key Options:**
- `--env ENV` - Environment (development/staging/production)
- `--domain DOMAIN` - Domain name for SSL and Nginx
- `--ssl-email EMAIL` - Email for SSL certificate registration
- `--supabase-url URL` - Supabase project URL
- `--db-host HOST` - Database host
- `--dry-run` - Show what would be done without executing
- `--skip-*` - Skip specific setup steps

### ⚡ Quick Deploy (`quick-deploy.sh`)

Pre-configured deployment scenarios for common use cases.

**Scenarios:**
- `local` - Local development setup
- `staging` - Staging server deployment  
- `production` - Production server deployment
- `nginx-only` - Only setup Nginx configuration
- `ssl-only` - Only setup SSL certificates
- `update` - Update existing deployment
- `wizard` - Run interactive configuration wizard

**Usage:**
```bash
./scripts/quick-deploy.sh SCENARIO [OPTIONS]
```

### 🔍 Status Checker (`check-deployment-status.sh`)

Comprehensive health check and status monitoring tool.

**Features:**
- System service status checking
- Database connectivity testing
- File permission verification
- Application health monitoring
- Log analysis and error detection
- Performance metrics
- Security configuration review

**Usage:**
```bash
./scripts/check-deployment-status.sh
```

### 🔄 Rollback Tool (`rollback-deployment.sh`)

Safe rollback system for deployment recovery.

**Features:**
- List available backups
- Interactive backup selection
- Selective restore (files/database)
- Pre-rollback safety backup
- Service management during rollback
- Post-rollback validation
- Health check after restore

**Usage:**
```bash
./scripts/rollback-deployment.sh [OPTIONS]
```

**Key Options:**
- `--list-backups` - Show available backups
- `--rollback-to BACKUP` - Specific backup to restore
- `--no-database` - Skip database restore
- `--no-files` - Skip file restore
- `--dry-run` - Preview rollback actions

## 🔧 Prerequisites

### System Requirements
- Ubuntu/Debian Linux (recommended)
- Root or sudo access
- Internet connection for package downloads

### Supabase Requirements
- Supabase project created
- Database credentials available
- API keys generated

## 📋 Supabase Setup

### 1. Create Supabase Project
1. Go to [supabase.com](https://supabase.com)
2. Create a new project
3. Note down your project URL

### 2. Get API Keys
1. Go to Project Settings > API
2. Copy the `anon` key (public key)
3. Copy the `service_role` key (secret key)

### 3. Get Database Credentials
1. Go to Project Settings > Database
2. Note down:
   - Host (e.g., `aws-0-ap-southeast-1.pooler.supabase.com`)
   - Database name (usually `postgres`)
   - Username (e.g., `postgres.your-project-id`)
   - Password

## 🌐 Domain Setup

### DNS Configuration
Point your domain to your server:
```
A record: pkki.itera.ac.id → YOUR_SERVER_IP
A record: www.pkki.itera.ac.id → YOUR_SERVER_IP
```

### SSL Certificate
The script automatically handles SSL certificates using Let's Encrypt:
- Requires domain to be pointing to your server
- Needs a valid email for certificate registration
- Auto-renewal is configured

## 🏗️ Deployment Environments

### Development
- Local development setup
- No SSL configuration
- Debug mode enabled
- Minimal security features

### Staging
- Production-like environment
- SSL enabled
- Testing and validation
- Some debug features

### Production
- Full security features
- SSL certificates
- Performance optimizations
- Monitoring and logging
- Backup systems

## 🔒 Security Features

### System Security
- PHP security configurations
- Nginx security headers
- File permission restrictions
- SSL/TLS encryption

### Application Security
- Environment variable protection
- Database connection security
- Session security configuration
- CSRF protection

## 📊 Monitoring & Logs

### Log Locations
```bash
# Application logs
/var/www/pkki-itera/storage/logs/

# Nginx logs
/var/log/nginx/

# PHP-FPM logs
/var/log/php8.3-fpm.log

# System logs
/var/log/syslog
```

### Health Checks
The deployment script includes automatic health checks:
- Database connectivity
- Web server status
- PHP-FPM status
- Redis connectivity
- Application response

## 🔄 Maintenance & Updates

### Update Application
```bash
# Update code and dependencies
./scripts/quick-deploy.sh update

# Or manually
cd /var/www/pkki-itera
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
```

### SSL Certificate Renewal
Automatic renewal is configured, but you can manually renew:
```bash
certbot renew --dry-run  # Test renewal
certbot renew            # Force renewal
```

### Backup
```bash
# Manual backup
tar -czf backup-$(date +%Y%m%d).tar.gz /var/www/pkki-itera
pg_dump -h host -U user database > backup-$(date +%Y%m%d).sql
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   ```bash
   # Check credentials
   psql -h your-host -U your-user -d your-database
   
   # Check firewall
   sudo ufw status
   ```

2. **SSL Certificate Failed**
   ```bash
   # Check DNS resolution
   dig +short your-domain.com
   
   # Check Nginx configuration
   nginx -t
   ```

3. **Permission Errors**
   ```bash
   # Fix Laravel permissions
   sudo chown -R www-data:www-data /var/www/pkki-itera
   sudo chmod -R 775 /var/www/pkki-itera/storage
   sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
   ```

4. **Application Not Loading**
   ```bash
   # Check logs
   tail -f /var/www/pkki-itera/storage/logs/laravel.log
   tail -f /var/log/nginx/error.log
   
   # Check services
   systemctl status nginx
   systemctl status php8.3-fpm
   ```

### Debug Mode
For troubleshooting, you can run scripts in debug mode:
```bash
DEBUG=true ./scripts/deploy-supabase-setup.sh [options]
```

### Dry Run
Test what would be executed without making changes:
```bash
./scripts/deploy-supabase-setup.sh --dry-run [options]
```

## 📞 Support

### Script Issues
- Check script logs and error messages
- Run with `--dry-run` to verify configuration
- Check system requirements and prerequisites

### Application Issues  
- Check Laravel logs in `storage/logs/`
- Verify database connection and migrations
- Check web server and PHP-FPM status

### Security Concerns
- Ensure credentials are properly secured
- Regular security updates
- Monitor access logs
- Use strong passwords

## 📝 Examples

### Complete Production Deployment
```bash
# Using wizard (recommended)
./scripts/supabase-config-wizard.sh

# Manual command
sudo ./scripts/deploy-supabase-setup.sh \
    --env production \
    --domain pkki.itera.ac.id \
    --ssl-email admin@itera.ac.id \
    --supabase-url "https://abcdefg.supabase.co" \
    --supabase-anon "eyJhbGc..." \
    --supabase-service "eyJhbGc..." \
    --db-host "aws-0-ap-southeast-1.pooler.supabase.com" \
    --db-name "postgres" \
    --db-user "postgres.abcdefg" \
    --db-pass "your-secure-password"
```

### Development Setup
```bash
# Local development
./scripts/quick-deploy.sh local

# Or with specific Supabase config
./scripts/deploy-supabase-setup.sh \
    --env development \
    --skip-nginx \
    --skip-ssl \
    --supabase-url "https://localhost:54321" \
    --db-host "localhost" \
    --db-port "54322"
```

### Nginx-Only Setup
```bash
# Setup Nginx for existing deployment
./scripts/quick-deploy.sh nginx-only --domain pkki.itera.ac.id

# Or manual
./scripts/deploy-supabase-setup.sh \
    --nginx-only \
    --domain pkki.itera.ac.id
```

## ⚖️ License

This deployment system is part of the PKKI ITERA project and follows the same licensing terms.

---

**🎉 Happy Deploying!**

For additional help, refer to the main project documentation or create an issue in the project repository.
