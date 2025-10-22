#!/bin/bash

cd /
cd /var/www/html

echo "Ensuring required directories exist..."
mkdir -p storage/framework/{cache/data,views,sessions} storage/logs bootstrap/cache database

echo "Ensuring Laravel log and SQLite database exist..."
touch storage/logs/laravel.log
touch database/database.sqlite

echo "Setting ownership and permissions..."
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database
chmod 664 storage/logs/laravel.log
chmod 664 database/database.sqlite

# Ensure .env exists
#if [ ! -f ".env" ]; then
#    cp .env.example .env
#    sudo -u www-data php artisan key:generate
#fi

# Export AWS environment variables for Artisan commands (optional)
#if [ -f .env ]; then
#    export AWS_ACCESS_KEY_ID=$(grep AWS_ACCESS_KEY_ID .env | cut -d '=' -f2)
#    export AWS_SECRET_ACCESS_KEY=$(grep AWS_SECRET_ACCESS_KEY .env | cut -d '=' -f2)
#    export AWS_DEFAULT_REGION=$(grep AWS_DEFAULT_REGION .env | cut -d '=' -f2)
#    export AWS_BUCKET=$(grep AWS_BUCKET .env | cut -d '=' -f2)
#    export AWS_USE_PATH_STYLE_ENDPOINT=$(grep AWS_USE_PATH_STYLE_ENDPOINT .env | cut -d '=' -f2)
#fi

echo "Installing Composer dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

#echo "Running migrations and seeders..."
#sudo -u www-data php artisan migrate --force
#sudo -u www-data php artisan db:seed

#echo "Clearing and caching configurations and routes..."
#sudo -u www-data php artisan cache:clear
#sudo -u www-data php artisan config:cache
#sudo -u www-data php artisan route:cache

#echo "Skipping view:cache — API-only app"
