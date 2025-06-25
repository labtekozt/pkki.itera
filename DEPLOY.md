# 🚀 Quick Deployment Guide

## Deploy with GitHub (Recommended)

1. **Run the setup script** (only once):
   ```bash
   ./scripts/setup-github-deployment.sh
   ```

2. **Add secrets to GitHub**:
   - Go to: https://github.com/labtekozt/pkki.itera/settings/secrets/actions
   - Add the SSH_PRIVATE_KEY, SERVER_HOST, and SERVER_USER secrets

3. **Deploy automatically**:
   ```bash
   git push origin development  # Deploy to dev/staging
   git push origin main         # Deploy to production
   ```

## Manual Deployment (Backup Method)

If GitHub Actions isn't working:

```bash
# SSH to server
ssh partikelxyz@34.101.196.4

# Navigate to project
cd /home/partikelxyz/pkki.itera

# Pull latest changes
git pull origin main  # or development

# Update dependencies and build
composer install --no-dev --optimize-autoloader
npm ci --production && npm run build

# Laravel optimizations
php artisan config:cache
php artisan migrate --force
php artisan cache:clear
```

## Benefits of GitHub Deployment

✅ **Automatic**: Push code → Automatic deployment  
✅ **Safe**: Maintenance mode during deployment  
✅ **Consistent**: Same process every time  
✅ **Backup**: Database backup before production deployment  
✅ **Rollback**: Easy to revert with Git  
✅ **Monitoring**: See deployment status in GitHub  

## Troubleshooting

### SSH Issues
```bash
# Test SSH connection
ssh partikelxyz@34.101.196.4

# Check SSH key on server
cat ~/.ssh/authorized_keys
```

### Deployment Fails
1. Check GitHub Actions logs
2. SSH to server and check Laravel logs:
   ```bash
   tail -f /home/partikelxyz/pkki.itera/storage/logs/laravel.log
   ```

### Site Not Working
```bash
# On server, check status
cd /home/partikelxyz/pkki.itera
php artisan down  # Enable maintenance
php artisan up    # Disable maintenance

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

---

**Need help?** Check `docs/GITHUB_DEPLOYMENT.md` for detailed instructions.
