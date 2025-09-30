#!/bin/bash
echo "Restoring .env..."
if [ -f /tmp/.env.backup ]; then
    cp /tmp/.env.backup /var/www/html/.env
fi