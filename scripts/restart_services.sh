#!/bin/bash

cd /

cd /var/www/html
php artisan config:clear
php artisan cache:clear
php artisan config:cache

echo "Restarting PHP-FPM and Nginx..."
systemctl restart php8.4-fpm
systemctl restart nginx
