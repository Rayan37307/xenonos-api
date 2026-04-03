#!/bin/bash
# =============================================================================
# XenonOS Laravel API - Production Deployment Script
# =============================================================================
# This script automates the deployment of the XenonOS Laravel API to production.
# 
# Usage: ./deploy.sh [environment]
#   environment: production (default), staging, development
#
# Example: ./deploy.sh production
# =============================================================================

set -e

# =============================================================================
# CONFIGURATION
# =============================================================================

# Deployment environment (default: production)
ENVIRONMENT="${1:-production}"

# Application paths
APP_NAME="xenonos-api"
APP_DIR="/var/www/${APP_NAME}"
APP_USER="www-data"
APP_GROUP="www-data"

# PHP version
PHP_VERSION="8.3"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# =============================================================================
# HELPER FUNCTIONS
# =============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root or with sudo"
        exit 1
    fi
}

check_prerequisites() {
    log_info "Checking prerequisites..."
    
    local missing=()
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        missing+=("php${PHP_VERSION}")
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        missing+=("composer")
    fi
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        missing+=("nodejs")
    fi
    
    # Check Nginx
    if ! command -v nginx &> /dev/null; then
        missing+=("nginx")
    fi
    
    if [ ${#missing[@]} -ne 0 ]; then
        log_warning "Missing packages: ${missing[*]}"
        log_info "Install with: apt install ${missing[*]}"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        log_success "All prerequisites installed"
    fi
}

backup_current() {
    log_info "Creating backup of current deployment..."
    
    local BACKUP_DIR="/var/backups/${APP_NAME}"
    local BACKUP_NAME="${APP_NAME}-backup-$(date +%Y%m%d-%H%M%S)"
    
    mkdir -p "$BACKUP_DIR"
    
    if [ -d "$APP_DIR" ]; then
        cp -r "$APP_DIR" "$BACKUP_DIR/$BACKUP_NAME"
        log_success "Backup created: $BACKUP_DIR/$BACKUP_NAME"
    else
        log_warning "No existing deployment to backup"
    fi
}

# =============================================================================
# DEPLOYMENT STEPS
# =============================================================================

deploy_code() {
    log_info "Deploying application code..."
    
    # Create app directory if not exists
    if [ ! -d "$APP_DIR" ]; then
        mkdir -p "$APP_DIR"
        chown "$APP_USER:$APP_GROUP" "$APP_DIR"
        log_info "Created application directory: $APP_DIR"
    fi
    
    # If running from within the app directory, skip git clone
    if [ "$(pwd)" != "$APP_DIR" ]; then
        # Check if git repo exists
        if [ -d "$APP_DIR/.git" ]; then
            log_info "Updating existing repository..."
            cd "$APP_DIR"
            git fetch origin
            git checkout main
            git pull origin main
        else
            log_info "Cloning repository..."
            cd "$APP_DIR"
            # Replace with your actual repository URL
            # git clone https://github.com/your-org/xenonos-api.git .
            log_warning "Git repository not configured. Please clone manually."
        fi
    fi
}

install_dependencies() {
    log_info "Installing dependencies..."
    
    cd "$APP_DIR"
    
    # Install Composer dependencies (production only)
    log_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
    
    # Install NPM dependencies
    log_info "Installing NPM dependencies..."
    if [ -f "package.json" ]; then
        npm install --production --silent
    fi
    
    log_success "Dependencies installed"
}

setup_environment() {
    log_info "Setting up environment..."
    
    cd "$APP_DIR"
    
    # Create .env if not exists
    if [ ! -f ".env" ]; then
        log_info "Creating .env file from .env.example..."
        cp .env.example .env
        
        # Generate application key
        log_info "Generating application key..."
        php artisan key:generate
    else
        log_info ".env file already exists"
    fi
    
    # Create SQLite database if using SQLite
    if grep -q "DB_CONNECTION=sqlite" .env; then
        if [ ! -f "database/database.sqlite" ]; then
            log_info "Creating SQLite database..."
            touch database/database.sqlite
            chown "$APP_USER:$APP_GROUP" database/database.sqlite
            chmod 664 database/database.sqlite
        fi
    fi
}

run_migrations() {
    log_info "Running database migrations..."
    
    cd "$APP_DIR"
    
    # Run migrations with force flag (for production)
    php artisan migrate --force --no-interaction
    
    # Seed database if needed (uncomment if you have seeders)
    # php artisan db:seed --force --no-interaction
    
    log_success "Migrations completed"
}

build_assets() {
    log_info "Building frontend assets..."
    
    cd "$APP_DIR"
    
    if [ -f "package.json" ] && [ -f "vite.config.js" ]; then
        npm run build
        log_success "Assets built successfully"
    else
        log_warning "No Vite configuration found, skipping asset build"
    fi
}

optimize_laravel() {
    log_info "Optimizing Laravel application..."
    
    cd "$APP_DIR"
    
    # Clear existing caches
    php artisan optimize:clear
    
    # Create optimization caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    log_success "Laravel optimized"
}

set_permissions() {
    log_info "Setting file permissions..."
    
    # Set ownership
    chown -R "$APP_USER:$APP_GROUP" "$APP_DIR"
    
    # Set directory permissions
    find "$APP_DIR" -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find "$APP_DIR" -type f -exec chmod 644 {} \;
    
    # Set storage and cache permissions
    chmod -R 775 "$APP_DIR/storage"
    chmod -R 775 "$APP_DIR/bootstrap/cache"
    
    # Secure .env file
    chmod 640 "$APP_DIR/.env"
    chown root:"$APP_GROUP" "$APP_DIR/.env"
    
    log_success "Permissions set"
}

setup_workers() {
    log_info "Setting up queue workers..."
    
    # Check if Supervisor is installed
    if ! command -v supervisorctl &> /dev/null; then
        log_warning "Supervisor not installed. Skipping worker setup."
        log_info "Install with: apt install supervisor"
        return
    fi
    
    # Create Supervisor configuration
    cat > /etc/supervisor/conf.d/${APP_NAME}-worker.conf << EOF
[program:${APP_NAME}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${APP_USER}
numprocs=4
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF
    
    # Reload Supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start "${APP_NAME}-worker:*"
    
    log_success "Queue workers configured and started"
}

setup_scheduler() {
    log_info "Setting up scheduler..."
    
    # Check if cron entry exists
    if ! crontab -u "$APP_USER" -l 2>/dev/null | grep -q "schedule:run"; then
        # Add cron entry
        (crontab -u "$APP_USER" -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u "$APP_USER" -
        log_success "Scheduler configured"
    else
        log_info "Scheduler already configured"
    fi
}

reload_services() {
    log_info "Reloading services..."
    
    # Reload PHP-FPM
    if systemctl is-active --quiet "php${PHP_VERSION}-fpm"; then
        systemctl reload "php${PHP_VERSION}-fpm"
        log_info "PHP-FPM reloaded"
    fi
    
    # Reload Nginx
    if systemctl is-active --quiet "nginx"; then
        nginx -t && systemctl reload nginx
        log_info "Nginx reloaded"
    fi
    
    # Reload Apache
    if systemctl is-active --quiet "apache2"; then
        apachectl configtest && systemctl reload apache2
        log_info "Apache reloaded"
    fi
}

health_check() {
    log_info "Running health check..."
    
    cd "$APP_DIR"
    
    # Check application
    php artisan about --no-interaction
    
    # Check database connection
    php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null && log_success "Database connection OK" || log_error "Database connection failed"
    
    # Check cache
    php artisan tinker --execute="Cache::put('health_check', true, 60);" 2>/dev/null && log_success "Cache OK" || log_warning "Cache check failed"
    
    log_success "Health check completed"
}

# =============================================================================
# MAIN DEPLOYMENT
# =============================================================================

main() {
    echo "=============================================="
    echo "  XenonOS API - Production Deployment"
    echo "  Environment: $ENVIRONMENT"
    echo "=============================================="
    echo ""
    
    # Check if running as root
    check_root
    
    # Check prerequisites
    check_prerequisites
    
    # Create backup
    backup_current
    
    # Deploy code
    deploy_code
    
    # Install dependencies
    install_dependencies
    
    # Setup environment
    setup_environment
    
    # Run migrations
    run_migrations
    
    # Build assets
    build_assets
    
    # Optimize Laravel
    optimize_laravel
    
    # Set permissions
    set_permissions
    
    # Setup workers and scheduler
    setup_workers
    setup_scheduler
    
    # Reload services
    reload_services
    
    # Health check
    health_check
    
    echo ""
    echo "=============================================="
    log_success "Deployment completed successfully!"
    echo "=============================================="
    echo ""
    echo "Next steps:"
    echo "  1. Configure your web server (nginx/apache)"
    echo "  2. Set up SSL certificate"
    echo "  3. Update .env with production values"
    echo "  4. Test the API endpoints"
    echo ""
}

# Run main function
main "$@"
