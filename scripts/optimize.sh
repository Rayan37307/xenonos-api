#!/bin/bash
# =============================================================================
# XenonOS Laravel API - Deployment Optimization Script
# =============================================================================
# This script handles all Laravel caching and optimization steps for
# production deployment. Run this AFTER code deployment and migrations.
#
# Usage: ./scripts/optimize.sh [--no-cache] [--verbose]
# =============================================================================

set -e

# =============================================================================
# CONFIGURATION
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
VERBOSE=false
SKIP_CACHE=false

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

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

log_step() {
    echo -e "\n${CYAN}━━━ $1 ━━━${NC}"
}

run_cmd() {
    local cmd="$1"
    local description="$2"
    local silent="${3:-true}"

    log_info "Running: $description"

    if [ "$VERBOSE" = true ]; then
        cd "$APP_DIR" && eval "$cmd"
    elif [ "$silent" = true ]; then
        cd "$APP_DIR" && eval "$cmd" > /dev/null 2>&1
    else
        cd "$APP_DIR" && eval "$cmd"
    fi

    if [ $? -eq 0 ]; then
        log_success "$description completed"
    else
        log_error "$description failed"
        return 1
    fi
}

# =============================================================================
# PARSE ARGUMENTS
# =============================================================================

for arg in "$@"; do
    case $arg in
        --no-cache)
            SKIP_CACHE=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: ./scripts/optimize.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --no-cache    Skip cache generation (clear only)"
            echo "  --verbose, -v Show detailed output"
            echo "  --help, -h    Show this help message"
            exit 0
            ;;
    esac
done

# =============================================================================
# PRE-FLIGHT CHECKS
# =============================================================================

preflight_checks() {
    log_step "Pre-flight Checks"

    # Check if we're in the right directory
    if [ ! -f "$APP_DIR/artisan" ]; then
        log_error "Laravel artisan not found. Are you in the right directory?"
        exit 1
    fi

    # Check if .env exists
    if [ ! -f "$APP_DIR/.env" ]; then
        log_warning ".env file not found. Copying from .env.example..."
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        php "$APP_DIR/artisan" key:generate --no-interaction
    fi

    # Check PHP version
    local php_version=$(php -r "echo PHP_VERSION;")
    log_info "PHP Version: $php_version"

    # Check if APP_DEBUG is false (production check)
    local app_debug=$(grep "^APP_DEBUG=" "$APP_DIR/.env" 2>/dev/null | cut -d'=' -f2)
    if [ "$app_debug" = "true" ]; then
        log_warning "APP_DEBUG is set to true. This is not recommended for production."
    fi

    # Check if laravel/boost is in dont-discover (prevents prod deployment errors)
    if grep -q '"dont-discover"' "$APP_DIR/composer.json" 2>/dev/null; then
        if ! grep -q '"laravel/boost"' "$APP_DIR/composer.json" 2>/dev/null; then
            log_warning "laravel/boost not in dont-discover list. Add it to composer.json to prevent prod errors."
        fi
    fi

    log_success "Pre-flight checks passed"
}

# =============================================================================
# CLEAR ALL CACHES
# =============================================================================

clear_caches() {
    log_step "Clearing All Caches"

    run_cmd "php artisan optimize:clear" "Clearing all caches" false

    # Clear individual caches for safety
    run_cmd "php artisan config:clear" "Clearing config cache"
    run_cmd "php artisan route:clear" "Clearing route cache"
    run_cmd "php artisan view:clear" "Clearing view cache"
    run_cmd "php artisan event:clear" "Clearing event cache"
    run_cmd "php artisan cache:clear" "Clearing application cache"

    # Clear compiled views
    if [ -d "$APP_DIR/storage/framework/views" ]; then
        rm -rf "$APP_DIR/storage/framework/views"/*
        log_success "Compiled views removed"
    fi

    # Clear bootstrap cache files
    rm -f "$APP_DIR/bootstrap/cache/config.php"
    rm -f "$APP_DIR/bootstrap/cache/routes-v7.php"
    rm -f "$APP_DIR/bootstrap/cache/events.php"
    rm -f "$APP_DIR/bootstrap/cache/packages.php"
    rm -f "$APP_DIR/bootstrap/cache/services.php"

    log_success "All caches cleared"
}

# =============================================================================
# OPTIMIZE AUTOLOADER
# =============================================================================

optimize_autoloader() {
    log_step "Optimizing Autoloader"

    run_cmd "composer dump-autoload --optimize --classmap-authoritative --no-dev" \
            "Dumping optimized autoload" false

    log_success "Autoloader optimized"
}

# =============================================================================
# BUILD CONFIGURATION CACHE
# =============================================================================

cache_config() {
    log_step "Caching Configuration"

    run_cmd "php artisan config:cache" "Caching configuration" false

    # Verify config cache was created
    if [ -f "$APP_DIR/bootstrap/cache/config.php" ]; then
        local cache_size=$(du -h "$APP_DIR/bootstrap/cache/config.php" | cut -f1)
        log_success "Config cache created ($cache_size)"
    else
        log_warning "Config cache file not found"
    fi
}

# =============================================================================
# BUILD ROUTE CACHE
# =============================================================================

cache_routes() {
    log_step "Caching Routes"

    run_cmd "php artisan route:cache" "Caching routes" false

    # Verify route cache was created
    if [ -f "$APP_DIR/bootstrap/cache/routes-v7.php" ]; then
        local cache_size=$(du -h "$APP_DIR/bootstrap/cache/routes-v7.php" | cut -f1)
        log_success "Route cache created ($cache_size)"
    else
        log_warning "Route cache file not found"
    fi
}

# =============================================================================
# BUILD VIEW CACHE
# =============================================================================

cache_views() {
    log_step "Caching Views"

    run_cmd "php artisan view:cache" "Caching views" false

    # Verify views are compiled
    local view_count=$(find "$APP_DIR/storage/framework/views" -name "*.php" 2>/dev/null | wc -l)
    log_success "Views cached ($view_count compiled files)"
}

# =============================================================================
# BUILD EVENT CACHE
# =============================================================================

cache_events() {
    log_step "Caching Events"

    run_cmd "php artisan event:cache" "Caching events and listeners"

    # Verify event cache was created
    if [ -f "$APP_DIR/bootstrap/cache/events.php" ]; then
        local cache_size=$(du -h "$APP_DIR/bootstrap/cache/events.php" | cut -f1)
        log_success "Event cache created ($cache_size)"
    else
        log_warning "Event cache file not found (may not have events)"
    fi
}

# =============================================================================
# VERIFY DEPLOYMENT
# =============================================================================

verify_deployment() {
    log_step "Verifying Deployment"

    # Check that caches exist
    local caches_ok=true

    if [ "$SKIP_CACHE" = false ]; then
        local cache_files=("config.php" "routes-v7.php")
        for file in "${cache_files[@]}"; do
            if [ ! -f "$APP_DIR/bootstrap/cache/$file" ]; then
                log_error "Cache file missing: $file"
                caches_ok=false
            fi
        done
    fi

    # Test database connection
    log_info "Testing database connection..."
    if php "$APP_DIR/artisan" tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
        log_success "Database connection OK"
    else
        log_error "Database connection failed"
        caches_ok=false
    fi

    # Test cache store
    log_info "Testing cache store..."
    if php "$APP_DIR/artisan" tinker --execute="Cache::put('deploy_test', 'ok', 60); echo Cache::get('deploy_test');" 2>/dev/null | grep -q "ok"; then
        log_success "Cache store OK"
    else
        log_warning "Cache store test failed (may be expected if using file cache)"
    fi

    if [ "$caches_ok" = true ]; then
        log_success "All verification checks passed"
    else
        log_error "Some verification checks failed. Review output above."
        exit 1
    fi
}

# =============================================================================
# MAIN EXECUTION
# =============================================================================

main() {
    echo "=============================================="
    echo "  XenonOS API - Optimization Script"
    echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "  Skip Cache: $SKIP_CACHE"
    echo "=============================================="
    echo ""

    # Pre-flight checks
    preflight_checks

    # Clear all caches first
    clear_caches

    # Optimize autoloader
    optimize_autoloader

    if [ "$SKIP_CACHE" = false ]; then
        # Build caches
        cache_config
        cache_routes
        cache_views
        cache_events
    fi

    # Verify deployment
    verify_deployment

    echo ""
    echo "=============================================="
    log_success "Optimization completed successfully!"
    echo "=============================================="
    echo ""

    if [ "$SKIP_CACHE" = false ]; then
        echo "Cache files created:"
        echo "  - bootstrap/cache/config.php"
        echo "  - bootstrap/cache/routes-v7.php"
        echo "  - bootstrap/cache/events.php"
        echo ""
        echo "To clear caches, run:"
        echo "  php artisan optimize:clear"
        echo ""
    fi

    echo "Important reminders:"
    echo "  - Set APP_DEBUG=false in production"
    echo "  - Set APP_ENV=production"
    echo "  - Ensure storage/ and bootstrap/cache/ are writable"
    echo "  - Restart queue workers after deployment"
    echo "  - Restart Reverb server after deployment"
    echo ""
}

# Run main function
main "$@"
