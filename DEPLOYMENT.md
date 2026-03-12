# Railway Deployment Guide

## Default Login Credentials

After deployment, use these credentials to log in:

**Admin Account:**
- Email: `admin@example.com`
- Password: `password`

**Vendor Account:**
- Email: `vendor@example.com`  
- Password: `password`

⚠️ **IMPORTANT**: Change these passwords immediately after first login in production!

## Default Client API Token

A default client is created with the following test token:
- Token: `test-token`

## Environment Variables Required in Railway

Make sure these environment variables are set in Railway:

```env
APP_NAME="Portal"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate with: php artisan key:generate --show>
APP_URL=https://your-app.railway.app

DB_CONNECTION=mysql
# Railway will automatically provide DATABASE_URL which contains:
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_LEVEL=error
```

## Deployment Process

1. Push your code to GitHub
2. Connect Railway to your repository
3. Railway will automatically:
   - Install PHP dependencies with Composer
   - Install Node.js dependencies
   - Build frontend assets with Vite
   - Run database migrations with `--force --seed`
   - Start the PHP development server

## Post-Deployment Checklist

- [ ] Log in with default admin credentials
- [ ] Change admin password
- [ ] Change vendor password  
- [ ] Update or remove the default client token
- [ ] Set `APP_DEBUG=false`
- [ ] Verify all environment variables are set correctly
- [ ] Test order creation and processing workflow
- [ ] Set up proper email configuration (currently using `log` mailer)

## Support

For issues or questions, contact the development team.
