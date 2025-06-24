#!/bin/bash

# PKKI ITERA - Supabase Configuration Wizard
# Interactive script to configure Supabase settings for deployment
# Version: 1.0.0

set -e

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Script information
readonly SCRIPT_VERSION="1.0.0"
readonly SCRIPT_NAME="PKKI ITERA Supabase Configuration Wizard"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Configuration variables
ENVIRONMENT=""
DOMAIN=""
SSL_EMAIL=""
SUPABASE_URL=""
SUPABASE_ANON_KEY=""
SUPABASE_SERVICE_ROLE_KEY=""
DB_HOST=""
DB_PORT="5432"
DB_DATABASE=""
DB_USERNAME=""
DB_PASSWORD=""
SKIP_NGINX=false
SKIP_SSL=false

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"
}

success() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"
}

# Show welcome message
show_welcome() {
    clear
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                🚀 PKKI ITERA DEPLOYMENT WIZARD                      ║${NC}"
    echo -e "${CYAN}║                                                                      ║${NC}"
    echo -e "${CYAN}║     Interactive configuration for Supabase deployment               ║${NC}"
    echo -e "${CYAN}║                     Version $SCRIPT_VERSION                                    ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${BLUE}This wizard will help you configure PKKI ITERA for deployment with Supabase.${NC}"
    echo -e "${BLUE}We'll gather all the necessary information and generate the deployment command.${NC}"
    echo
    read -p "Press Enter to continue..."
    echo
}

# Get environment selection
get_environment() {
    echo -e "${BOLD}🔧 Environment Configuration${NC}"
    echo "Select the environment you want to deploy to:"
    echo
    echo "1) 🏠 Development  - Local development with minimal security"
    echo "2) 🧪 Staging      - Testing environment with production-like settings"  
    echo "3) 🏭 Production   - Full production deployment with all optimizations"
    echo
    
    while true; do
        read -p "Choose environment (1-3): " choice
        case $choice in
            1)
                ENVIRONMENT="development"
                SKIP_SSL=true
                break
                ;;
            2)
                ENVIRONMENT="staging"
                break
                ;;
            3)
                ENVIRONMENT="production"
                break
                ;;
            *)
                error "Invalid choice. Please select 1, 2, or 3."
                ;;
        esac
    done
    
    success "Selected environment: $ENVIRONMENT"
    echo
}

# Get domain configuration
get_domain_config() {
    echo -e "${BOLD}🌐 Domain Configuration${NC}"
    
    if [ "$ENVIRONMENT" = "development" ]; then
        echo "For development environment, domain configuration is optional."
        read -p "Enter domain name (or press Enter to skip): " DOMAIN
    else
        echo "Enter your domain name for the application."
        echo -e "${YELLOW}Example: pkki.itera.ac.id${NC}"
        echo
        
        while true; do
            read -p "Domain name: " DOMAIN
            if [ -n "$DOMAIN" ]; then
                # Basic domain validation
                if [[ $DOMAIN =~ ^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$ ]]; then
                    break
                else
                    error "Invalid domain format. Please enter a valid domain name."
                fi
            else
                error "Domain name is required for $ENVIRONMENT environment."
            fi
        done
    fi
    
    if [ -n "$DOMAIN" ]; then
        success "Domain configured: $DOMAIN"
        
        # Get SSL email if not development
        if [ "$ENVIRONMENT" != "development" ]; then
            echo
            echo "Enter email address for SSL certificate registration:"
            echo -e "${YELLOW}This will be used by Let's Encrypt for important notifications.${NC}"
            
            while true; do
                read -p "SSL Email: " SSL_EMAIL
                if [[ $SSL_EMAIL =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
                    break
                else
                    error "Invalid email format. Please enter a valid email address."
                fi
            done
            
            success "SSL email configured: $SSL_EMAIL"
        fi
    else
        info "Skipping domain configuration"
        SKIP_NGINX=true
        SKIP_SSL=true
    fi
    echo
}

# Get Supabase configuration
get_supabase_config() {
    echo -e "${BOLD}🗄️ Supabase Configuration${NC}"
    echo "Enter your Supabase project details."
    echo -e "${YELLOW}You can find these in your Supabase project dashboard > Settings > API${NC}"
    echo
    
    # Supabase URL
    echo "1. Project URL"
    echo -e "${CYAN}Example: https://abcdefghijklmnop.supabase.co${NC}"
    while true; do
        read -p "Supabase URL: " SUPABASE_URL
        if [[ $SUPABASE_URL =~ ^https://.*\.supabase\.co$ ]]; then
            break
        else
            error "Invalid Supabase URL format. Should be https://your-project.supabase.co"
        fi
    done
    
    echo
    
    # Anon Key
    echo "2. Anonymous (public) Key"
    echo -e "${CYAN}This key is safe to use in client-side code.${NC}"
    while true; do
        read -p "Anon Key: " SUPABASE_ANON_KEY
        if [ ${#SUPABASE_ANON_KEY} -gt 50 ]; then
            break
        else
            error "Anon key seems too short. Please check and try again."
        fi
    done
    
    echo
    
    # Service Role Key
    echo "3. Service Role Key"
    echo -e "${RED}⚠️  This key has admin privileges. Keep it secure!${NC}"
    while true; do
        read -s -p "Service Role Key: " SUPABASE_SERVICE_ROLE_KEY
        echo
        if [ ${#SUPABASE_SERVICE_ROLE_KEY} -gt 50 ]; then
            break
        else
            error "Service role key seems too short. Please check and try again."
        fi
    done
    
    success "Supabase API keys configured"
    echo
}

# Get database configuration
get_database_config() {
    echo -e "${BOLD}🗄️ Database Configuration${NC}"
    echo "Enter your Supabase PostgreSQL database details."
    echo -e "${YELLOW}You can find these in your Supabase project dashboard > Settings > Database${NC}"
    echo
    
    # Database Host
    echo "1. Database Host"
    echo -e "${CYAN}Example: aws-0-ap-southeast-1.pooler.supabase.com${NC}"
    while true; do
        read -p "Database Host: " DB_HOST
        if [[ $DB_HOST =~ .*\.supabase\.com$ ]]; then
            break
        else
            warn "Host doesn't look like a Supabase host, but continuing..."
            if [ -n "$DB_HOST" ]; then
                break
            fi
        fi
    done
    
    echo
    
    # Database Port
    echo "2. Database Port"
    echo -e "${CYAN}Default is usually 5432${NC}"
    read -p "Database Port [$DB_PORT]: " input_port
    if [ -n "$input_port" ]; then
        DB_PORT="$input_port"
    fi
    
    echo
    
    # Database Name
    echo "3. Database Name"
    echo -e "${CYAN}Usually 'postgres' for Supabase${NC}"
    read -p "Database Name [postgres]: " DB_DATABASE
    if [ -z "$DB_DATABASE" ]; then
        DB_DATABASE="postgres"
    fi
    
    echo
    
    # Database Username
    echo "4. Database Username"
    echo -e "${CYAN}Example: postgres.abcdefghijklmnop${NC}"
    while true; do
        read -p "Database Username: " DB_USERNAME
        if [ -n "$DB_USERNAME" ]; then
            break
        else
            error "Database username is required."
        fi
    done
    
    echo
    
    # Database Password
    echo "5. Database Password"
    echo -e "${RED}⚠️  This password provides database access. Keep it secure!${NC}"
    while true; do
        read -s -p "Database Password: " DB_PASSWORD
        echo
        if [ -n "$DB_PASSWORD" ]; then
            break
        else
            error "Database password is required."
        fi
    done
    
    success "Database configuration completed"
    echo
}

# Test database connection
test_database_connection() {
    echo -e "${BOLD}🔍 Testing Database Connection${NC}"
    
    # Check if psql is available
    if ! command -v psql &> /dev/null; then
        warn "psql not available, skipping connection test"
        return 0
    fi
    
    echo "Testing connection to database..."
    
    # Test connection
    export PGPASSWORD="$DB_PASSWORD"
    if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT version();" &> /dev/null; then
        success "✅ Database connection successful!"
    else
        error "❌ Database connection failed!"
        echo -e "${YELLOW}Please check your database credentials and try again.${NC}"
        echo "You can continue with deployment and fix the connection later."
        echo
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    unset PGPASSWORD
    echo
}

# Generate deployment command
generate_deployment_command() {
    echo -e "${BOLD}🚀 Deployment Command Generation${NC}"
    
    # Build the deployment command
    local cmd="./scripts/deploy-supabase-setup.sh"
    
    # Add environment
    cmd="$cmd --env $ENVIRONMENT"
    
    # Add domain configuration
    if [ -n "$DOMAIN" ]; then
        cmd="$cmd --domain $DOMAIN"
        
        if [ -n "$SSL_EMAIL" ]; then
            cmd="$cmd --ssl-email $SSL_EMAIL"
        fi
    fi
    
    # Add skip options
    if [ "$SKIP_NGINX" = "true" ]; then
        cmd="$cmd --skip-nginx"
    fi
    
    if [ "$SKIP_SSL" = "true" ]; then
        cmd="$cmd --skip-ssl"
    fi
    
    # Add Supabase configuration
    cmd="$cmd --supabase-url '$SUPABASE_URL'"
    cmd="$cmd --supabase-anon '$SUPABASE_ANON_KEY'"
    cmd="$cmd --supabase-service '$SUPABASE_SERVICE_ROLE_KEY'"
    
    # Add database configuration
    cmd="$cmd --db-host '$DB_HOST'"
    cmd="$cmd --db-port '$DB_PORT'"
    cmd="$cmd --db-name '$DB_DATABASE'"
    cmd="$cmd --db-user '$DB_USERNAME'"
    cmd="$cmd --db-pass '$DB_PASSWORD'"
    
    # Save command to file
    local cmd_file="$SCRIPT_DIR/generated-deploy-command.sh"
    echo "#!/bin/bash" > "$cmd_file"
    echo "# Generated deployment command for PKKI ITERA" >> "$cmd_file"
    echo "# Generated on: $(date)" >> "$cmd_file"
    echo "# Environment: $ENVIRONMENT" >> "$cmd_file"
    echo "" >> "$cmd_file"
    echo "$cmd" >> "$cmd_file"
    chmod +x "$cmd_file"
    
    echo -e "${GREEN}✅ Deployment command generated!${NC}"
    echo
    echo -e "${BOLD}Generated command:${NC}"
    echo -e "${CYAN}$cmd${NC}"
    echo
    echo -e "${BOLD}Saved to:${NC} $cmd_file"
    echo
}

# Show deployment summary
show_deployment_summary() {
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                       📋 DEPLOYMENT SUMMARY                         ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${BOLD}Environment:${NC} $ENVIRONMENT"
    if [ -n "$DOMAIN" ]; then
        echo -e "${BOLD}Domain:${NC} $DOMAIN"
        if [ -n "$SSL_EMAIL" ]; then
            echo -e "${BOLD}SSL Email:${NC} $SSL_EMAIL"
        fi
    fi
    echo -e "${BOLD}Supabase URL:${NC} $SUPABASE_URL"
    echo -e "${BOLD}Database Host:${NC} $DB_HOST"
    echo -e "${BOLD}Database Name:${NC} $DB_DATABASE"
    echo -e "${BOLD}Database User:${NC} $DB_USERNAME"
    echo
    echo -e "${YELLOW}📝 Next Steps:${NC}"
    echo "1. Review the generated deployment command"
    echo "2. Run the deployment script as root or with sudo"
    echo "3. Monitor the deployment process"
    echo "4. Test the application after deployment"
    echo
    echo -e "${BOLD}To run the deployment:${NC}"
    echo -e "${GREEN}sudo ./scripts/generated-deploy-command.sh${NC}"
    echo
    echo -e "${RED}⚠️  Important Security Notes:${NC}"
    echo "• The generated script contains sensitive credentials"
    echo "• Make sure to secure or delete it after deployment"
    echo "• Never commit it to version control"
    echo
}

# Offer to run deployment
offer_deployment() {
    echo -e "${BOLD}🚀 Ready to Deploy?${NC}"
    echo "Would you like to run the deployment now?"
    echo
    echo -e "${YELLOW}Note: This requires root/sudo privileges and will modify your system.${NC}"
    echo
    read -p "Run deployment now? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo
        log "Starting deployment..."
        
        # Check if running as root
        if [ "$EUID" -ne 0 ]; then
            echo "Deployment requires root privileges. Switching to sudo..."
            sudo "$SCRIPT_DIR/generated-deploy-command.sh"
        else
            "$SCRIPT_DIR/generated-deploy-command.sh"
        fi
    else
        echo
        info "Deployment skipped. You can run it later with:"
        echo -e "${GREEN}sudo ./scripts/generated-deploy-command.sh${NC}"
    fi
}

# Cleanup function
cleanup_on_exit() {
    echo
    info "Configuration wizard completed."
}

# Main function
main() {
    # Set up cleanup on exit
    trap cleanup_on_exit EXIT
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        error "❌ This script must be run from the Laravel project root"
        error "Expected to find artisan file in: $PROJECT_ROOT"
        exit 1
    fi
    
    # Show welcome message
    show_welcome
    
    # Gather configuration
    get_environment
    get_domain_config
    get_supabase_config
    get_database_config
    
    # Test database connection
    test_database_connection
    
    # Generate deployment command
    generate_deployment_command
    
    # Show summary
    show_deployment_summary
    
    # Offer to run deployment
    offer_deployment
    
    echo
    success "🎉 Configuration wizard completed successfully!"
    echo
    echo -e "${BLUE}For manual deployment, use:${NC}"
    echo -e "${GREEN}sudo ./scripts/generated-deploy-command.sh${NC}"
    echo
}

# Run main function
main "$@"
