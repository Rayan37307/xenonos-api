# Deployment Guide - XenonOS Laravel API

Complete guide for deploying the XenonOS Laravel API to production with proper caching, optimizations, and security configurations.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Environment Configuration](#environment-configuration)
4. [Deployment Steps](#deployment-steps)
5. [Configuration Caching](#configuration-caching)
6. [Queue Worker Setup](#queue-worker-setup)
7. [Scheduler Setup](#scheduler-setup)
8. [Web Server Configuration](#web-server-configuration)
9. [SSL/HTTPS Setup](#sslhttps-setup)
10. [Monitoring & Logging](#monitoring--logging)
11. [Security Hardening](#security-hardening)
12. [Performance Optimization](#performance-optimization)
13. [Rollback Procedures](#rollback-procedures)
14. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Knowledge

- Basic Linux server administration
- PHP and Laravel framework familiarity
- Database management (MySQL/PostgreSQL/SQLite)
- Web server configuration (Nginx/Apache)
- SSH and command-line operations

### Tools Needed

- SSH client for server access
- Git for version control
- Text editor for configuration files
- Database client (optional, for verification)

---

## Server Requirements

### Minimum Specifications

| Component | Requirement | Recommended |
|-----------|-------------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 4 GB | 8+ GB |
| Storage | 20 GB SSD | 40+ GB NVMe |
| PHP Version | 8.2+ | 8.3+ |

### Required PHP Extensions

```bash
# Essential extensions
php-bcmath
php-ctype
php-curl
php-dom
php-fileinfo
php-json
php-mbstring
php-openssl
php-pdo
php-tokenizer
php-xml

# Database extensions (choose based on your DB)
php-sqlite3    # For SQLite
php-mysql      # For MySQL/MariaDB
php-pgsql      # For PostgreSQL

# Recommended extensions
php-redis      # For Redis cache
php-gd         # For image processing
php-intl       # For internationalization
php-zip        # For file handling
```

### Required Services

- **Web Server:** Nginx (recommended) or Apache 2.4+
- **Database:** MySQL 8.0+, PostgreSQL 14+, or SQLite 3.35+
- **Cache:** Redis 6.0+ (recommended) or Memcached
- **Queue:** Redis (recommended) or database driver
- **SSL:** Let's Encrypt or commercial certificate

---

## Environment Configuration

### Production .env Settings

Create or update your `.env` file with production settings:

```env
# =============================================================================
# APPLICATION SETTINGS
# =============================================================================
APP_NAME="XenonOS"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_TIMEZONE=UTC

# =============================================================================
# LOGGING
# =============================================================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# =============================================================================
# DATABASE CONFIGURATION
# =============================================================================
# For SQLite (simple deployments)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/xenonos-api/database/database.sqlite

# For MySQL (recommended for production)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=xenonos_production
# DB_USERNAME=xenonos_user
# DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

# For PostgreSQL
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=xenonos_production
# DB_USERNAME=xenonos_user
# DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

# =============================================================================
# CACHE CONFIGURATION
# =============================================================================
# Use Redis for production (recommended)
CACHE_STORE=redis
CACHE_PREFIX=xenonos_

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Alternative: Use database cache (simpler but slower)
# CACHE_STORE=database

# =============================================================================
# SESSION CONFIGURATION
# =============================================================================
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.yourdomain.com
SESSION_SECURE_COOKIE=true

# =============================================================================
# QUEUE CONFIGURATION
# =============================================================================
QUEUE_CONNECTION=redis
QUEUE_PREFIX=xenonos_

# =============================================================================
# FILESYSTEM CONFIGURATION
# =============================================================================
FILESYSTEM_DISK=s3
# For local storage (development/small deployments)
# FILESYSTEM_DISK=local

# =============================================================================
# MAIL CONFIGURATION
# =============================================================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourmailprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=YOUR_MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# =============================================================================
# BROADCASTING (WebSockets)
# =============================================================================
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="api.yourdomain.com"
REVERB_PORT=443
REVERB_SCHEME=https

# =============================================================================
# SANCTUM API AUTHENTICATION
# =============================================================================
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com,app.yourdomain.com

# =============================================================================
# AWS S3 (for file storage)
# =============================================================================
AWS_ACCESS_KEY_ID=YOUR_AWS_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_AWS_SECRET_KEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false

# =============================================================================
# MISCELLANEOUS
# =============================================================================
BCRYPT_ROUNDS=12
MAINTENANCE_DRIVER=file
VITE_APP_NAME="${APP_NAME}"
```

### Generate Application Key

```bash
# Generate a new application key (do this once)
php artisan key:generate

# Or specify the key directly
# APP_KEY=base64:$(openssl rand -base64 32)
```

### Environment-Specific Configurations

Create environment-specific config files if needed:

```bash
# Copy production config
cp .env.example .env.production
```

---

## Deployment Steps

### Step 1: Prepare the Server

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-intl php8.3-redis \
    php8.3-sqlite3 php8.3-mysql nginx git unzip curl redis-server

# For MySQL
sudo apt install -y mysql-server

# Or for PostgreSQL
# sudo apt install -y postgresql postgresql-contrib
```

### Step 2: Clone the Repository

```bash
# Create application directory
sudo mkdir -p /var/www/xenonos-api
sudo chown -R $USER:$USER /var/www/xenonos-api

# Navigate to directory
cd /var/www/xenonos-api

# Clone repository
git clone https://github.com/your-org/xenonos-api.git .

# Or copy existing code
# rsync -avz /local/path/xenonos-api/ /var/www/xenonos-api/
```

### Step 3: Install Dependencies

```bash
# Install Composer dependencies (production only)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Install NPM dependencies (if using Vite assets)
npm install --production
```

### Step 4: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit environment file with production settings
nano .env

# Generate application key
php artisan key:generate

# Create SQLite database (if using SQLite)
touch database/database.sqlite
chmod 664 database/database.sqlite
```

### Step 5: Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Seed database (optional, for initial data)
php artisan db:seed --force

# Or run migrations with seeding
php artisan migrate --seed --force
```

### Step 6: Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/xenonos-api

# Set directory permissions
sudo find /var/www/xenonos-api -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/xenonos-api -type f -exec chmod 644 {} \;

# Set storage and bootstrap/cache permissions
sudo chmod -R 775 /var/www/xenonos-api/storage
sudo chmod -R 775 /var/www/xenonos-api/bootstrap/cache

# Set ownership for storage and cache
sudo chown -R www-data:www-data /var/www/xenonos-api/storage
sudo chown -R www-data:www-data /var/www/xenonos-api/bootstrap/cache
```

### Step 7: Build Frontend Assets

```bash
# Build Vite assets for production
npm run build

# Or with pnpm
# pnpm build
```

---

## Configuration Caching

### Cache Configuration Files

Laravel provides several caching commands to optimize performance:

```bash
# Cache all configuration files into a single file
php artisan config:cache

# Cache all routes into a single file
php artisan route:cache

# Cache all views
php artisan view:cache

# Cache events and listeners
php artisan event:cache

# Run all optimizations at once
php artisan optimize

# Clear all caches (for deployments)
php artisan optimize:clear
```

### Cache Optimization Script

Create a deployment optimization script:

```bash
#!/bin/bash
# optimize.sh

set -e

echo "🚀 Optimizing Laravel application..."

# Clear existing caches
php artisan optimize:clear

# Install dependencies
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Run migrations
php artisan migrate --force

# Build assets
npm run build

# Create caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ Optimization complete!"
```

### OPcache Configuration

Configure PHP OPcache for optimal performance:

```ini
; /etc/php/8.3/fpm/conf.d/10-opcache.ini

[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_file_override=1
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.jit=1255
opcache.jit_buffer_size=128M
```

**Important:** Set `opcache.validate_timestamps=0` in production to prevent file stat checks on every request.

### Autoload Optimization

Composer autoload optimization:

```bash
# Optimize autoloader (included in --optimize-autoloader flag)
composer dump-autoload --classmap-authoritative --no-dev

# This converts PSR-4 autoloading to classmap for better performance
```

---

## Queue Worker Setup

### Using Supervisor (Recommended)

Supervisor keeps queue workers running continuously:

```bash
# Install Supervisor
sudo apt install -y supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/xenonos-worker.conf
```

**Supervisor Configuration:**

```ini
[program:xenonos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/xenonos-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/xenonos-api/storage/logs/worker.log
stopwaitsecs=3600
```

**Manage Workers:**

```bash
# Reload Supervisor configuration
sudo supervisorctl reread

# Apply changes
sudo supervisorctl update

# Start all workers
sudo supervisorctl start xenonos-worker:*

# Check worker status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart xenonos-worker:*

# Stop workers
sudo supervisorctl stop xenonos-worker:*
```

### Using Systemd (Alternative)

```ini
# /etc/systemd/system/xenonos-worker.service

[Unit]
Description=XenonOS Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/xenonos-api
ExecStart=/usr/bin/php /var/www/xenonos-api/artisan queue:work redis --sleep=3 --tries=3
Restart=always
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable xenonos-worker
sudo systemctl start xenonos-worker

# Check status
sudo systemctl status xenonos-worker
```

### Queue Worker Best Practices

1. **Run multiple workers** for high-traffic applications
2. **Set appropriate timeouts** based on job duration
3. **Monitor worker logs** for failures
4. **Use horizon** for advanced queue management (optional)

---

## Scheduler Setup

### Configure Cron Job

Laravel's scheduler runs all scheduled tasks from a single cron entry:

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e

# Add the following line
* * * * * cd /var/www/xenonos-api && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduler

```bash
# List scheduled tasks
php artisan schedule:list

# Run scheduler manually (for testing)
php artisan schedule:run

# Run a specific scheduled task
php artisan schedule:run --test
```

### Common Scheduled Tasks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Clean up old sessions
    $schedule->command('session:flush')->daily();

    // Clean up old cache entries
    $schedule->command('cache:prune-stale-tags')->hourly();

    // Process queued notifications
    $schedule->command('notifications:table')->daily();

    // Custom tasks
    $schedule->call(function () {
        // Your custom logic
    })->dailyAt('02:00');
}
```

---

## Web Server Configuration

### Nginx Configuration (Recommended)

Create Nginx configuration:

```nginx
# /etc/nginx/sites-available/xenonos-api

server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    root /var/www/xenonos-api/public;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Hide Nginx version
    server_tokens off;

    # Logging
    access_log /var/log/nginx/xenonos-api-access.log;
    error_log /var/log/nginx/xenonos-api-error.log;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml application/javascript 
               application/json;

    # PHP-FPM configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM socket configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
        
        # Buffer sizes
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~* /(?:\.env|\.git|composer\.(json|lock)|package\.(json|lock)|phpunit\.xml|\.idea|\.vscode) {
        deny all;
    }

    # Deny access to storage directory
    location ~* /storage/ {
        deny all;
        return 404;
    }

    # Deny access to vendor directory
    location ~* /vendor/ {
        deny all;
        return 404;
    }

    # Handle Laravel Mix/Vite assets
    location ~* ^/(css|js|images)/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # API rate limiting (optional)
    # limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    # location /api/ {
    #     limit_req zone=api burst=20 nodelay;
    #     try_files $uri $uri/ /index.php?$query_string;
    # }
}
```

**Enable Site:**

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/xenonos-api /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Apache Configuration (Alternative)

```apache
# /etc/apache2/sites-available/xenonos-api.conf

<VirtualHost *:80>
    ServerName api.yourdomain.com
    DocumentRoot /var/www/xenonos-api/public

    <Directory /var/www/xenonos-api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable mod_rewrite
        RewriteEngine On
        
        # Security headers
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </Directory>

    # PHP-FPM configuration
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/xenonos-api-error.log
    CustomLog ${APACHE_LOG_DIR}/xenonos-api-access.log combined

    # Hide server version
    ServerTokens Prod
    ServerSignature Off
</VirtualHost>
```

```bash
# Enable required modules
sudo a2enmod rewrite headers proxy proxy_fcgi

# Enable site
sudo a2ensite xenonos-api

# Disable default site
sudo a2dissite 000-default

# Test and reload
sudo apachectl configtest
sudo systemctl reload apache2
```

---

## SSL/HTTPS Setup

### Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d api.yourdomain.com

# For Apache
# sudo certbot --apache -d api.yourdomain.com

# For standalone (if no web server integration)
# sudo certbot certonly --standalone -d api.yourdomain.com
```

### Auto-Renewal

Certbot sets up automatic renewal, but verify:

```bash
# Test renewal
sudo certbot renew --dry-run

# Check renewal timer
sudo systemctl list-timers certbot.timer
```

### Manual SSL Configuration

If using a commercial certificate:

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_stapling on;
    ssl_stapling_verify on;
    
    # ... rest of configuration
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## Monitoring & Logging

### Laravel Logging Configuration

```env
# .env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error
```

### Log Rotation

```bash
# /etc/logrotate.d/xenonos-api

/var/www/xenonos-api/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php8.3-fpm > /dev/null 2>&1 || true
    endscript
}
```

### Health Check Endpoint

Create a health check route in `routes/api.php`:

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

Route::get('/health', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'redis' => false,
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        // Database check failed
    }

    try {
        Cache::put('health_check', true, 1);
        $checks['cache'] = Cache::get('health_check') === true;
    } catch (\Exception $e) {
        // Cache check failed
    }

    try {
        Redis::ping();
        $checks['redis'] = true;
    } catch (\Exception $e) {
        // Redis check failed
    }

    $healthy = in_array(false, $checks, true) === false;

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

### Monitoring Tools

1. **Laravel Telescope** (development/staging only)
2. **Laravel Horizon** (for Redis queues)
3. **Sentry** (error tracking)
4. **New Relic** (APM)
5. **Datadog** (monitoring)

---

## Security Hardening

### File Permissions

```bash
# Ensure proper ownership
sudo chown -R www-data:www-data /var/www/xenonos-api

# Lock down .env file
chmod 640 /var/www/xenonos-api/.env
sudo chown root:www-data /var/www/xenonos-api/.env

# Secure storage directory
chmod -R 775 /var/www/xenonos-api/storage
```

### Disable Directory Listing

Already included in Nginx/Apache configs above.

### Secure Headers

Add to Nginx config:

```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:; frame-ancestors 'self';" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

### Rate Limiting

```nginx
# Add to http block in /etc/nginx/nginx.conf
http {
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=60r/m;
    limit_conn_zone $binary_remote_addr zone=conn_limit:10m;
    
    # ... rest of config
}

# Apply to server block
location /api/ {
    limit_req zone=api_limit burst=20 nodelay;
    limit_conn conn_limit 10;
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Firewall Configuration

```bash
# UFW (Ubuntu Firewall)
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable

# Or with iptables
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

---

## Performance Optimization

### Database Optimization

```bash
# Optimize SQLite
sqlite3 database/database.sqlite "VACUUM; ANALYZE;"

# For MySQL
mysql -u root -p -e "OPTIMIZE TABLE xenonos_production.*"
```

### Cache Warm-up Script

```bash
#!/bin/bash
# warmup-cache.sh

API_URL="https://api.yourdomain.com"

# Warm up common endpoints
curl -s "${API_URL}/api/auth/me" > /dev/null
curl -s "${API_URL}/api/projects" > /dev/null
curl -s "${API_URL}/api/tasks" > /dev/null

echo "Cache warmed up!"
```

### PHP-FPM Optimization

```ini
; /etc/php/8.3/fpm/pool.d/www.conf

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Slow log for debugging
request_slowlog_timeout = 5s
slowlog = /var/log/php-fpm/slow.log
```

---

## Rollback Procedures

### Manual Rollback

```bash
# Navigate to application
cd /var/www/xenonos-api

# Create backup of current version
sudo cp -r /var/www/xenonos-api /var/www/xenonos-api-backup-$(date +%Y%m%d-%H%M%S)

# Revert code changes
git checkout <previous-commit-hash>

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Rollback migrations (if needed)
php artisan migrate:rollback --step=5

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Restart workers
sudo supervisorctl restart xenonos-worker:*

# Reload PHP-FPM
sudo systemctl reload php8.3-fpm
```

### Automated Rollback Script

```bash
#!/bin/bash
# rollback.sh

set -e

BACKUP_DIR="/var/backups/xenonos-api"
CURRENT_DIR="/var/www/xenonos-api"

# Find latest backup
LATEST_BACKUP=$(ls -t $BACKUP_DIR | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "No backup found!"
    exit 1
fi

echo "Rolling back to: $LATEST_BACKUP"

# Stop workers
sudo supervisorctl stop xenonos-worker:*

# Restore backup
sudo rsync -av --delete $BACKUP_DIR/$LATEST_BACKUP/ $CURRENT_DIR/

# Set permissions
sudo chown -R www-data:www-data $CURRENT_DIR
sudo chmod -R 755 $CURRENT_DIR
sudo chmod -R 775 $CURRENT_DIR/storage
sudo chmod -R 775 $CURRENT_DIR/bootstrap/cache

# Restart services
sudo supervisorctl start xenonos-worker:*
sudo systemctl reload php8.3-fpm

echo "Rollback complete!"
```

---

## Troubleshooting

### Common Issues

#### 1. Permission Denied Errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/xenonos-api/storage
sudo chmod -R 775 /var/www/xenonos-api/storage

# Fix bootstrap/cache permissions
sudo chown -R www-data:www-data /var/www/xenonos-api/bootstrap/cache
sudo chmod -R 775 /var/www/xenonos-api/bootstrap/cache
```

#### 2. 500 Internal Server Error

```bash
# Check logs
tail -f /var/www/xenonos-api/storage/logs/laravel.log
tail -f /var/log/nginx/xenonos-api-error.log

# Clear caches
php artisan optimize:clear

# Check .env configuration
php artisan env
```

#### 3. Queue Workers Not Processing

```bash
# Check worker status
sudo supervisorctl status xenonos-worker:*

# Restart workers
sudo supervisorctl restart xenonos-worker:*

# Check queue connection
php artisan queue:monitor redis

# Test queue
php artisan queue:work --once
```

#### 4. Database Connection Failed

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check .env settings
cat .env | grep DB_

# For SQLite, ensure file exists and is writable
ls -la database/database.sqlite
```

#### 5. Assets Not Loading

```bash
# Rebuild assets
npm run build

# Check public/build directory
ls -la public/build

# Clear view cache
php artisan view:clear
```

### Debug Mode (Temporary)

```env
# Enable debug temporarily (disable after debugging!)
APP_DEBUG=true
LOG_LEVEL=debug
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Backup current production data
- [ ] Test deployment on staging environment
- [ ] Review environment variables
- [ ] Verify database migrations
- [ ] Test rollback procedure

### During Deployment

- [ ] Put application in maintenance mode
- [ ] Pull latest code
- [ ] Install dependencies
- [ ] Run migrations
- [ ] Clear and rebuild caches
- [ ] Build frontend assets
- [ ] Set file permissions
- [ ] Restart queue workers
- [ ] Reload PHP-FPM

### Post-Deployment

- [ ] Remove maintenance mode
- [ ] Verify health check endpoint
- [ ] Test critical API endpoints
- [ ] Check error logs
- [ ] Monitor queue processing
- [ ] Verify WebSocket connections
- [ ] Test file uploads
- [ ] Confirm email sending

---

## Support

For deployment issues or questions, contact the Xenon Studios team.

**Contributors:**
- Tasin - Xenon Studios
- Munthasir - Xenon Studios

---

**Last Updated:** March 31, 2026
**Version:** 1.0.0
