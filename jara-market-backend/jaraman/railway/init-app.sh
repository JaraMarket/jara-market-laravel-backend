#!/bin/bash

# Exit the script if any command fails
set -e

echo "🚀 Initializing JaraMarket App Service..."

# Run migrations (force for production)
php artisan migrate --force

# Create the storage symlink so uploaded files are publicly accessible
php artisan storage:link --force

# Optimize and Cache
echo "🧹 Clearing and caching configurations..."
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "✅ App Service Initialization Complete."

echo "🚀 Starting Laravel Web Server on port ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
