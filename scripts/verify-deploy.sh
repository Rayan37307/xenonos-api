#!/bin/bash
# =============================================================================
# XenonOS Laravel API - Post-Deployment Verification Script
# =============================================================================
# Run this script after deployment to verify everything is working correctly.
#
# Usage: ./scripts/verify-deploy.sh [--full]
# =============================================================================

set -e

# =============================================================================
# CONFIGURATION
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
FULL_CHECK=false
EXIT_CODE=0

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Results tracking
PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

# =============================================================================
# HELPER FUNCTIONS
# =============================================================================

pass() {
    echo -e "  ${GREEN}✓ PASS${NC} $1"
    ((PASS_COUNT++))
}

fail() {
    echo -e "  ${RED}✗ FAIL${NC} $1"
    ((FAIL_COUNT++))
    EXIT_CODE=1
}

warn() {
    echo -e "  ${YELLOW}⚠ WARN${NC} $1"
    ((WARN_COUNT++))
}

section() {
    echo -e "\n${CYAN}━━━ $1 ━━━${NC}"
}

run_test() {
    local description="$1"
    local command="$2"

    echo -n "  Testing: $description... "

    if eval "$command" > /dev/null 2>&1; then
        echo -e "${GREEN}OK${NC}"
        ((PASS_COUNT++))
    else
        echo -e "${RED}FAILED${NC}"
        ((FAIL_COUNT++))
        EXIT_CODE=1
    fi
}

# =============================================================================
# PARSE ARGUMENTS
# =============================================================================

for arg in "$@"; do
    case $arg in
        --full)
            FULL_CHECK=true
            shift
            ;;
    esac
done

# =============================================================================
# VERIFICATION CHECKS
# =============================================================================

echo "=============================================="
echo "  XenonOS API - Deployment Verification"
echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Full Check: $FULL_CHECK"
echo "=============================================="

# --------------------------------------------------------------------------
# 1. FILE SYSTEM CHECKS
# --------------------------------------------------------------------------
section "File System Checks"

# Check artisan exists
if [ -f "$APP_DIR/artisan" ]; then
    pass "Artisan file exists"
else
    fail "Artisan file missing"
fi

# Check .env exists
if [ -f "$APP_DIR/.env" ]; then
    pass ".env file exists"
else
    fail ".env file missing"
fi

# Check .env is not world-readable
if [ -f "$APP_DIR/.env" ]; then
    env_perms=$(stat -c "%a" "$APP_DIR/.env" 2>/dev/null || stat -f "%Lp" "$APP_DIR/.env" 2>/dev/null)
    if [ "$env_perms" = "640" ] || [ "$env_perms" = "600" ]; then
        pass ".env permissions are secure ($env_perms)"
    else
        warn ".env permissions are not optimal ($env_perms), should be 640"
    fi
fi

# Check storage directory is writable
if [ -w "$APP_DIR/storage" ]; then
    pass "Storage directory is writable"
else
    fail "Storage directory is not writable"
fi

# Check bootstrap/cache is writable
if [ -w "$APP_DIR/bootstrap/cache" ]; then
    pass "Bootstrap/cache directory is writable"
else
    fail "Bootstrap/cache directory is not writable"
fi

# Check vendor directory exists
if [ -d "$APP_DIR/vendor" ]; then
    pass "Vendor directory exists"
else
    fail "Vendor directory missing - run composer install"
fi

# Check for .git directory (should not exist in production)
if [ -d "$APP_DIR/.git" ]; then
    if [ "$FULL_CHECK" = true ]; then
        warn ".git directory exists (consider removing in production)"
    fi
else
    pass ".git directory not present (good for production)"
fi

# --------------------------------------------------------------------------
# 2. PHP & LARAVEL CHECKS
# --------------------------------------------------------------------------
section "PHP & Laravel Checks"

# Check PHP version
php_version=$(php -r "echo PHP_VERSION;")
php_major=$(php -r "echo PHP_MAJOR_VERSION;")
php_minor=$(php -r "echo PHP_MINOR_VERSION;")

if [ "$php_major" -ge 8 ] && [ "$php_minor" -ge 2 ]; then
    pass "PHP version: $php_version (>= 8.2)"
else
    fail "PHP version: $php_version (requires >= 8.2)"
fi

# Check required PHP extensions
required_extensions=("bcmath" "ctype" "curl" "dom" "fileinfo" "json" "mbstring" "openssl" "pdo" "tokenizer" "xml")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if ! php -m 2>/dev/null | grep -qi "$ext"; then
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -eq 0 ]; then
    pass "All required PHP extensions loaded"
else
    fail "Missing PHP extensions: ${missing_extensions[*]}"
fi

# Check Redis extension (recommended)
if php -m 2>/dev/null | grep -qi "redis"; then
    pass "PHP Redis extension loaded"
else
    warn "PHP Redis extension not loaded (recommended for production)"
fi

# Test artisan commands
cd "$APP_DIR"

run_test "Artisan is functional" "php artisan --version"

# --------------------------------------------------------------------------
# 3. ENVIRONMENT CHECKS
# --------------------------------------------------------------------------
section "Environment Checks"

# Check APP_ENV
app_env=$(php artisan env --env=production 2>/dev/null | grep "APP_ENV" | cut -d'=' -f2 || echo "unknown")
if [ "$app_env" = "production" ]; then
    pass "APP_ENV is set to production"
else
    warn "APP_ENV is '$app_env' (should be 'production')"
fi

# Check APP_DEBUG
app_debug=$(grep "^APP_DEBUG=" "$APP_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "unknown")
if [ "$app_debug" = "false" ]; then
    pass "APP_DEBUG is false (correct for production)"
elif [ "$app_debug" = "true" ]; then
    fail "APP_DEBUG is true (should be false in production)"
else
    warn "APP_DEBUG value unclear: $app_debug"
fi

# Check APP_KEY
if php artisan tinker --execute="echo config('app.key');" 2>/dev/null | grep -q "base64:"; then
    pass "APP_KEY is configured"
else
    fail "APP_KEY is not configured - run 'php artisan key:generate'"
fi

# Check APP_URL
app_url=$(grep "^APP_URL=" "$APP_DIR/.env" 2>/dev/null | cut -d'=' -f2 || echo "")
if [ -n "$app_url" ] && [ "$app_url" != "http://localhost" ]; then
    pass "APP_URL is configured ($app_url)"
else
    warn "APP_URL may not be properly configured"
fi

# --------------------------------------------------------------------------
# 4. CACHE CHECKS
# --------------------------------------------------------------------------
section "Cache Checks"

# Check config cache
if [ -f "$APP_DIR/bootstrap/cache/config.php" ]; then
    config_size=$(du -h "$APP_DIR/bootstrap/cache/config.php" | cut -f1)
    pass "Config cache exists ($config_size)"
else
    fail "Config cache missing - run 'php artisan config:cache'"
fi

# Check route cache
if [ -f "$APP_DIR/bootstrap/cache/routes-v7.php" ]; then
    route_size=$(du -h "$APP_DIR/bootstrap/cache/routes-v7.php" | cut -f1)
    pass "Route cache exists ($route_size)"
else
    fail "Route cache missing - run 'php artisan route:cache'"
fi

# Check event cache
if [ -f "$APP_DIR/bootstrap/cache/events.php" ]; then
    event_size=$(du -h "$APP_DIR/bootstrap/cache/events.php" | cut -f1)
    pass "Event cache exists ($event_size)"
else
    warn "Event cache missing (may not be required)"
fi

# Check compiled views
view_count=$(find "$APP_DIR/storage/framework/views" -name "*.php" 2>/dev/null | wc -l)
if [ "$view_count" -gt 0 ]; then
    pass "Compiled views exist ($view_count files)"
else
    warn "No compiled views found (run 'php artisan view:cache')"
fi

# --------------------------------------------------------------------------
# 5. DATABASE CHECKS
# --------------------------------------------------------------------------
section "Database Checks"

# Test database connection
if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
    pass "Database connection successful"
else
    fail "Database connection failed"
fi

# Check pending migrations
pending_migrations=$(php artisan migrate:status 2>/dev/null | grep -c "N" || echo "0")
if [ "$pending_migrations" -eq 0 ]; then
    pass "No pending migrations"
else
    warn "$pending_migrations pending migration(s) found - run 'php artisan migrate'"
fi

# Check SQLite file exists (if using SQLite)
if grep -q "DB_CONNECTION=sqlite" "$APP_DIR/.env" 2>/dev/null; then
    if [ -f "$APP_DIR/database/database.sqlite" ]; then
        pass "SQLite database file exists"
        sqlite_size=$(du -h "$APP_DIR/database/database.sqlite" | cut -f1)
        pass "SQLite database size: $sqlite_size"
    else
        fail "SQLite database file missing"
    fi
fi

# --------------------------------------------------------------------------
# 6. QUEUE WORKER CHECKS (Full check only)
# --------------------------------------------------------------------------
if [ "$FULL_CHECK" = true ]; then
    section "Queue Worker Checks"

    # Check Supervisor
    if command -v supervisorctl &> /dev/null; then
        pass "Supervisor is installed"

        # Check worker status
        worker_status=$(supervisorctl status xenonos-worker:* 2>/dev/null || echo "")
        if [ -n "$worker_status" ]; then
            running_workers=$(echo "$worker_status" | grep -c "RUNNING" || echo "0")
            total_workers=$(echo "$worker_status" | wc -l)
            if [ "$running_workers" -eq "$total_workers" ] && [ "$total_workers" -gt 0 ]; then
                pass "All queue workers running ($running_workers/$total_workers)"
            else
                fail "Some queue workers not running ($running_workers/$total_workers)"
            fi
        else
            warn "No queue workers configured"
        fi

        # Check Reverb
        reverb_status=$(supervisorctl status xenonos-reverb 2>/dev/null || echo "")
        if echo "$reverb_status" | grep -q "RUNNING"; then
            pass "Reverb WebSocket server is running"
        else
            warn "Reverb WebSocket server is not running"
        fi

        # Check scheduler cron
        if crontab -u www-data -l 2>/dev/null | grep -q "schedule:run"; then
            pass "Laravel scheduler cron entry exists"
        else
            warn "Laravel scheduler cron entry missing"
        fi
    else
        warn "Supervisor not installed - skipping worker checks"
    fi
fi

# --------------------------------------------------------------------------
# 7. WEB SERVER CHECKS (Full check only)
# --------------------------------------------------------------------------
if [ "$FULL_CHECK" = true ]; then
    section "Web Server Checks"

    # Check Nginx
    if command -v nginx &> /dev/null; then
        if systemctl is-active --quiet nginx; then
            pass "Nginx is running"
        else
            fail "Nginx is not running"
        fi

        # Test Nginx config
        if nginx -t 2>/dev/null; then
            pass "Nginx configuration is valid"
        else
            fail "Nginx configuration has errors"
        fi
    fi

    # Check PHP-FPM
    php_fpm_service="php8.3-fpm"
    if systemctl is-active --quiet "$php_fpm_service"; then
        pass "PHP-FPM ($php_fpm_service) is running"
    else
        # Try alternate version
        for version in "8.2-fpm" "8.4-fpm"; do
            if systemctl is-active --quiet "php${version}"; then
                pass "PHP-FPM (php${version}) is running"
                php_fpm_service="php${version}"
                break
            fi
        done
        fail "PHP-FPM is not running"
    fi

    # Check SSL (if configured)
    if grep -q "https://" "$APP_DIR/.env" 2>/dev/null; then
        ssl_cert=$(grep "ssl_certificate " /etc/nginx/sites-enabled/xenonos-api 2>/dev/null | head -1 | awk '{print $2}' | tr -d ';')
        if [ -n "$ssl_cert" ] && [ -f "$ssl_cert" ]; then
            pass "SSL certificate file exists"
        else
            warn "SSL certificate not found - HTTPS may not work"
        fi
    fi
fi

# --------------------------------------------------------------------------
# 8. STORAGE & LOGS
# --------------------------------------------------------------------------
section "Storage & Logs"

# Check log files
if [ -f "$APP_DIR/storage/logs/laravel.log" ]; then
    log_size=$(du -h "$APP_DIR/storage/logs/laravel.log" | cut -f1)
    pass "Laravel log file exists ($log_size)"

    # Check for recent errors in log
    recent_errors=$(tail -100 "$APP_DIR/storage/logs/laravel.log" 2>/dev/null | grep -c "ERROR\|CRITICAL\|ALERT\|EMERGENCY" || echo "0")
    if [ "$recent_errors" -gt 0 ]; then
        warn "$recent_errors recent error(s) in Laravel log"
    else
        pass "No recent errors in Laravel log"
    fi
else
    warn "Laravel log file not found (may be normal for fresh install)"
fi

# Check storage structure
required_dirs=("storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "storage/logs")
for dir in "${required_dirs[@]}"; do
    if [ -d "$APP_DIR/$dir" ]; then
        pass "Directory exists: $dir"
    else
        fail "Directory missing: $dir"
    fi
done

# =============================================================================
# SUMMARY
# =============================================================================

echo -e "\n=============================================="
echo "  Verification Summary"
echo "=============================================="
echo -e "  ${GREEN}Passed:${NC}   $PASS_COUNT"
echo -e "  ${RED}Failed:${NC}   $FAIL_COUNT"
echo -e "  ${YELLOW}Warnings:${NC} $WARN_COUNT"
echo "=============================================="

if [ $FAIL_COUNT -eq 0 ]; then
    echo -e "\n${GREEN}✓ Deployment verification passed!${NC}"
else
    echo -e "\n${RED}✗ Deployment verification failed with $FAIL_COUNT error(s)${NC}"
    echo -e "${YELLOW}Review the failed checks above and fix them before going live.${NC}"
fi

if [ $WARN_COUNT -gt 0 ]; then
    echo -e "\n${YELLOW}⚠ $WARN_COUNT warning(s) found. These should be reviewed but may not block deployment.${NC}"
fi

echo ""

exit $EXIT_CODE
