#!/bin/bash

# EMERGENCY FIX for Laravel Permission Issues
# Fixes the specific errors you're seeing right now

echo "🚨 EMERGENCY LARAVEL PERMISSION FIX"
echo "=================================="

PROJECT_DIR="/var/www/pkki-itera"

if [ "$EUID" -ne 0 ]; then
    echo "❌ This script must be run as root (use sudo)"
    exit 1
fi

if [ ! -d "$PROJECT_DIR" ]; then
    echo "❌ Project directory not found at $PROJECT_DIR"
    exit 1
fi

echo "🔧 Creating missing directories..."

# Create all required Laravel directories
mkdir -p $PROJECT_DIR/storage/logs
mkdir -p $PROJECT_DIR/storage/app/public
mkdir -p $PROJECT_DIR/storage/framework/cache/data
mkdir -p $PROJECT_DIR/storage/framework/sessions
mkdir -p $PROJECT_DIR/storage/framework/views
mkdir -p $PROJECT_DIR/storage/framework/testing
mkdir -p $PROJECT_DIR/bootstrap/cache

# Create nested cache directories (for cache files like 85/5f/855f92484c8c414d...)
echo "📁 Creating nested cache directories..."
for i in {0..9} {a..f}; do
    for j in {0..9} {a..f}; do
        mkdir -p $PROJECT_DIR/storage/framework/cache/data/$i$j
    done
done

echo "🔒 Setting permissions..."

# Give full permissions to storage (temporarily for emergency fix)
chmod -R 777 $PROJECT_DIR/storage
chmod -R 777 $PROJECT_DIR/bootstrap/cache

# Set correct ownership
chown -R www-data:www-data $PROJECT_DIR/storage
chown -R www-data:www-data $PROJECT_DIR/bootstrap/cache

# Create log file if missing
if [ ! -f "$PROJECT_DIR/storage/logs/laravel.log" ]; then
    touch $PROJECT_DIR/storage/logs/laravel.log
    chown www-data:www-data $PROJECT_DIR/storage/logs/laravel.log
    chmod 666 $PROJECT_DIR/storage/logs/laravel.log
fi

echo "🧹 Clearing caches..."
cd $PROJECT_DIR

# Clear all caches
sudo -u www-data php artisan config:clear 2>/dev/null || echo "Config clear skipped"
sudo -u www-data php artisan cache:clear 2>/dev/null || echo "Cache clear skipped" 
sudo -u www-data php artisan view:clear 2>/dev/null || echo "View clear skipped"
sudo -u www-data php artisan route:clear 2>/dev/null || echo "Route clear skipped"

echo "🔄 Restarting services..."
systemctl restart php8.2-fpm nginx

echo ""
echo "✅ EMERGENCY FIX COMPLETED!"
echo ""
echo "🧪 Testing your site now:"
curl -I http://hki.proyekai.com

echo ""
echo "📋 If still having issues, check logs:"
echo "   tail -f $PROJECT_DIR/storage/logs/laravel.log"
echo ""
echo "🔧 For complete fix, run:"
echo "   sudo ./fix-500-error.sh"
