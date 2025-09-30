#!/bin/bash
echo "Restarting PHP-FPM and Nginx..."
systemctl restart php8.4-fpm
systemctl restart nginx