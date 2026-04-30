#!/bin/bash

# Exit the script if any command fails
set -e

echo "🚀 Initializing JaraMarket App Service..."

# Run migrations (force for production)
php artisan migrate --force

# Optimize and Cache
echo "🧹 Clearing and caching configurations..."
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "✅ App Service Initialization Complete."
