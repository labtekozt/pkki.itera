# PKKI ITERA Deployment Credentials

## ⚠️ SECURITY NOTICE
This file contains placeholder values. Replace with actual credentials during deployment.

## Required Credentials

### Database
- Database Password: Set in DB_PASSWORD

### Email Configuration
- Gmail App Password: Set in MAIL_PASSWORD
- Email Address: Set in MAIL_USERNAME

### SSO ITERA
- Client ID: Set in SSO_ITERA_CLIENT_ID
- Client Secret: Set in SSO_ITERA_CLIENT_SECRET

## Setup Instructions

1. Copy `.env.production.template` to `.env`
2. Update all placeholder values
3. Never commit `.env` file to git
4. Use environment variables or secure vaults in production

## Deployment Notes

- All deployment scripts now use placeholder values
- Actual credentials must be set during deployment
- Use secure methods to inject credentials (e.g., environment variables, CI/CD secrets)
