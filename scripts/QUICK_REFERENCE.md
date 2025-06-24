# PKKI ITERA Deployment Scripts - Quick Reference

## 🚀 Main Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| `deploy-manager.sh` | 🎯 **Main Interface** - Interactive menu for all operations | `./scripts/deploy-manager.sh` |
| `supabase-config-wizard.sh` | 🧙 **Setup Wizard** - Interactive Supabase configuration | `./scripts/supabase-config-wizard.sh` |
| `deploy-supabase-setup.sh` | 🚀 **Full Deployment** - Complete deployment automation | `./scripts/deploy-supabase-setup.sh [OPTIONS]` |
| `quick-deploy.sh` | ⚡ **Quick Scenarios** - Pre-configured deployments | `./scripts/quick-deploy.sh SCENARIO [OPTIONS]` |
| `check-deployment-status.sh` | 🔍 **Health Check** - Status monitoring and diagnostics | `./scripts/check-deployment-status.sh` |
| `rollback-deployment.sh` | 🔄 **Rollback** - Restore previous deployment | `./scripts/rollback-deployment.sh [OPTIONS]` |

## 📋 Quick Commands

### First Time Setup
```bash
# Interactive setup (recommended)
./scripts/deploy-manager.sh

# Or use wizard directly
./scripts/supabase-config-wizard.sh
```

### Development
```bash
# Local development setup
./scripts/quick-deploy.sh local

# Check status
./scripts/check-deployment-status.sh
```

### Production Deployment
```bash
# Production with domain
./scripts/quick-deploy.sh production --domain pkki.itera.ac.id --email admin@itera.ac.id

# Or use interactive menu
./scripts/deploy-manager.sh
```

### Maintenance
```bash
# Update existing deployment
./scripts/quick-deploy.sh update

# Check deployment health
./scripts/check-deployment-status.sh

# Rollback if needed
./scripts/rollback-deployment.sh
```

### Emergency
```bash
# List available backups
./scripts/rollback-deployment.sh --list-backups

# Rollback to specific backup
./scripts/rollback-deployment.sh --rollback-to backup-20240624-120000

# Check what's wrong
./scripts/check-deployment-status.sh
```

## 🎯 Choose Your Method

### 👥 For Beginners
Start with the **Deployment Manager** - it guides you through everything:
```bash
./scripts/deploy-manager.sh
```

### 🧙 For Interactive Setup
Use the **Configuration Wizard** to set up Supabase:
```bash
./scripts/supabase-config-wizard.sh
```

### ⚡ For Quick Actions
Use **Quick Deploy** for common scenarios:
```bash
./scripts/quick-deploy.sh local              # Development
./scripts/quick-deploy.sh production --domain example.com --email admin@example.com
```

### 🔧 For Advanced Users
Use the **Full Deployment Script** with custom options:
```bash
./scripts/deploy-supabase-setup.sh --env production --domain example.com --ssl-email admin@example.com
```

## 🆘 Need Help?

- Run any script with `--help` for detailed options
- Use `./scripts/deploy-manager.sh` and select "Help" from the menu
- Check `./scripts/README.md` for comprehensive documentation
- Use `--dry-run` with any script to see what it would do

## 🔍 Troubleshooting

```bash
# Check current status
./scripts/check-deployment-status.sh

# View recent logs
./scripts/deploy-manager.sh  # Select "View Logs"

# Test deployment without changes
./scripts/deploy-supabase-setup.sh --dry-run

# Emergency rollback
./scripts/rollback-deployment.sh
```
