<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n--- S3 SETTINGS IN DATABASE ---\n";
$settings = DB::table('settings')->where('key', 'like', 's3_%')->pluck('value', 'key');
print_r($settings->toArray());

echo "\n--- PRODUCT TABLE COLUMNS ---\n";
$columns = Schema::getColumnListing('products');
print_r($columns);

echo "\n--- SAMPLE PRODUCT IMAGES ---\n";
$products = DB::table('products')->select('id', 'name', 'image', 'image_url')->limit(5)->get();
print_r($products->toArray());
