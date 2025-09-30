#!/bin/bash
# Stop Nginx to prevent file access conflicts during deployment
systemctl stop nginx

# Remove existing application files (except .env if it exists)
if [ -d "/var/www/html" ]; then
    cd /var/www/html
    find . -maxdepth 1 ! -name '.env' -exec rm -rf {} +
fi