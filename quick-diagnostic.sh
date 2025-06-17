#!/bin/bash

# Quick 500 Error Diagnostic
# Check the most common causes of 500 errors

echo "🔍 QUICK 500 ERROR DIAGNOSTIC"
echo "=============================="

# Check if we're on VPS
echo ""
echo "📍 Current Location: $(pwd)"
echo "🖥️  Server Info: $(uname -a)"

# Check Laravel logs
echo ""
echo "📋 RECENT LARAVEL ERRORS:"
echo "-------------------------"
if [ -f "/var/www/pkki-itera/storage/logs/laravel.log" ]; then
    echo "Last 10 Laravel errors:"
    tail -10 /var/www/pkki-itera/storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|EMERGENCY)" || echo "No recent Laravel errors found"
else
    echo "❌ Laravel log not found at /var/www/pkki-itera/storage/logs/laravel.log"
fi

# Check Nginx errors
echo ""
echo "🌐 RECENT NGINX ERRORS:"
echo "----------------------"
if [ -f "/var/log/nginx/error.log" ]; then
    echo "Last 5 Nginx errors:"
    tail -5 /var/log/nginx/error.log || echo "No recent Nginx errors found"
else
    echo "❌ Nginx error log not found"
fi

# Check PHP-FPM errors
echo ""
echo "🐘 RECENT PHP-FPM ERRORS:"
echo "-------------------------"
if [ -f "/var/log/php8.2-fpm.log" ]; then
    echo "Last 5 PHP-FPM errors:"
    tail -5 /var/log/php8.2-fpm.log || echo "No recent PHP-FPM errors found"
else
    echo "❌ PHP-FPM log not found"
fi

# Check services
echo ""
echo "🔧 SERVICE STATUS:"
echo "-----------------"
services=("nginx" "php8.2-fpm" "mysql")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service 2>/dev/null; then
        echo "✅ $service: Running"
    else
        echo "❌ $service: Not running"
    fi
done

# Check basic file existence
echo ""
echo "📁 FILE CHECKS:"
echo "---------------"
if [ -f "/var/www/pkki-itera/public/index.php" ]; then
    echo "✅ Laravel entry point exists"
else
    echo "❌ Laravel entry point missing"
fi

if [ -f "/var/www/pkki-itera/.env" ]; then
    echo "✅ .env file exists"
else
    echo "❌ .env file missing"
fi

if [ -d "/var/www/pkki-itera/vendor" ]; then
    echo "✅ Vendor directory exists"
else
    echo "❌ Vendor directory missing"
fi

# Check permissions
echo ""
echo "🔒 PERMISSION CHECK:"
echo "-------------------"
if [ -d "/var/www/pkki-itera" ]; then
    ls -la /var/www/pkki-itera | head -5
else
    echo "❌ Project directory not found"
fi

# Quick HTTP test
echo ""
echo "🌐 HTTP TEST:"
echo "-------------"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://hki.proyekai.com" 2>/dev/null || echo "000")
echo "HTTP Status: $HTTP_STATUS"

# Instructions
echo ""
echo "🚀 QUICK FIXES TO TRY:"
echo "======================"
echo "1. Run the full fix script:"
echo "   sudo ./fix-500-error.sh"
echo ""
echo "2. Or run these commands manually:"
echo "   sudo systemctl restart nginx php8.2-fpm mysql"
echo "   sudo chown -R www-data:www-data /var/www/pkki-itera"
echo "   sudo chmod -R 775 /var/www/pkki-itera/storage"
echo "   cd /var/www/pkki-itera && sudo -u www-data php artisan config:clear"
echo ""
echo "3. Check real-time logs:"
echo "   tail -f /var/www/pkki-itera/storage/logs/laravel.log"
