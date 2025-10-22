#!/bin/bash
# Start PHP-FPM
systemctl restart php8.4-fpm

# Start Nginx
systemctl restart nginx
