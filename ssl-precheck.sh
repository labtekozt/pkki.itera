#!/bin/bash

# PKKI ITERA - SSL Pre-Check Script
# Quick check before running SSL setup
# Usage: ./ssl-precheck.sh

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

DOMAIN="hki.proyekai.com"

echo -e "${BLUE}🔍 PKKI ITERA SSL Pre-Check${NC}"
echo "=================================="
echo ""

# 1. Get server IP
echo -e "${BLUE}1. Server Information:${NC}"
SERVER_IP=$(curl -s http://checkip.amazonaws.com 2>/dev/null || curl -s http://icanhazip.com 2>/dev/null || echo "Unable to detect")
echo "   Server IP: $SERVER_IP"

# 2. Check DNS
echo -e "${BLUE}2. DNS Check:${NC}"
DOMAIN_IP=$(dig +short $DOMAIN 2>/dev/null || echo "Failed")
echo "   Domain IP: $DOMAIN_IP"

if [ "$DOMAIN_IP" = "$SERVER_IP" ]; then
    echo -e "   Status: ${GREEN}✅ DNS correctly configured${NC}"
    DNS_OK=true
else
    echo -e "   Status: ${RED}❌ DNS mismatch or not configured${NC}"
    DNS_OK=false
fi

# 3. Check HTTP access
echo -e "${BLUE}3. HTTP Access:${NC}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
echo "   HTTP Status: $HTTP_STATUS"

if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo -e "   Status: ${GREEN}✅ HTTP accessible${NC}"
    HTTP_OK=true
else
    # Try with port 3003
    HTTP_STATUS_3003=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN:3003" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS_3003" = "200" ]; then
        echo -e "   Status: ${YELLOW}⚠️ Accessible on port 3003, need nginx fix${NC}"
        HTTP_OK=false
    else
        echo -e "   Status: ${RED}❌ HTTP not accessible${NC}"
        HTTP_OK=false
    fi
fi

# 4. Check if already has SSL
echo -e "${BLUE}4. SSL Status:${NC}"
if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo -e "   Status: ${GREEN}✅ SSL certificate exists${NC}"
    SSL_EXISTS=true
    
    # Check expiration
    EXPIRE_DATE=$(openssl x509 -enddate -noout -in "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" 2>/dev/null | cut -d= -f2)
    if [ -n "$EXPIRE_DATE" ]; then
        echo "   Expires: $EXPIRE_DATE"
    fi
else
    echo -e "   Status: ${YELLOW}⚠️ No SSL certificate found${NC}"
    SSL_EXISTS=false
fi

# 5. Check services
echo -e "${BLUE}5. Services:${NC}"
if systemctl is-active --quiet nginx; then
    echo -e "   Nginx: ${GREEN}✅ Running${NC}"
    NGINX_OK=true
else
    echo -e "   Nginx: ${RED}❌ Not running${NC}"
    NGINX_OK=false
fi

if systemctl is-active --quiet php8.2-fpm; then
    echo -e "   PHP-FPM: ${GREEN}✅ Running${NC}"
    PHP_OK=true
else
    echo -e "   PHP-FPM: ${RED}❌ Not running${NC}"
    PHP_OK=false
fi

# 6. Check certbot
echo -e "${BLUE}6. Certbot:${NC}"
if command -v certbot &> /dev/null; then
    echo -e "   Status: ${GREEN}✅ Installed${NC}"
    echo "   Version: $(certbot --version 2>/dev/null | head -1)"
    CERTBOT_OK=true
else
    echo -e "   Status: ${RED}❌ Not installed${NC}"
    CERTBOT_OK=false
fi

echo ""
echo "=================================="
echo -e "${BLUE}📋 Summary:${NC}"

if [ "$DNS_OK" = "true" ] && [ "$HTTP_OK" = "true" ] && [ "$NGINX_OK" = "true" ] && [ "$CERTBOT_OK" = "true" ]; then
    echo -e "${GREEN}✅ Ready for SSL setup!${NC}"
    echo ""
    echo "Run: sudo ./ssl-setup-manual.sh"
elif [ "$SSL_EXISTS" = "true" ]; then
    echo -e "${GREEN}✅ SSL already configured!${NC}"
    echo ""
    echo "Test your SSL: https://$DOMAIN"
    echo "SSL Labs test: https://www.ssllabs.com/ssltest/analyze.html?d=$DOMAIN"
else
    echo -e "${RED}❌ Issues found, need to fix first:${NC}"
    echo ""
    
    if [ "$DNS_OK" = "false" ]; then
        echo "🔧 DNS Fix needed:"
        echo "   - Point $DOMAIN to $SERVER_IP"
        echo "   - Add A record: @ → $SERVER_IP"
        echo "   - Add A record: www → $SERVER_IP"
        echo ""
    fi
    
    if [ "$HTTP_OK" = "false" ]; then
        echo "🔧 HTTP Fix needed:"
        echo "   - Check nginx configuration"
        echo "   - Ensure site is accessible on port 80"
        echo ""
    fi
    
    if [ "$NGINX_OK" = "false" ]; then
        echo "🔧 Nginx Fix needed:"
        echo "   sudo systemctl start nginx"
        echo ""
    fi
    
    if [ "$CERTBOT_OK" = "false" ]; then
        echo "🔧 Certbot Fix needed:"
        echo "   sudo apt update && sudo apt install -y certbot python3-certbot-nginx"
        echo ""
    fi
fi

echo "=================================="
