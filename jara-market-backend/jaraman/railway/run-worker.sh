#!/bin/bash

echo "👷 Starting JaraMarket Worker Service..."

# Run the queue worker
# --tries=3: retry failed jobs 3 times
# --timeout=90: timeout jobs after 90 seconds
php artisan queue:work --tries=3 --timeout=90
