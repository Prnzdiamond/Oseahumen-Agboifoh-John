#!/bin/bash

set -e

echo "==> Starting build process..."

# Function from your php-fpm-entrypoint
prepare_file_permissions() {
    echo "Setting file permissions..."
    chmod a+x ./artisan
}

# Function adapted from your php-fpm-entrypoint
run_npm_build() {
    echo "Installing NPM dependencies..."
    if [ -f "package.json" ]; then
        echo "Running NPM clean install..."
        npm ci
        echo "Running NPM build..."
        npm run build
    else
        echo "No package.json found, skipping NPM build"
    fi
}

# Function adapted from your php-fpm-entrypoint
prepare_storage() {
    echo "Preparing storage directories..."
    # Create required directories for Laravel
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    # Set permissions for the storage directory
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache

    # Ensure the symlink exists
    php artisan storage:link
}

# Function adapted from your php-fmp-entrypoint
optimize_app() {
    echo "Optimizing Laravel application..."
    php artisan optimize:clear
    php artisan optimize

    # Filament specific optimizations
    php artisan filament:optimize
}

main() {
    echo "==> Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction

    echo "==> Preparing file permissions..."
    prepare_file_permissions

    echo "==> Building frontend assets..."
    run_npm_build

    echo "==> Setting up storage..."
    prepare_storage

    echo "==> Optimizing application..."
    optimize_app

    echo "==> Build completed successfully!"
}

# Run the main function
main
