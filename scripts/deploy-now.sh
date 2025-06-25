#!/bin/bash

# Simple deployment script for PKKI ITERA
# Use this until GitHub Actions secrets are configured

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
VPS_HOST="34.101.196.4"
VPS_USER="partikelxyz"
PROJECT_PATH="/home/partikelxyz/pkki.itera"

echo -e "${BLUE}🚀 PKKI ITERA - Simple Deployment${NC}"
echo "=================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Not in Laravel project directory${NC}"
    echo "Please run this script from the project root directory"
    exit 1
fi

# Get current branch
CURRENT_BRANCH=$(git branch --show-current)
echo -e "${YELLOW}Current branch: $CURRENT_BRANCH${NC}"

# Ask for confirmation
echo ""
echo "This will deploy to production server:"
echo "Server: $VPS_HOST"
echo "Path: $PROJECT_PATH"
echo "Branch: $CURRENT_BRANCH"
echo ""
read -p "Continue with deployment? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

echo -e "${YELLOW}Starting deployment...${NC}"

# Deploy to server
ssh "$VPS_USER@$VPS_HOST" << EOF
    set -e
    
    echo "📂 Navigating to project directory..."
    cd $PROJECT_PATH
    
    echo "🚦 Enabling maintenance mode..."
    php artisan down --message="Deployment in progress..." --retry=60 || echo "Maintenance mode failed"
    
    echo "📥 Pulling latest changes from $CURRENT_BRANCH..."
    git fetch origin
    git reset --hard origin/$CURRENT_BRANCH
    
    echo "📦 Installing/updating dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    echo "🏗️ Building frontend assets..."
    npm ci --production
    npm run build
    
    echo "⚙️ Optimizing Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "🗄️ Running database migrations..."
    php artisan migrate --force
    
    echo "🧹 Clearing caches..."
    php artisan cache:clear
    
    echo "🔒 Setting permissions..."
    chmod -R 755 storage bootstrap/cache
    sudo chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || echo "Permission change failed, continuing..."
    
    echo "✅ Disabling maintenance mode..."
    php artisan up
    
    echo "🎉 Deployment completed successfully!"
EOF

# Verify deployment
echo -e "${YELLOW}Verifying deployment...${NC}"
sleep 5

if curl -f https://hki.proyekai.com > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Site is accessible and working!${NC}"
    echo -e "${GREEN}🌐 Visit: https://hki.proyekai.com${NC}"
    echo -e "${GREEN}🔧 Admin: https://hki.proyekai.com/admin${NC}"
else
    echo -e "${YELLOW}⚠️ Site verification failed (might be temporary)${NC}"
    echo "Please check manually: https://hki.proyekai.com"
fi

echo ""
echo -e "${BLUE}📋 Deployment Summary:${NC}"
echo "• Branch deployed: $CURRENT_BRANCH"
echo "• Deployment time: $(date)"
echo "• Server: $VPS_HOST"
echo ""
echo -e "${YELLOW}💡 Next time, use GitHub Actions:${NC}"
echo "1. Set up GitHub secrets (see GITHUB_SECRETS_SETUP.md)"
echo "2. Just push to GitHub: git push origin $CURRENT_BRANCH"
