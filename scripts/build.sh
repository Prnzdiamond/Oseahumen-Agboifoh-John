#!/usr/bin/env bash

echo "Running composer install..."
composer install --no-dev --working-dir=/opt/render/project/src

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Creating storage symlink..."
php artisan storage:link

echo "Installing npm dependencies..."
npm ci

echo "Building assets..."
npm run build

echo "Optimizing application..."
php artisan optimize
