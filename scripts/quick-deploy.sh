#!/bin/bash

# PKKI ITERA - Quick Deployment Scripts
# Collection of pre-configured deployment commands for common scenarios
# Version: 1.0.0

set -e

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Script information
readonly SCRIPT_VERSION="1.0.0"
readonly SCRIPT_NAME="PKKI ITERA Quick Deployment"

# Show help
show_help() {
    echo -e "${CYAN}${SCRIPT_NAME} v${SCRIPT_VERSION}${NC}"
    echo
    echo "Quick deployment scripts for common scenarios"
    echo
    echo "USAGE:"
    echo "    ./scripts/quick-deploy.sh SCENARIO [OPTIONS]"
    echo
    echo "SCENARIOS:"
    echo "    local               Local development setup"
    echo "    staging             Staging server deployment"
    echo "    production          Production server deployment"
    echo "    nginx-only          Only setup Nginx configuration"
    echo "    ssl-only            Only setup SSL certificates"
    echo "    update              Update existing deployment"
    echo "    wizard              Run interactive configuration wizard"
    echo
    echo "OPTIONS:"
    echo "    --domain DOMAIN     Domain name for the application"
    echo "    --email EMAIL       Email for SSL certificate"
    echo "    --help              Show this help message"
    echo
    echo "EXAMPLES:"
    echo "    # Local development"
    echo "    ./scripts/quick-deploy.sh local"
    echo
    echo "    # Production with domain"
    echo "    ./scripts/quick-deploy.sh production --domain pkki.itera.ac.id --email admin@itera.ac.id"
    echo
    echo "    # Interactive wizard"
    echo "    ./scripts/quick-deploy.sh wizard"
    echo
}

# Parse arguments
DOMAIN=""
EMAIL=""
SCENARIO=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --domain)
            DOMAIN="$2"
            shift 2
            ;;
        --email)
            EMAIL="$2"
            shift 2
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        local|staging|production|nginx-only|ssl-only|update|wizard)
            SCENARIO="$1"
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_help
            exit 1
            ;;
    esac
done

if [ -z "$SCENARIO" ]; then
    echo -e "${RED}Error: Scenario is required${NC}"
    show_help
    exit 1
fi

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

case $SCENARIO in
    "local")
        echo -e "${GREEN}🏠 Setting up local development environment...${NC}"
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --env development \
            --skip-nginx \
            --skip-ssl \
            --skip-system
        ;;
    
    "staging")
        echo -e "${YELLOW}🧪 Setting up staging environment...${NC}"
        if [ -z "$DOMAIN" ]; then
            echo -e "${RED}Error: --domain is required for staging deployment${NC}"
            exit 1
        fi
        if [ -z "$EMAIL" ]; then
            echo -e "${RED}Error: --email is required for staging deployment${NC}"
            exit 1
        fi
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --env staging \
            --domain "$DOMAIN" \
            --ssl-email "$EMAIL"
        ;;
    
    "production")
        echo -e "${RED}🏭 Setting up production environment...${NC}"
        if [ -z "$DOMAIN" ]; then
            echo -e "${RED}Error: --domain is required for production deployment${NC}"
            exit 1
        fi
        if [ -z "$EMAIL" ]; then
            echo -e "${RED}Error: --email is required for production deployment${NC}"
            exit 1
        fi
        echo -e "${YELLOW}⚠️  This will set up a production environment. Make sure you have:${NC}"
        echo "  • Configured your Supabase credentials"
        echo "  • Verified DNS settings point to this server"
        echo "  • Backed up any existing data"
        echo
        read -p "Continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Cancelled."
            exit 0
        fi
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --env production \
            --domain "$DOMAIN" \
            --ssl-email "$EMAIL"
        ;;
    
    "nginx-only")
        echo -e "${BLUE}🌐 Setting up Nginx only...${NC}"
        if [ -z "$DOMAIN" ]; then
            echo -e "${RED}Error: --domain is required for Nginx setup${NC}"
            exit 1
        fi
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --nginx-only \
            --domain "$DOMAIN"
        ;;
    
    "ssl-only")
        echo -e "${GREEN}🔒 Setting up SSL only...${NC}"
        if [ -z "$DOMAIN" ]; then
            echo -e "${RED}Error: --domain is required for SSL setup${NC}"
            exit 1
        fi
        if [ -z "$EMAIL" ]; then
            echo -e "${RED}Error: --email is required for SSL setup${NC}"
            exit 1
        fi
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --ssl-only \
            --domain "$DOMAIN" \
            --ssl-email "$EMAIL"
        ;;
    
    "update")
        echo -e "${CYAN}🔄 Updating existing deployment...${NC}"
        "$SCRIPT_DIR/deploy-supabase-setup.sh" \
            --skip-system \
            --skip-nginx \
            --skip-ssl
        ;;
    
    "wizard")
        echo -e "${PURPLE}🧙 Starting configuration wizard...${NC}"
        "$SCRIPT_DIR/supabase-config-wizard.sh"
        ;;
    
    *)
        echo -e "${RED}Unknown scenario: $SCENARIO${NC}"
        show_help
        exit 1
        ;;
esac
