#!/bin/bash

# PKKI ITERA VPS Compatibility Check
# Checks VPS environment before deployment

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[CHECK]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[FAIL]${NC} $1"
}

# Configuration
SSH_KEY="~/.ssh/psd-new"
SSH_USER="partikelxyz"
SSH_HOST="34.124.214.243"

# SSH command wrapper
ssh_exec() {
    ssh -i $SSH_KEY $SSH_USER@$SSH_HOST "$1" 2>/dev/null
}

echo -e "${BLUE}🔍 PKKI ITERA VPS Compatibility Check${NC}"
echo "Checking VPS environment for deployment readiness..."
echo ""

CHECKS_PASSED=0
CHECKS_FAILED=0
WARNINGS=0

# Test SSH connection
print_status "Testing SSH connection..."
if ssh_exec "echo 'Connection test'"; then
    print_success "SSH connection established"
    ((CHECKS_PASSED++))
else
    print_error "SSH connection failed"
    ((CHECKS_FAILED++))
    exit 1
fi

# Check operating system
print_status "Checking operating system..."
OS_INFO=$(ssh_exec "lsb_release -d" | cut -f2)
if [[ $OS_INFO == *"Ubuntu"* ]]; then
    print_success "Operating System: $OS_INFO"
    ((CHECKS_PASSED++))
else
    print_warning "Operating System: $OS_INFO (may not be fully compatible)"
    ((WARNINGS++))
fi

# Check disk space
print_status "Checking disk space..."
DISK_USAGE=$(ssh_exec "df -h / | tail -1 | awk '{print \$5}' | sed 's/%//'")
if [ "$DISK_USAGE" -lt 80 ]; then
    print_success "Disk usage: ${DISK_USAGE}% (sufficient space available)"
    ((CHECKS_PASSED++))
else
    print_warning "Disk usage: ${DISK_USAGE}% (consider freeing up space)"
    ((WARNINGS++))
fi

# Check memory
print_status "Checking memory..."
MEMORY_MB=$(ssh_exec "free -m | grep '^Mem:' | awk '{print \$2}'")
if [ "$MEMORY_MB" -ge 1024 ]; then
    print_success "Memory: ${MEMORY_MB}MB (sufficient for Laravel + React)"
    ((CHECKS_PASSED++))
else
    print_warning "Memory: ${MEMORY_MB}MB (may be insufficient for optimal performance)"
    ((WARNINGS++))
fi

# Check PHP version
print_status "Checking PHP version..."
PHP_VERSION=$(ssh_exec "php --version 2>/dev/null | head -n1" || echo "not installed")
if [[ $PHP_VERSION == *"PHP 8."* ]]; then
    print_success "PHP version: $PHP_VERSION"
    ((CHECKS_PASSED++))
elif [[ $PHP_VERSION == *"PHP 7."* ]]; then
    print_warning "PHP version: $PHP_VERSION (Laravel 11 requires PHP 8.2+)"
    ((WARNINGS++))
else
    print_error "PHP not installed or incompatible version"
    ((CHECKS_FAILED++))
fi

# Check Node.js version
print_status "Checking Node.js version..."
NODE_VERSION=$(ssh_exec "node --version 2>/dev/null" || echo "not installed")
if [[ $NODE_VERSION == v1[89].* ]] || [[ $NODE_VERSION == v[2-9][0-9].* ]]; then
    print_success "Node.js version: $NODE_VERSION (compatible)"
    ((CHECKS_PASSED++))
elif [[ $NODE_VERSION == v1[4-7].* ]]; then
    print_warning "Node.js version: $NODE_VERSION (minimum compatible, update recommended)"
    ((WARNINGS++))
elif [[ $NODE_VERSION == v1[0-3].* ]]; then
    print_error "Node.js version: $NODE_VERSION (too old, requires 14.18.0+)"
    ((CHECKS_FAILED++))
else
    print_error "Node.js not installed"
    ((CHECKS_FAILED++))
fi

# Check npm version
print_status "Checking npm version..."
NPM_VERSION=$(ssh_exec "npm --version 2>/dev/null" || echo "not installed")
if [[ $NPM_VERSION =~ ^[6-9]\. ]] || [[ $NPM_VERSION =~ ^[1-9][0-9]\. ]]; then
    print_success "npm version: $NPM_VERSION"
    ((CHECKS_PASSED++))
else
    print_error "npm not installed or incompatible version: $NPM_VERSION"
    ((CHECKS_FAILED++))
fi

# Check Composer
print_status "Checking Composer..."
COMPOSER_VERSION=$(ssh_exec "composer --version 2>/dev/null" || echo "not installed")
if [[ $COMPOSER_VERSION == *"Composer version"* ]]; then
    print_success "Composer installed: $COMPOSER_VERSION"
    ((CHECKS_PASSED++))
else
    print_error "Composer not installed"
    ((CHECKS_FAILED++))
fi

# Check web server
print_status "Checking web server..."
if ssh_exec "systemctl is-active nginx" >/dev/null 2>&1; then
    print_success "Nginx is running"
    ((CHECKS_PASSED++))
elif ssh_exec "systemctl is-active apache2" >/dev/null 2>&1; then
    print_success "Apache is running"
    ((CHECKS_PASSED++))
else
    print_error "No web server running (Nginx/Apache)"
    ((CHECKS_FAILED++))
fi

# Check database
print_status "Checking database server..."
if ssh_exec "systemctl is-active mysql" >/dev/null 2>&1; then
    print_success "MySQL is running"
    ((CHECKS_PASSED++))
elif ssh_exec "systemctl is-active mariadb" >/dev/null 2>&1; then
    print_success "MariaDB is running"
    ((CHECKS_PASSED++))
else
    print_error "No database server running (MySQL/MariaDB)"
    ((CHECKS_FAILED++))
fi

# Check ports
print_status "Checking port availability..."
if ! ssh_exec "ss -tuln | grep ':3003'"; then
    print_success "Port 3003 is available"
    ((CHECKS_PASSED++))
else
    print_warning "Port 3003 is already in use"
    ((WARNINGS++))
fi

# Check available ports for HTTPS
if ! ssh_exec "ss -tuln | grep ':3443'"; then
    print_success "Port 3443 is available"
    ((CHECKS_PASSED++))
else
    print_warning "Port 3443 is already in use"
    ((WARNINGS++))
fi

# Summary
echo ""
echo -e "${BLUE}📊 COMPATIBILITY CHECK SUMMARY${NC}"
echo "=================================="
echo "Checks Passed: ${GREEN}$CHECKS_PASSED${NC}"
echo "Checks Failed: ${RED}$CHECKS_FAILED${NC}"
echo "Warnings: ${YELLOW}$WARNINGS${NC}"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}🎉 EXCELLENT! VPS is fully ready for deployment${NC}"
        READINESS="READY"
    else
        echo -e "${YELLOW}⚠️ GOOD! VPS is ready with minor issues${NC}"
        READINESS="MOSTLY_READY"
    fi
else
    echo -e "${RED}❌ VPS needs fixes before deployment${NC}"
    READINESS="NOT_READY"
fi

echo ""
echo -e "${BLUE}📋 RECOMMENDED ACTIONS:${NC}"

if [[ $NODE_VERSION == v1[0-3].* ]] || [[ $NODE_VERSION == "not installed" ]]; then
    echo "• Update Node.js: ./fix-nodejs-vps.sh"
fi

if [[ $PHP_VERSION == "not installed" ]] || [[ $PHP_VERSION == *"PHP 7."* ]]; then
    echo "• Install/Update PHP 8.2: sudo apt install php8.2"
fi

if [[ $COMPOSER_VERSION == "not installed" ]]; then
    echo "• Install Composer: curl -sS https://getcomposer.org/installer | php"
fi

if [ $CHECKS_FAILED -gt 0 ]; then
    echo "• Fix critical issues before deployment"
fi

echo ""
echo -e "${BLUE}🚀 DEPLOYMENT COMMANDS:${NC}"

if [ "$READINESS" = "READY" ] || [ "$READINESS" = "MOSTLY_READY" ]; then
    echo "• Full deployment: ./deploy-vps.sh deploy"
    echo "• Test deployment: ./deploy-vps.sh"
    echo "• Fix Node.js only: ./fix-nodejs-vps.sh"
else
    echo "• Fix issues first, then run: ./deploy-vps.sh deploy"
fi

echo ""
echo "Check completed: $(date)"

# Exit with appropriate code
if [ $CHECKS_FAILED -gt 0 ]; then
    exit 1
else
    exit 0
fi
