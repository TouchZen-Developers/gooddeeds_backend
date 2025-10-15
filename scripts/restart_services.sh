#!/bin/bash

cd /

#!/bin/bash
cd /var/www/html

# Rebuild caches after migrations
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

echo "Restarting PHP-FPM and Nginx..."
systemctl restart php8.4-fpm
systemctl restart nginx
