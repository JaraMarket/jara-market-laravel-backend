#!/bin/bash

echo "⏰ Starting JaraMarket Cron Service..."

# Keep the scheduler running in a loop
# This is the standard approach for a dedicated Cron Service on Railway
while [ true ]
do
  echo "Running the scheduler at $(date)..."
  php artisan schedule:run --no-interaction --verbose
  
  # Wait for 60 seconds before running again
  sleep 60
done
