# 🚨 GitHub Secrets Setup Required

The deployment failed because GitHub secrets are not configured yet. Here's how to fix it:

## 🔧 Setup GitHub Secrets (Required)

### Step 1: Go to GitHub Repository Settings
1. Open: https://github.com/labtekozt/pkki.itera
2. Click "Settings" tab
3. Go to "Secrets and variables" → "Actions"
4. Click "New repository secret"

### Step 2: Add These 3 Secrets

#### Secret 1: SSH_PRIVATE_KEY
- **Name**: `SSH_PRIVATE_KEY`
- **Value**: Copy and paste this ENTIRE private key content:

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACDQmpUNgvK+7X+P0k6O2fMs3wmBQDusJ3vM0r/NjxxpZAAAAKDIGnJ/yBpy
fwAAAAtzc2gtZWQyNTUxOQAAACDQmpUNgvK+7X+P0k6O2fMs3wmBQDusJ3vM0r/NjxxpZA
AAAEDOCoYrgtvcR2p2gyD00Qcx0GthObjdUCvDFawbsQH+StCalQ2C8r7tf4/STo7Z8yzf
CYFAO6wne8zSv82PHGlkAAAAGWdpdGh1Yi1hY3Rpb25zQHBra2ktaXRlcmEBAgME
-----END OPENSSH PRIVATE KEY-----
```

#### Secret 2: SERVER_HOST
- **Name**: `SERVER_HOST`
- **Value**: `34.101.196.4`

#### Secret 3: SERVER_USER
- **Name**: `SERVER_USER`
- **Value**: `partikelxyz`

### Step 3: Test Deployment
After adding all secrets, try pushing again:
```bash
git push origin development
```

## 🔄 Alternative: Manual Deployment (Until Secrets Are Set)

If you need to deploy immediately before setting up secrets:

```bash
# SSH to your server
ssh partikelxyz@34.101.196.4

# Navigate to project
cd /home/partikelxyz/pkki.itera

# Enable maintenance mode
php artisan down --message="Updating system..." --retry=60

# Pull latest changes
git fetch origin
git reset --hard origin/development  # or origin/main for production

# Update dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets
npm ci --production
npm run build

# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear

# Fix permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Disable maintenance mode
php artisan up

echo "✅ Manual deployment completed!"
```

## 📱 Quick Setup Script

You can also use our setup script to get the exact secret values:

```bash
./scripts/setup-github-deployment.sh
```

This script will show you exactly what to copy-paste into GitHub secrets.

---

**Once secrets are added, GitHub Actions will work automatically! 🚀**
