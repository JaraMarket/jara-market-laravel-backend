#!/bin/bash

# Exit on error
set -e

echo "🚀 JaraMarket Production Startup Initializing..."

# 1. Run migrations
echo "🔄 Syncing database schema..."
php artisan migrate --force

# 2. Clear and Cache everything (Ensures no Mailtrap settings remain)
echo "🧹 Purging and rebuilding caches..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Generate Docs (Required for the API)
echo "📚 Refreshing API Documentation..."
php artisan l5-swagger:generate

# 4. Start the Queue Worker (DAEMON MODE)
echo "👷 Starting Background Queue Worker (Swift OTP Delivery)..."
php artisan queue:work --tries=3 --timeout=90 --daemon &

# 5. Start the Scheduler
echo "🕐 Starting Task Scheduler..."
php artisan schedule:work &

# 6. Final handoff to web server
echo "✅ Initialization complete. Launching web server..."
vendor/bin/heroku-php-apache2 public/

