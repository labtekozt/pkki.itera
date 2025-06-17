# 🚀 PKKI ITERA - Script Deployment Lengkap

## Deskripsi
Script deployment tunggal yang mengatur **SEMUA** konfigurasi PKKI ITERA dari awal hingga selesai dalam satu perintah.

## Yang Dikonfigurasi Otomatis

### ✅ **System Setup**
- Update sistem Ubuntu/Debian
- Install PHP 8.2 + ekstensi yang dibutuhkan
- Install MySQL 8.0
- Install Nginx
- Install Node.js 18.x + npm
- Install Certbot untuk SSL

### ✅ **Database Setup** 
- Secure MySQL installation
- Buat database `pkki_itera`
- Buat user `pkki_user` dengan password
- Jalankan migrasi dan seeder

### ✅ **Aplikasi Setup**
- Clone/copy source code
- Install composer dependencies
- Install npm dependencies
- Build React Inertia frontend
- Generate APP_KEY
- Konfigurasi .env production

### ✅ **Web Server Setup**
- Konfigurasi Nginx untuk port 3003/3443
- Setup virtual host untuk domain
- Optimasi PHP-FPM
- Security headers

### ✅ **SSL Certificate**
- Otomatis install SSL dari Let's Encrypt
- Konfigurasi HTTPS redirect
- Setup auto-renewal
- Fallback ke HTTP jika SSL gagal

### ✅ **Security & Optimization**
- Konfigurasi UFW firewall
- Set permission files yang benar
- Cache config dan routes Laravel
- Setup cron job untuk scheduler
- Security headers Nginx

### ✅ **Admin User**
- Buat user admin default
- Assign role super admin
- Setup Filament Shield

### ✅ **Verification**
- Test semua services
- Test HTTP/HTTPS response
- Test database connection
- Test Laravel artisan
- Test admin panel access

## Cara Penggunaan

### 1. Upload ke VPS
```bash
# Upload files ke VPS
scp -r . user@your-vps:/tmp/pkki-itera

# Atau clone langsung di VPS
git clone https://github.com/labtekozt/pkki.itera.git /tmp/pkki-itera
```

### 2. Jalankan Script
```bash
# SSH ke VPS
ssh user@your-vps

# Masuk ke direktori project
cd /tmp/pkki-itera

# Jalankan script deployment lengkap
sudo ./deploy-complete.sh
```

### 3. Ikuti Instruksi
Script akan menanyakan konfirmasi konfigurasi:
- Domain: `hki.proyekai.com`
- Database: `pkki_itera`
- Admin: `admin@hki.itera.ac.id`

## Domain DNS Setup

**SEBELUM** menjalankan script, pastikan domain sudah diarahkan ke VPS:

```
A Record: hki.proyekai.com → IP_VPS_ANDA
A Record: www.hki.proyekai.com → IP_VPS_ANDA
```

Tunggu 5-30 menit untuk DNS propagation.

## Hasil Setelah Deployment

### 🔗 **URL Akses:**
- **HTTPS (Utama):** `https://hki.proyekai.com`
- **Admin Panel:** `https://hki.proyekai.com/admin`
- **HTTP:** `http://hki.proyekai.com:3003` (redirect ke HTTPS)

### 👤 **Kredensial Admin:**
- **Email:** `admin@hki.itera.ac.id`
- **Password:** `admin123`

### 📋 **Informasi Sistem:**
- **Environment:** Production
- **Database:** MySQL (pkki_itera)
- **SSL:** Let's Encrypt (auto-renewal)
- **Firewall:** UFW enabled
- **Ports:** 3003 (HTTP), 3443 (HTTPS)

## Fitur Script

### 🎨 **User-Friendly Interface**
- Progress bar dan status berwarna
- Logging timestamp untuk setiap langkah
- Error handling yang baik
- Rollback otomatis jika ada error

### 🔒 **Security First**
- SSL certificate otomatis
- Security headers Nginx
- MySQL secure installation
- Firewall configuration
- File permissions yang benar

### ⚡ **Performance Optimized**
- Cache Laravel config & routes
- Nginx optimization
- PHP-FPM tuning
- Static file caching
- Gzip compression

### 🛠️ **Maintenance Ready**
- Auto-renewal SSL
- Cron job Laravel scheduler
- Log rotation
- Backup preparation

## Troubleshooting

### Jika SSL Gagal:
```bash
# Manual SSL setup
sudo certbot --nginx -d hki.proyekai.com -d www.hki.proyekai.com
```

### Jika Database Error:
```bash
# Reset database
cd /var/www/pkki-itera
php artisan migrate:fresh --seed
```

### Jika Permission Error:
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/pkki-itera
sudo chmod -R 775 /var/www/pkki-itera/storage
sudo chmod -R 775 /var/www/pkki-itera/bootstrap/cache
```

### Cek Logs:
```bash
# Laravel logs
tail -f /var/www/pkki-itera/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

## File Konfigurasi Penting

### Environment (.env)
```bash
nano /var/www/pkki-itera/.env
```

### Nginx Config
```bash
sudo nano /etc/nginx/sites-available/pkki-itera
```

### Crontab
```bash
crontab -l
```

## Post-Deployment Tasks

### 1. Ganti Password Admin
- Login ke `/admin`
- Ganti password default `admin123`

### 2. Konfigurasi Email
- Update setting MAIL_* di `.env`
- Test email notifications

### 3. Backup Setup
```bash
# Database backup
mysqldump -u pkki_user -p pkki_itera > backup.sql

# Files backup
tar -czf backup.tar.gz /var/www/pkki-itera
```

### 4. Monitoring
- Setup uptime monitoring
- Configure log monitoring
- Regular security updates

---

## 🎉 Kesimpulan

Script `deploy-complete.sh` ini adalah solusi **one-click deployment** yang mengatur **SEMUA** konfigurasi PKKI ITERA secara otomatis:

✅ **System packages & services**  
✅ **Database setup & seeding**  
✅ **Application deployment**  
✅ **SSL certificate & security**  
✅ **Web server configuration**  
✅ **Performance optimization**  
✅ **Admin user creation**  
✅ **Complete verification**  

**Cukup 1 perintah:** `sudo ./deploy-complete.sh`

**Aplikasi PKKI ITERA siap production dalam 10-15 menit!** 🚀
