#!/bin/bash
# Start PHP-FPM
systemctl start php8.4-fpm

# Start Nginx
systemctl start nginx

# Ensure services are enabled to start on boot
systemctl enable php8.4-fpm
systemctl enable nginx