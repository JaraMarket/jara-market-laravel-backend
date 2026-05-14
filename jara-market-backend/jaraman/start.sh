#!/bin/bash

# Run database migrations on every deploy to keep schema up to date
echo "🔄 Running database migrations..."
php artisan migrate --force

# Start the Laravel Queue Worker in the background to process OTPs and emails instantly
echo "📬 Starting Queue Worker (Swift OTP delivery)..."
php artisan queue:work --daemon --tries=3 --timeout=60 &

# Start the Laravel Scheduler in the background for cron-based tasks
echo "🕐 Starting Laravel Scheduler..."
php artisan schedule:work &

# Start the web server
echo "🚀 Starting web server..."
vendor/bin/heroku-php-apache2 public/
