#!/bin/bash
set -e

# Create required Laravel storage framework directories if missing
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs

# Copy .env.example if .env does not exist
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Seed database (only if empty)
php artisan db:seed --force 2>/dev/null || true

# Generate Swagger docs
php artisan l5-swagger:generate 2>/dev/null || true

# Clear and cache config
php artisan config:clear
php artisan route:clear

# Fix permissions for storage, cache, and database (including files created by migrations/seeders/docs generation)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

exec "$@"

