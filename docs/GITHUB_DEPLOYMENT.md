# GitHub SSH Deployment Setup

This guide will help you set up automatic deployment from GitHub to your VPS using SSH.

## 🚀 Quick Setup

### 1. Generate SSH Key for GitHub Actions

On your local machine, generate a new SSH key specifically for GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions@pkki-itera" -f ~/.ssh/github_actions_pkki
```

### 2. Add Public Key to VPS

Copy the public key to your VPS:

```bash
# Copy public key content
cat ~/.ssh/github_actions_pkki.pub

# On your VPS, add it to authorized_keys
ssh partikelxyz@34.101.196.4
echo "YOUR_PUBLIC_KEY_CONTENT" >> ~/.ssh/authorized_keys
```

### 3. Configure GitHub Repository Secrets

Go to your GitHub repository: `https://github.com/labtekozt/pkki.itera/settings/secrets/actions`

Add these secrets:

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `SSH_PRIVATE_KEY` | Content of `~/.ssh/github_actions_pkki` | Private key for SSH access |
| `SERVER_HOST` | `34.101.196.4` | Your VPS IP address |
| `SERVER_USER` | `partikelxyz` | Your VPS username |

### 4. Test the Deployment

1. Commit and push your changes to the `development` branch
2. Check the GitHub Actions tab to see if deployment works
3. If successful, merge to `main` for production deployment

## 🔄 How It Works

- **Push to `development`** → Deploys to development environment
- **Push to `main`** → Deploys to production with maintenance mode
- **Manual trigger** → Can deploy any branch manually

## 📋 Deployment Process

1. **Maintenance Mode** - Site shows "updating" message
2. **Git Pull** - Gets latest code from GitHub
3. **Dependencies** - Updates Composer and NPM packages
4. **Build Assets** - Compiles CSS/JS
5. **Laravel Cache** - Optimizes configuration
6. **Database** - Runs migrations
7. **Permissions** - Fixes file permissions
8. **Go Live** - Disables maintenance mode

## 🔧 Manual Deployment

You can also trigger deployment manually:

1. Go to GitHub Actions tab
2. Select "Simple Deploy" workflow
3. Click "Run workflow"
4. Choose branch and run

## 🔍 Monitoring

After deployment, the workflow will:
- ✅ Verify the site is accessible
- 📝 Show deployment status
- 🚨 Alert if something goes wrong

## ⚠️ Troubleshooting

### SSH Connection Issues
```bash
# Test SSH connection locally
ssh -i ~/.ssh/github_actions_pkki partikelxyz@34.101.196.4

# Check authorized_keys on server
cat ~/.ssh/authorized_keys
```

### Permission Issues
```bash
# On VPS, fix permissions
cd /home/partikelxyz/pkki.itera
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Deployment Fails
1. Check GitHub Actions logs for errors
2. SSH to server and check Laravel logs:
   ```bash
   tail -f /home/partikelxyz/pkki.itera/storage/logs/laravel.log
   ```

## 🎯 Next Steps

1. **Set up the GitHub secrets** (most important)
2. **Test with development branch first**
3. **Monitor first few deployments**
4. **Add more automation as needed**

## 💡 Benefits of This Setup

✅ **No more manual `cp` commands**  
✅ **Automatic dependency management**  
✅ **Consistent deployments**  
✅ **Rollback capability with Git**  
✅ **Maintenance mode handling**  
✅ **Error notifications**  

---

**Ready to deploy?** Just push your code to GitHub and watch it deploy automatically! 🚀
