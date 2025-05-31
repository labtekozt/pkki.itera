#!/bin/bash

# PKKI ITERA Development Setup Script
# This script sets up the development environment for PKKI ITERA

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_command() {
    if ! command -v $1 &> /dev/null; then
        print_error "$1 is not installed. Please install $1 first."
        exit 1
    fi
}

# Main setup function
main() {
    print_status "Starting PKKI ITERA development setup..."
    
    # Check required tools
    print_status "Checking required tools..."
    check_command "php"
    check_command "composer"
    check_command "node"
    check_command "npm"
    check_command "mysql"
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_status "PHP version: $PHP_VERSION"
    
    if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
        print_error "PHP 8.2 or higher is required. Current version: $PHP_VERSION"
        exit 1
    fi
    
    # Install Composer dependencies
    print_status "Installing Composer dependencies..."
    if [ -f "composer.lock" ]; then
        composer install --optimize-autoloader
    else
        composer install
    fi
    print_success "Composer dependencies installed"
    
    # Install NPM dependencies
    print_status "Installing NPM dependencies..."
    npm install
    print_success "NPM dependencies installed"
    
    # Copy environment file
    if [ ! -f ".env" ]; then
        print_status "Creating .env file..."
        cp .env.example .env
        print_success ".env file created"
    else
        print_warning ".env file already exists, skipping..."
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate
    print_success "Application key generated"
    
    # Set up storage links
    print_status "Creating storage link..."
    php artisan storage:link
    print_success "Storage link created"
    
    # Database setup
    read -p "Do you want to set up the database? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Setting up database..."
        
        # Check if database exists
        DB_NAME=$(php -r "echo parse_ini_file('.env')['DB_DATABASE'] ?? 'pkki_itera';")
        DB_USER=$(php -r "echo parse_ini_file('.env')['DB_USERNAME'] ?? 'root';")
        DB_PASS=$(php -r "echo parse_ini_file('.env')['DB_PASSWORD'] ?? '';")
        
        # Create database if it doesn't exist
        print_status "Creating database if it doesn't exist..."
        mysql -u${DB_USER} ${DB_PASS:+-p$DB_PASS} -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;" 2>/dev/null || {
            print_warning "Could not create database automatically. Please create it manually."
        }
        
        # Run migrations
        print_status "Running database migrations..."
        php artisan migrate --force
        print_success "Database migrations completed"
        
        # Run seeders
        read -p "Do you want to run database seeders? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "Running database seeders..."
            php artisan db:seed --force
            print_success "Database seeders completed"
        fi
    fi
    
    # Generate Filament resources
    print_status "Generating Filament resources..."
    php artisan shield:generate --all --ignore-existing
    print_success "Filament resources generated"
    
    # Clear caches
    print_status "Clearing application caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    print_success "Application caches cleared"
    
    # Build assets
    read -p "Do you want to build frontend assets? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Building frontend assets..."
        npm run build
        print_success "Frontend assets built"
    fi
    
    # Set permissions
    print_status "Setting directory permissions..."
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    print_success "Directory permissions set"
    
    # Final steps
    print_success "Development setup completed!"
    echo
    print_status "Next steps:"
    echo "1. Update your .env file with your database credentials"
    echo "2. Run 'php artisan serve' to start the development server"
    echo "3. Run 'npm run dev' in another terminal for asset watching"
    echo "4. Visit http://localhost:8000 to see your application"
    echo "5. Visit http://localhost:8000/admin to access the admin panel"
    echo
    print_status "Default admin credentials (if seeders were run):"
    echo "Email: admin@itera.ac.id"
    echo "Password: admin123"
    echo
    print_warning "Remember to change the default credentials in production!"
}

# Run the main function
main "$@"
