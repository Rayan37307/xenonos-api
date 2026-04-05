# Deployment Checklist - XenonOS API

Use this checklist to ensure nothing is missed during deployment.

---

## Pre-Deployment

### Code & Repository
- [ ] All tests passing locally (`vendor/bin/pest`)
- [ ] Code style checks pass (`vendor/bin/pint --test`)
- [ ] No debug code left in codebase
- [ ] `APP_DEBUG` set to `false` in `.env`
- [ ] `APP_ENV` set to `production` in `.env`
- [ ] No hardcoded credentials or secrets in code
- [ ] Git branch is up to date and ready to deploy

### Server Prerequisites
- [ ] PHP 8.2+ installed with required extensions
- [ ] Composer installed
- [ ] Node.js and npm installed
- [ ] Nginx or Apache installed and configured
- [ ] Redis installed and running
- [ ] Database server running (MySQL/PostgreSQL/SQLite)
- [ ] Supervisor installed (for queue workers)
- [ ] SSL certificate obtained

### Environment Configuration
- [ ] `.env` file created from `.env.production.example`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_URL` set to production URL
- [ ] Database credentials configured
- [ ] Redis credentials configured
- [ ] Mail credentials configured
- [ ] AWS S3 credentials configured (if using)
- [ ] Sanctum stateful domains configured
- [ ] Reverb credentials configured (if using WebSockets)

---

## Deployment Steps

### 1. Prepare Server
```bash
sudo apt update && sudo apt upgrade -y
```
- [ ] System packages updated
- [ ] Required packages installed

### 2. Deploy Code
- [ ] Code uploaded/cloned to server
- [ ] Correct branch/tag checked out

### 3. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
npm install --production
```
- [ ] Composer dependencies installed
- [ ] NPM dependencies installed

### 4. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite  # if using SQLite
```
- [ ] `.env` file configured
- [ ] Application key generated
- [ ] Database file created (if SQLite)

### 5. Run Migrations
```bash
php artisan migrate --force
```
- [ ] Migrations completed successfully
- [ ] Seeders run (if applicable)

### 6. Build Frontend Assets
```bash
npm run build
```
- [ ] Assets built successfully
- [ ] `public/build` directory populated

### 7. Run Optimization Script
```bash
./scripts/optimize.sh
```
- [ ] All caches cleared
- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Events cached
- [ ] Autoloader optimized

### 8. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/xenonos-api
sudo find /var/www/xenonos-api -type d -exec chmod 755 {} \;
sudo find /var/www/xenonos-api -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/xenonos-api/storage
sudo chmod -R 775 /var/www/xenonos-api/bootstrap/cache
chmod 640 /var/www/xenonos-api/.env
```
- [ ] File ownership set correctly
- [ ] Directory permissions set
- [ ] `.env` file secured (640)

### 9. Setup Queue Workers
```bash
sudo cp supervisor.conf /etc/supervisor/conf.d/xenonos-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start xenonos-worker:*
```
- [ ] Supervisor configuration installed
- [ ] Workers started and running

### 10. Setup Scheduler
```bash
sudo crontab -u www-data -e
# Add: * * * * * cd /var/www/xenonos-api && php artisan schedule:run >> /dev/null 2>&1
```
- [ ] Cron entry added

### 11. Configure Web Server
```bash
sudo cp nginx.conf /etc/nginx/sites-available/xenonos-api
sudo ln -s /etc/nginx/sites-available/xenonos-api /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```
- [ ] Nginx configuration installed
- [ ] Nginx configuration tested
- [ ] Nginx reloaded

### 12. Setup SSL
```bash
sudo certbot --nginx -d api.yourdomain.com
```
- [ ] SSL certificate installed
- [ ] HTTPS redirect working

### 13. Restart Services
```bash
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart xenonos-worker:*
sudo supervisorctl restart xenonos-reverb
```
- [ ] PHP-FPM reloaded
- [ ] Queue workers restarted
- [ ] Reverb restarted

---

## Post-Deployment Verification

### Run Verification Script
```bash
./scripts/verify-deploy.sh --full
```
- [ ] All checks passed
- [ ] Warnings reviewed and addressed

### Manual Checks
- [ ] Health endpoint returns 200 (`curl https://api.yourdomain.com/health`)
- [ ] API authentication works
- [ ] Database queries executing
- [ ] Queue jobs processing
- [ ] WebSocket connections working (if applicable)
- [ ] File uploads working (if applicable)
- [ ] Email sending working (if applicable)

### Monitoring
- [ ] Error logs checked (`storage/logs/laravel.log`)
- [ ] Web server error logs checked
- [ ] Queue worker logs checked
- [ ] No unusual errors or warnings

---

## Rollback Plan

If deployment fails:

```bash
# 1. Put in maintenance mode
php artisan down

# 2. Restore backup
cd /var/backups/xenonos-api
# Restore from latest backup

# 3. Restore caches
php artisan optimize:clear
php artisan optimize

# 4. Bring back online
php artisan up
```

- [ ] Backup location verified before deployment
- [ ] Rollback procedure tested
- [ ] Team notified of rollback

---

## Post-Deployment Tasks

- [ ] Monitor error rates for 30 minutes
- [ ] Check queue processing metrics
- [ ] Verify API response times
- [ ] Update deployment log/changelog
- [ ] Notify team of successful deployment
- [ ] Schedule follow-up review if needed

---

**Deployed By:** ________________  
**Date:** ________________  
**Version/Tag:** ________________  
**Notes:** ________________
