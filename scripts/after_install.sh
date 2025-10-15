#!/bin/bash
# Navigate to the application directory
cd /
cd /var/www/html

# Ensure .env file exists (copy from a backup or create if needed)
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Set correct permissions for Laravel directories
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Run Laravel migrations
php artisan migrate --force
php artisan db:seed 

# Clear and cache Laravel configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
