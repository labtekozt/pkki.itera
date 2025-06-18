#!/bin/sh

# PKKI ITERA Docker Entrypoint Script

set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database connection..."
    while ! nc -z $DB_HOST $DB_PORT; do
        echo "Database is unavailable - sleeping"
        sleep 1
    done
    echo "Database is up - continuing"
}

# Function to run Laravel setup
setup_laravel() {
    echo "Setting up Laravel application..."
    
    # Wait for database if configured
    if [ "${DB_CONNECTION}" = "mysql" ]; then
        wait_for_db
    fi
    
    # Generate application key if not set
    if [ -z "${APP_KEY}" ]; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi
    
    # Run database migrations
    echo "Running database migrations..."
    php artisan migrate --force
    
    # Seed database if in development
    if [ "${APP_ENV}" = "local" ] || [ "${APP_ENV}" = "development" ]; then
        echo "Seeding database..."
        php artisan db:seed --force
    fi
    
    # Generate Filament resources
    echo "Generating Filament resources..."
    php artisan shield:generate --all --ignore-existing
    
    # Clear and cache configurations
    echo "Optimizing application..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Create storage link
    php artisan storage:link
    
    echo "Laravel setup completed"
}

# Function to set correct permissions
set_permissions() {
    echo "Setting file permissions..."
    chown -R www:www /var/www
    chmod -R 755 /var/www/storage
    chmod -R 755 /var/www/bootstrap/cache
    echo "Permissions set"
}

# Main execution
echo "Starting PKKI ITERA application..."

# Set permissions
set_permissions

# Setup Laravel
setup_laravel

# Start supervisord
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
