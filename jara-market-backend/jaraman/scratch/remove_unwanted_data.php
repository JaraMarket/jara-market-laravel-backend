<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🩺 Surgical Data Removal Script
 * This script removes the 200+ unwanted "restored" records while preserving original data.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 Starting Surgical Data Removal...\n";

// 1. Identify Target IDs from Legacy Files
$dataDir = database_path('data');
$legacyProducts = json_decode(file_get_contents("$dataDir/legacy_products.json"), true);
$legacyIngredients = json_decode(file_get_contents("$dataDir/legacy_ingredients.json"), true);

$productIds = array_column($legacyProducts, 'id');
$ingredientIds = array_column($legacyIngredients, 'id');

// 2. Add IDs from ProfessionalMealSeeder (Names)
$professionalMealNames = [
    'Gourmet Egusi Soup (Traditional Style)',
    'Smoky Jollof Rice (Party Style)',
    'Classic Edikaikong Soup',
    'Assorted Meat Pepper Soup',
    'Yam Porridge with Garden Eggs',
    'Native Rice with Dried Fish',
    'Vegetable Fried Rice',
    'Beans & Fried Plantain Duo',
    'Afang Soup (Calabar Special)',
    'White Soup (Ofe-Nsala) with Catfish'
];
$profIds = DB::table('products')->whereIn('name', $professionalMealNames)->pluck('id')->toArray();
$productIds = array_merge($productIds, $profIds);

echo "📦 Found " . count($productIds) . " products and " . count($ingredientIds) . " ingredients to remove.\n";

// 3. Remove Pivot Data First
DB::table('ingredient_product')->whereIn('product_id', $productIds)->delete();
DB::table('ingredient_product')->whereIn('ingredient_id', $ingredientIds)->delete();
DB::table('category_product')->whereIn('product_id', $productIds)->delete();

echo "🔗 Pivot data removed.\n";

// 4. Remove Products and Ingredients
DB::table('products')->whereIn('id', $productIds)->delete();
DB::table('ingredients')->whereIn('id', $ingredientIds)->delete();

echo "🥗 Products and Ingredients removed.\n";

// 5. Cleanup Empty "Restored" Categories
DB::table('categories')->where('name', 'like', 'Restored Category%')->delete();

echo "📁 Category placeholders cleaned up.\n";

// 6. Final Count Verification
$finalProd = DB::table('products')->count();
$finalIng = DB::table('ingredients')->count();

echo "✅ Surgery Complete.\n";
echo "📊 Remaining Data: $finalProd Products, $finalIng Ingredients.\n";
