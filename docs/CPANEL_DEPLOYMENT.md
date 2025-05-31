# cPanel Deployment Guide for PKKI ITERA Laravel Application

This guide walks you through deploying the PKKI ITERA application on shared hosting with cPanel interface.

## Table of Contents

- [Overview](#-overview)
- [Step 1: Verify Hosting Requirements](#-step-1-verify-hosting-requirements)
- [Step 2: Prepare Application Files](#-step-2-prepare-application-files)
- [Step 3: Database Setup](#-step-3-database-setup)
- [Step 4: Upload Application Files](#-step-4-upload-application-files)
- [Step 5: Configure Application](#-step-5-configure-application)
- [Step 6: Set Up Cron Jobs](#-step-6-set-up-cron-jobs)
- [Step 7: Configure Email](#-step-7-configure-email)
- [Step 8: SSL Certificate Setup](#-step-8-ssl-certificate-setup)
- [Step 9: Final Testing](#-step-9-final-testing)
- [Maintenance and Updates](#maintenance-and-updates)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

**Advantages:**
- Easy to use interface
- Managed hosting (no server maintenance)
- Built-in backup tools
- Email management
- Cost-effective for small projects

**Limitations:**
- Limited server control
- PHP version restrictions
- Resource limitations
- No SSH access (usually)
- Limited customization

**Requirements:**
- Shared hosting account with cPanel
- PHP 8.2+ support
- MySQL database support
- Minimum 512MB memory limit
- File manager or FTP access
- Domain name

---

## 🔍 Step 1: Verify Hosting Requirements

### 1.1 Check PHP Version and Extensions
In cPanel, go to **Software** → **Select PHP Version**:

**Required PHP Version:** 8.2 or higher

**Required Extensions (check these are enabled):**
- ✅ bcmath
- ✅ curl
- ✅ gd
- ✅ intl
- ✅ mbstring
- ✅ mysql/mysqli
- ✅ openssl
- ✅ pdo_mysql
- ✅ xml
- ✅ zip
- ✅ fileinfo
- ✅ json

### 1.2 Check PHP Settings
In **Software** → **PHP Configuration**, verify:
```ini
memory_limit = 512M (minimum 256M)
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
max_input_vars = 3000
```

### 1.3 Check Available Space
Ensure you have at least **2GB** of available disk space.

---

## 🗄️ Step 2: Database Setup

### 2.1 Create MySQL Database
1. In cPanel, go to **Databases** → **MySQL Databases**
2. Create a new database:
   - Database Name: `pkki_itera_prod` (or similar)
3. Create a database user:
   - Username: `pkki_user`
   - Password: Use a strong password (save it!)
4. Add user to database with **ALL PRIVILEGES**

### 2.2 Note Database Information
Save these details for later:
```
Database Host: localhost (usually)
Database Name: username_pkki_itera_prod
Database User: username_pkki_user
Database Password: your_password
```

**Note:** Many hosting providers prefix database names and usernames with your cPanel username.

---

## 📁 Step 3: Prepare Project Files

### 3.1 Download Project from Repository
On your local machine:
```bash
git clone https://github.com/your-username/pkki-itera.git
cd pkki-itera
```

### 3.2 Install Dependencies Locally
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm install
npm run build
```

### 3.3 Prepare Environment File
```bash
cp .env.example .env
```

Edit `.env` with your hosting details:
```env
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_pkki_itera_prod
DB_USERNAME=username_pkki_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail Configuration (use your hosting provider's SMTP or external service)
MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=your_email_password
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

### 3.4 Generate Application Key
```bash
php artisan key:generate
```

### 3.5 Create Upload Package
Create a ZIP file with your project (excluding unnecessary files):
```bash
# Create a deployment package
zip -r pkki-itera-deploy.zip . \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "tests/*" \
  -x ".github/*" \
  -x "*.md" \
  -x ".env.example" \
  -x "package-lock.json" \
  -x "composer.lock"
```

---

## 🚀 Step 4: Upload Files to cPanel

### 4.1 Method 1: Using File Manager (Recommended)

1. **Access File Manager**
   - Go to cPanel → **Files** → **File Manager**
   - Navigate to `public_html` (or your domain's folder)

2. **Create Application Directory**
   - Create a new folder called `pkki-itera` in your home directory (NOT in public_html)
   - Upload `pkki-itera-deploy.zip` to this folder
   - Extract the ZIP file

3. **Move Public Files**
   - Copy contents of `pkki-itera/public` to `public_html`
   - Update `public_html/index.php` to point to correct path

### 4.2 Method 2: Using FTP/SFTP

```bash
# Using SCP (if SSH is available)
scp pkki-itera-deploy.zip username@your-domain.com:~/

# Using FTP client (FileZilla, WinSCP, etc.)
# Upload to your home directory, then extract
```

### 4.3 Update Document Root (Important!)

**Option A: Update index.php (Recommended for shared hosting)**

Edit `public_html/index.php`:
```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/

if (file_exists($maintenance = __DIR__.'/../pkki-itera/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

require __DIR__.'/../pkki-itera/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$app = require_once __DIR__.'/../pkki-itera/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Option B: Change Document Root (if hosting allows)**
- In cPanel → **Domains** → **Subdomains** or **Addon Domains**
- Set document root to `/home/username/pkki-itera/public`

---

## ⚙️ Step 5: Configure Application

### 5.1 Set Correct Permissions
Using File Manager, set permissions:
- `storage/` and all subdirectories: **755**
- `bootstrap/cache/`: **755**
- All files in these directories: **644**

### 5.2 Create Storage Link
Create a symbolic link from `public/storage` to `storage/app/public`:

**Method 1: If SSH is available**
```bash
cd /home/username/public_html
ln -s ../pkki-itera/storage/app/public storage
```

**Method 2: Using File Manager**
1. Go to File Manager
2. Navigate to `public_html`
3. Create a new file called `.htaccess` (if not exists)
4. Add rewrite rules (see Step 5.3)

### 5.3 Configure .htaccess for Laravel
Create/update `public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Angular and Vue History API fallback
    RewriteCond %{REQUEST_URI} !(\.css|\.js|\.png|\.jpg|\.gif|\.ico|\.svg)$ [NC]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . /index.php [L]

    # Redirect Trailing Slashes If Not A Folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent access to sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
```

---

## 🛠️ Step 6: Database Migration and Setup

### 6.1 Access Terminal (if available)
If your hosting provides SSH access:
```bash
ssh username@your-domain.com
cd pkki-itera
```

### 6.2 Run Migrations
**Option A: SSH Terminal (if available)**
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan shield:generate --all
```

**Option B: Using cPanel Terminal (if available)**
Some hosts provide a terminal in cPanel:
- Go to **Advanced** → **Terminal**
- Navigate to your project directory
- Run the same commands

**Option C: Using PHP Scripts (if no terminal access)**

Create temporary migration scripts in your project root:

**migrate.php:**
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('migrate', ['--force' => true]);
    echo "Migrations completed successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
```

**seed.php:**
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('db:seed', ['--force' => true]);
    echo "Seeding completed successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
?>
```

**shield.php:**
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('shield:generate', ['--all' => true]);
    echo "Shield permissions generated successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Shield generation failed: " . $e->getMessage() . "\n";
}
?>
```

Run these scripts by visiting:
- `https://your-domain.com/migrate.php`
- `https://your-domain.com/seed.php`
- `https://your-domain.com/shield.php`

**⚠️ IMPORTANT: Delete these files after use for security!**

---

## 🔐 Step 7: Create Admin User

### 7.1 Create Admin User Script
Create `create-admin.php` in your project root:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $admin = User::create([
        'fullname' => 'Administrator',
        'email' => 'admin@your-domain.com',
        'password' => Hash::make('SecurePassword123!'),
        'email_verified_at' => now(),
    ]);
    
    // Assign super admin role
    $admin->assignRole('super_admin');
    
    echo "Admin user created successfully!\n";
    echo "Email: admin@your-domain.com\n";
    echo "Password: SecurePassword123!\n";
    echo "Please change the password after first login!\n";
    
} catch (Exception $e) {
    echo "Failed to create admin user: " . $e->getMessage() . "\n";
}
?>
```

Run by visiting: `https://your-domain.com/create-admin.php`

**⚠️ Delete this file immediately after use!**

---

## 🎯 Step 8: Optimize for Production

### 8.1 Cache Configuration
Create `optimize.php`:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

try {
    // Clear all caches first
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    
    // Cache for production
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    
    echo "Application optimized for production!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Optimization failed: " . $e->getMessage() . "\n";
}
?>
```

### 8.2 Create Storage Link Script
Create `storage-link.php`:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('storage:link');
    echo "Storage link created successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Storage link creation failed: " . $e->getMessage() . "\n";
}
?>
```

Run both scripts and delete them after use.

---

## 📧 Step 9: Configure Email

### 9.1 Setup Email Account in cPanel
1. Go to **Email** → **Email Accounts**
2. Create email account: `noreply@your-domain.com`
3. Set a strong password

### 9.2 Configure SMTP Settings
In your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="PKKI ITERA"
```

### 9.3 Test Email Configuration
Create `test-email.php`:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Test email from PKKI ITERA application', function ($message) {
        $message->to('your-test-email@example.com')
                ->subject('PKKI ITERA - Email Test');
    });
    
    echo "Test email sent successfully!\n";
} catch (Exception $e) {
    echo "Email test failed: " . $e->getMessage() . "\n";
}
?>
```

---

## 🔒 Step 10: SSL Certificate Setup

### 10.1 Using cPanel SSL (Recommended)
1. Go to **Security** → **SSL/TLS**
2. Choose **Let's Encrypt** (if available) or upload your certificate
3. Enable **Force HTTPS Redirect**

### 10.2 Manual HTTPS Redirect
If automatic redirect isn't available, update `.htaccess`:
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🧪 Step 11: Testing and Verification

### 11.1 Basic Functionality Test
1. **Homepage**: Visit `https://your-domain.com`
2. **Admin Panel**: Visit `https://your-domain.com/admin`
3. **Login**: Use admin credentials created earlier
4. **Dashboard**: Verify admin dashboard loads
5. **Submissions**: Try creating a test submission
6. **File Upload**: Test document upload functionality

### 11.2 Performance Test
Use online tools to test:
- **GTmetrix**: https://gtmetrix.com
- **PageSpeed Insights**: https://pagespeed.web.dev
- **Pingdom**: https://tools.pingdom.com

### 11.3 Error Checking
Check for errors:
1. Look at `storage/logs/laravel.log`
2. Check cPanel Error Logs
3. Test all major features

---

## 🔄 Step 12: Backup Setup

### 12.1 Use cPanel Backup Tools
1. Go to **Files** → **Backup Wizard**
2. Schedule automatic backups
3. Include both files and database

### 12.2 Manual Backup Script
Create `backup.php` for manual backups:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$date = date('Y-m-d_H-i-s');
$backupPath = "backups/backup_$date.sql";

// Create backup directory
if (!file_exists('backups')) {
    mkdir('backups', 0755, true);
}

// Database backup
$tables = DB::select('SHOW TABLES');
$sql = '';

foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    
    $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0];
    $sql .= $createTable->{'Create Table'} . ";\n\n";
    
    $rows = DB::select("SELECT * FROM `$tableName`");
    foreach ($rows as $row) {
        $sql .= "INSERT INTO `$tableName` VALUES (";
        $values = array_values((array)$row);
        foreach ($values as $value) {
            $sql .= "'" . addslashes($value) . "', ";
        }
        $sql = rtrim($sql, ', ') . ");\n";
    }
    $sql .= "\n";
}

file_put_contents($backupPath, $sql);
echo "Database backup created: $backupPath\n";
?>
```

---

## 🚀 Step 13: Performance Optimization for Shared Hosting

### 13.1 Enable OPcache (if available)
In cPanel → **Software** → **PHP Configuration**:
```ini
opcache.enable=1
opcache.memory_consumption=64
opcache.max_accelerated_files=2000
```

### 13.2 Optimize Database Queries
Create `optimize-db.php`:
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    // Optimize all tables
    $tables = DB::select('SHOW TABLES');
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        DB::statement("OPTIMIZE TABLE `$tableName`");
    }
    
    echo "Database optimization completed!\n";
} catch (Exception $e) {
    echo "Database optimization failed: " . $e->getMessage() . "\n";
}
?>
```

### 13.3 Minimize Resource Usage
Update `.env`:
```env
# Use file-based sessions and cache for better performance
SESSION_DRIVER=file
CACHE_DRIVER=file

# Reduce log verbosity
LOG_LEVEL=error

# Disable unnecessary services
BROADCAST_DRIVER=log
QUEUE_CONNECTION=sync
```

---

## 🛠️ Troubleshooting Common cPanel Issues

### Issue 1: "Class not found" Errors
**Solution:**
```bash
# Re-run composer autoload
composer dump-autoload --optimize
```

### Issue 2: Permission Denied
**Solution:**
- Check file permissions (755 for directories, 644 for files)
- Ensure storage and cache directories are writable

### Issue 3: Database Connection Failed
**Solution:**
- Verify database credentials in `.env`
- Check if database user has proper permissions
- Ensure database server is accessible

### Issue 4: "Route not found" or 404 Errors
**Solution:**
- Check `.htaccess` file in public directory
- Verify document root configuration
- Clear route cache

### Issue 5: File Upload Issues
**Solution:**
- Check PHP upload limits
- Verify storage directory permissions
- Check disk space availability

### Issue 6: Email Not Working
**Solution:**
- Verify SMTP settings
- Check email account exists and password is correct
- Test with external SMTP service (Gmail, SendGrid)

### Issue 7: Slow Performance
**Solution:**
- Enable caching
- Optimize database
- Use CDN for static assets
- Check shared hosting resource limits

---

## 📋 Post-Deployment Checklist

- [ ] Application loads at your domain
- [ ] Admin panel accessible at `/admin`
- [ ] HTTPS working (green padlock)
- [ ] Admin user can login
- [ ] Database migrations completed
- [ ] Email functionality working
- [ ] File uploads working
- [ ] All temporary scripts deleted
- [ ] Error logs checked
- [ ] Backup system configured
- [ ] Performance optimized

---

## 🔄 Future Updates

### Updating the Application
1. **Download new version** to local machine
2. **Install dependencies** locally
3. **Build assets** with `npm run build`
4. **Backup current version** on server
5. **Upload updated files** (excluding `.env`)
6. **Run migrations** if needed
7. **Clear cache** and test

### Maintenance Mode
When updating, put the site in maintenance mode by creating a `maintenance.html` file in public_html or using Laravel's maintenance mode through terminal.

**🎉 Congratulations! Your PKKI ITERA Laravel application is now successfully deployed on cPanel shared hosting!**

For ongoing maintenance, regularly check error logs, perform backups, and keep your dependencies updated.
