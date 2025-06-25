#!/bin/bash

# GitHub SSH Deployment Setup Script
# This script helps you set up SSH keys and GitHub secrets for automatic deployment

set -e

echo "🚀 PKKI ITERA - GitHub SSH Deployment Setup"
echo "============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
REPO_URL="https://github.com/labtekozt/pkki.itera"
SSH_KEY_NAME="github_actions_pkki"
SSH_KEY_PATH="$HOME/.ssh/$SSH_KEY_NAME"
VPS_HOST="34.101.196.4"
VPS_USER="partikelxyz"

echo -e "${YELLOW}This script will:${NC}"
echo "1. Generate SSH key for GitHub Actions"
echo "2. Show you how to add it to your VPS" 
echo "3. Provide GitHub secrets to configure"
echo ""

read -p "Continue? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 1
fi

# Step 1: Generate SSH Key
echo -e "${YELLOW}Step 1: Generating SSH key...${NC}"
if [ -f "$SSH_KEY_PATH" ]; then
    echo -e "${RED}SSH key already exists at $SSH_KEY_PATH${NC}"
    read -p "Overwrite? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Using existing key..."
    else
        rm -f "$SSH_KEY_PATH" "$SSH_KEY_PATH.pub"
        ssh-keygen -t ed25519 -C "github-actions@pkki-itera" -f "$SSH_KEY_PATH" -N ""
        echo -e "${GREEN}✅ New SSH key generated${NC}"
    fi
else
    ssh-keygen -t ed25519 -C "github-actions@pkki-itera" -f "$SSH_KEY_PATH" -N ""
    echo -e "${GREEN}✅ SSH key generated${NC}"
fi

# Step 2: Display public key
echo -e "${YELLOW}Step 2: Add this public key to your VPS${NC}"
echo "==============================================" 
echo "Public key content:"
echo ""
cat "$SSH_KEY_PATH.pub"
echo ""
echo -e "${YELLOW}Commands to run on your VPS:${NC}"
echo "ssh $VPS_USER@$VPS_HOST"
echo "echo \"$(cat $SSH_KEY_PATH.pub)\" >> ~/.ssh/authorized_keys"
echo "chmod 600 ~/.ssh/authorized_keys"
echo ""

# Step 3: Test SSH connection
echo -e "${YELLOW}Step 3: Testing SSH connection...${NC}"
read -p "Have you added the public key to your VPS? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Testing SSH connection..."
    if ssh -i "$SSH_KEY_PATH" -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$VPS_USER@$VPS_HOST" "echo 'SSH connection successful!'" 2>/dev/null; then
        echo -e "${GREEN}✅ SSH connection successful!${NC}"
    else
        echo -e "${RED}❌ SSH connection failed. Please check:${NC}"
        echo "- Public key is added to VPS ~/.ssh/authorized_keys"
        echo "- VPS SSH service is running"
        echo "- Firewall allows SSH connections"
    fi
fi

# Step 4: GitHub Secrets
echo ""
echo -e "${YELLOW}Step 4: GitHub Repository Secrets${NC}"
echo "====================================="
echo "Go to: $REPO_URL/settings/secrets/actions"
echo ""
echo "Add these secrets:"
echo ""
echo -e "${GREEN}Secret Name: SSH_PRIVATE_KEY${NC}"
echo "Value:"
echo "------"
cat "$SSH_KEY_PATH"
echo "------"
echo ""
echo -e "${GREEN}Secret Name: SERVER_HOST${NC}"
echo "Value: $VPS_HOST"
echo ""
echo -e "${GREEN}Secret Name: SERVER_USER${NC}"
echo "Value: $VPS_USER"
echo ""

# Step 5: Instructions
echo -e "${YELLOW}Step 5: Test Deployment${NC}"
echo "========================"
echo "1. Push changes to 'development' branch"
echo "2. Check GitHub Actions tab for deployment status"
echo "3. If successful, merge to 'main' for production"
echo ""
echo -e "${GREEN}🎉 Setup complete!${NC}"
echo ""
echo -e "${YELLOW}Quick commands:${NC}"
echo "# Test SSH manually:"
echo "ssh -i $SSH_KEY_PATH $VPS_USER@$VPS_HOST"
echo ""
echo "# Deploy manually:"
echo "git push origin development  # Deploy to dev"
echo "git push origin main         # Deploy to production"
echo ""
echo -e "${YELLOW}Documentation:${NC}"
echo "See docs/GITHUB_DEPLOYMENT.md for full details"
