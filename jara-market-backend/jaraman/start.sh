#!/bin/bash

# Start the queue worker in the background
echo "🚀 Starting background queue worker..."
php artisan queue:work --tries=3 --timeout=90 &

# Start the web server
echo "🚀 Starting web server..."
vendor/bin/heroku-php-apache2 public/
