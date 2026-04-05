#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "Clearing cached configurations..."
php artisan optimize:clear

echo "Installing Node dependencies..."
npm install

echo "Building Node assets..."
npm run build

echo "Running database migrations..."
php artisan migrate --force

echo "Build complete."
