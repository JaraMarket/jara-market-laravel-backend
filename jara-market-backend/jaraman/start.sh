#!/bin/bash

# Start the Laravel Scheduler to handle background tasks and emails
echo "🚀 Starting JaraMarket Scheduler (Handles Swift OTPs & Automations)..."
php artisan schedule:work &

# Start the web server
echo "🚀 Starting web server..."
vendor/bin/heroku-php-apache2 public/
