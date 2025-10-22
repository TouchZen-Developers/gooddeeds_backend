#!/bin/bash
echo "Restoring .env..."
if [ -f /tmp/.env.backup ]; then
    cp /tmp/.env.backup /var/www/html/.env
    chown www-data:www-data /var/www/html/.env
fi

# Clear config cache so Laravel reads the restored .env
cd /
cd /var/www/html
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
