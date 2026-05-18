#!/bin/bash

# Exit the script if any command fails
set -e

echo "🚀 Initializing JaraMarket App Service..."

# Run migrations and seeders (force for production)
php artisan migrate --force
php artisan db:seed --force

# Create the storage symlink so uploaded files are publicly accessible
php artisan storage:link --force

# Optimize and Cache
echo "🧹 Clearing and caching configurations..."
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Generate API Documentation
echo "📚 Generating API Documentation..."
php artisan l5-swagger:generate

echo "✅ App Service Initialization Complete."

# Start background queue worker
echo "🚀 Starting JaraMarket Queue Worker (Handles Background OTPs & Emails)..."
php artisan queue:work --verbose --tries=3 &

# Start background scheduler
echo "🚀 Starting JaraMarket Scheduler (Handles Automations)..."
php artisan schedule:work &

echo "🚀 Starting Laravel Web Server on port ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
