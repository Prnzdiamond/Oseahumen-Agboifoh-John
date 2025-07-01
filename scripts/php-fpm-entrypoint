#!/bin/bash

main() {
    if [ "$IS_WORKER" = "true" ]; then
        exec "$@"
    else
        prepare_file_permissions
        run_npm_build
        prepare_storage
        # Skip database operations since you're using Supabase
        optimize_app
        run_server "$@"
    fi
}

prepare_file_permissions() {
    chmod a+x ./artisan
}

run_npm_build() {
    echo "Installing NPM dependencies"
    if [ -f "package.json" ]; then
        echo "Running NPM clean install"
        npm ci
        echo "Running NPM build"
        npm run build
    else
        echo "No package.json found, skipping NPM build"
    fi
}

prepare_storage() {
    # Create required directories for Laravel
    mkdir -p /var/www/html/storage/framework/cache/data
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views

    # Set permissions for the storage directory
    chown -R www-data:www-data /var/www/html/storage
    chmod -R 775 /var/www/html/storage

    # Ensure the symlink exists
    php artisan storage:link
}

optimize_app() {
    ./artisan optimize:clear
    ./artisan optimize

    # Filament specific optimizations
    ./artisan filament:optimize
}

run_server() {
    exec /usr/local/bin/docker-php-entrypoint "$@"
}

main "$@"
