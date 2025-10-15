#!/bin/bash
echo "Cleaning old deployment..."
cd /
cd /var/www/html
shopt -s extglob
rm -rf !(.env)
echo "Cleanup complete."
