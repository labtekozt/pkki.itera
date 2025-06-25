#!/bin/bash

# PKKI ITERA - Environment Setup Script
# Simple configuration for different environments

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"; }
info() { echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"; }

# Environment templates
create_production_env() {
    cat > "$PROJECT_ROOT/.env" << 'EOF'
APP_NAME="PKKI ITERA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database (Supabase PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=your-database-password

# Supabase Configuration
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key

# Cache & Session (Use database for simplicity)
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="PKKI ITERA"

# File Storage
FILESYSTEM_DISK=public

# Social Login
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://your-domain.com/login/google/callback

# Activity Logging
ACTIVITY_LOGGER_ENABLED=true
EOF
    log "Production environment template created"
}

create_staging_env() {
    cat > "$PROJECT_ROOT/.env" << 'EOF'
APP_NAME="PKKI ITERA (Staging)"
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://staging.your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Database (Supabase PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=your-database-password

# Supabase Configuration
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail Configuration (use Mailtrap for testing)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=staging@your-domain.com
MAIL_FROM_NAME="PKKI ITERA Staging"

# File Storage
FILESYSTEM_DISK=public

# Social Login
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://staging.your-domain.com/login/google/callback

# Activity Logging
ACTIVITY_LOGGER_ENABLED=true
EOF
    log "Staging environment template created"
}

create_development_env() {
    cat > "$PROJECT_ROOT/.env" << 'EOF'
APP_NAME="PKKI ITERA (Dev)"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Database (Supabase PostgreSQL or Local)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pkki_itera_dev
DB_USERNAME=postgres
DB_PASSWORD=password

# Supabase Configuration (optional for development)
SUPABASE_URL=
SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=

# Cache & Session
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

# Mail Configuration (use log driver for development)
MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=dev@localhost
MAIL_FROM_NAME="PKKI ITERA Dev"

# File Storage
FILESYSTEM_DISK=local

# Social Login (for testing)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/login/google/callback

# Activity Logging
ACTIVITY_LOGGER_ENABLED=false
EOF
    log "Development environment template created"
}

# Setup database tables
setup_database() {
    log "Setting up database..."
    
    cd "$PROJECT_ROOT"
    
    # Generate app key if not exists
    php artisan key:generate --force
    
    # Create required tables
    php artisan cache:table 2>/dev/null || true
    php artisan queue:table 2>/dev/null || true
    php artisan session:table 2>/dev/null || true
    
    # Run migrations
    php artisan migrate --force
    
    # Create storage link
    php artisan storage:link
    
    log "Database setup completed"
}

# Setup permissions and roles
setup_permissions() {
    log "Setting up permissions and roles..."
    
    cd "$PROJECT_ROOT"
    
    # Disable activity logging temporarily
    export ACTIVITY_LOGGER_ENABLED=false
    
    # Run seeders
    php artisan db:seed --force
    
    # Generate Filament Shield permissions
    php artisan shield:generate --all
    
    log "Permissions and roles setup completed"
}

# Create admin user
create_admin_user() {
    log "Creating admin user..."
    
    cd "$PROJECT_ROOT"
    
    # Create admin user interactively
    php artisan make:filament-user
    
    log "Admin user created"
}

# Main setup function
main() {
    echo -e "${BLUE}PKKI ITERA Environment Setup${NC}"
    echo
    
    # Get environment choice
    echo "Select environment to setup:"
    echo "1) Production"
    echo "2) Staging" 
    echo "3) Development"
    echo
    read -p "Enter choice (1-3): " choice
    
    case $choice in
        1)
            create_production_env
            ENVIRONMENT="production"
            ;;
        2)
            create_staging_env
            ENVIRONMENT="staging"
            ;;
        3)
            create_development_env
            ENVIRONMENT="development"
            ;;
        *)
            echo "Invalid choice"
            exit 1
            ;;
    esac
    
    echo
    info "Environment template created for: $ENVIRONMENT"
    warn "Please edit .env file with your actual credentials before continuing"
    echo
    
    read -p "Have you updated the .env file? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        warn "Please update .env file and run this script again"
        exit 0
    fi
    
    # Setup database and permissions
    setup_database
    setup_permissions
    
    # Ask if want to create admin user
    echo
    read -p "Do you want to create an admin user? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        create_admin_user
    fi
    
    echo
    log "Environment setup completed successfully!"
    echo
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Test the application: php artisan serve (for development)"
    echo "2. Access admin panel: /admin"
    echo "3. Run deployment script: ./scripts/deploy.sh $ENVIRONMENT"
}

main "$@"
