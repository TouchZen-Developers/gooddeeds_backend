#!/bin/bash
echo "Backing up existing .env..."
if [ -f /var/www/html/.env ]; then
    cp /var/www/html/.env /tmp/.env.backup
fi