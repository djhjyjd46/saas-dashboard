#!/bin/bash
# Server-side deployment script for SaaS Dashboard
set -e

REMOTE_PATH=$1
cd "$REMOTE_PATH"

echo "=== Deployment started at $(date) ==="
echo "Working directory: $(pwd)"

# Detect PHP 8.4 specifically for ISPmanager
if [ -f /opt/php84/bin/php ]; then
    PHP_BIN="/opt/php84/bin/php"
elif [ -f /usr/bin/php8.4 ]; then
    PHP_BIN="/usr/bin/php8.4"
elif [ -f /usr/bin/php84 ]; then
    PHP_BIN="/usr/bin/php84"
else
    PHP_BIN="php"
fi

echo "Using PHP: $PHP_BIN"
$PHP_BIN -v | head -n 1

# Unzip
echo "Step 1: Unzipping files..."
unzip -o dist.zip || [ $? -eq 1 ] || { echo "Unzip failed with critical error"; exit 1; }
echo "Unzip completed."
rm dist.zip

# Permissions and folders
echo "Step 2: Setting up storage and permissions..."
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs
chmod -R 777 storage bootstrap/cache

# Composer
echo "Step 3: Installing dependencies (Composer)..."
# Cleanup vendor to avoid corrupted state
rm -rf vendor
COMPOSER_BIN=$(which composer || echo "no-composer")
if [ "$COMPOSER_BIN" != "no-composer" ]; then
    $PHP_BIN "$COMPOSER_BIN" install --no-dev --optimize-autoloader
else
    echo "Warning: Global composer not found. Trying local composer.phar..."
    if [ ! -f composer.phar ]; then
        curl -sS https://getcomposer.org/installer | $PHP_BIN
    fi
    $PHP_BIN composer.phar install --no-dev --optimize-autoloader
fi

# Laravel actions
echo "Step 4: Running Artisan commands..."

echo "Clearing non-DB caches..."
$PHP_BIN artisan route:clear
$PHP_BIN artisan config:clear
$PHP_BIN artisan view:clear

echo "Running migrations..."
$PHP_BIN artisan migrate --force

echo "Syncing Yandex campaigns (updates status & last_synced_at)..."
$PHP_BIN artisan sync:yandex-campaigns || echo "Warning: campaign sync failed, continuing..."

echo "Clearing DB cache after migration..."
$PHP_BIN artisan cache:clear

echo "=== Deployment finished successfully! ==="
