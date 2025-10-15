#!/bin/bash
cd /
cd /var/www/html

echo "Running Laravel migrations and seeders..."
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed
