<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Jaramarket Specialized Audit ---\n";

// 1. Check Routes
echo "\n[1] Checking Routes...\n";
$routes = Route::getRoutes();
echo "Total Routes: " . count($routes) . "\n";
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'debug') || str_contains($route->uri(), 'test')) {
        echo "⚠️ Potential sensitive route: " . $route->uri() . "\n";
    }
}

// 2. Check Database Connectivity & Seeding
echo "\n[2] Checking Database & Seeding...\n";
try {
    $userCount = User::count();
    $catCount = Category::count();
    $stateCount = DB::table('states')->count();
    
    echo "Users: $userCount\n";
    echo "Categories: $catCount\n";
    echo "States: $stateCount\n";
    
    if ($userCount === 0) echo "❌ Error: Users table is empty!\n";
    if ($catCount === 0) echo "❌ Error: Categories table is empty!\n";
    if ($stateCount === 0) echo "❌ Error: States table is empty!\n";
} catch (\Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

// 3. Check App Key
echo "\n[3] Checking APP_KEY...\n";
if (empty(env('APP_KEY'))) {
    echo "❌ Error: APP_KEY is missing!\n";
} else {
    echo "✅ APP_KEY is set.\n";
}

// 4. Check Environment
echo "\n[4] Checking Environment...\n";
echo "Current Env: " . env('APP_ENV') . "\n";
if (env('APP_DEBUG')) {
    echo "⚠️ Warning: APP_DEBUG is enabled in production environment!\n";
}

// 5. Check Log Configuration
echo "\n[5] Checking Logs...\n";
echo "Log Channel: " . env('LOG_CHANNEL') . "\n";

echo "\n--- Audit Finished ---\n";
