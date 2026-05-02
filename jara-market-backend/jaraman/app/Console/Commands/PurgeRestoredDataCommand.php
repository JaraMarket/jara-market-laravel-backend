<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeRestoredDataCommand extends Command
{
    protected $signature = 'app:purge-restored';
    protected $description = 'Surgically remove unwanted restored legacy data and professional meals';

    public function handle()
    {
        $this->info("🚀 Starting Surgical Data Removal...");

        $dataDir = database_path('data');
        
        // 1. Load Legacy IDs
        $legacyProducts = json_decode(file_get_contents("$dataDir/legacy_products.json"), true);
        $legacyIngredients = json_decode(file_get_contents("$dataDir/legacy_ingredients.json"), true);

        $productIds = array_column($legacyProducts, 'id');
        $ingredientIds = array_column($legacyIngredients, 'id');

        // 2. Load Professional Names
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

        $this->warn("📦 Target: " . count($productIds) . " products and " . count($ingredientIds) . " ingredients.");

        if (!$this->confirm('Are you sure you want to proceed with this surgical removal?', true)) {
            return;
        }

        // 3. Remove Pivot Data
        DB::table('ingredient_product')->whereIn('product_id', $productIds)->delete();
        DB::table('ingredient_product')->whereIn('ingredient_id', $ingredientIds)->delete();
        DB::table('category_product')->whereIn('product_id', $productIds)->delete();

        // 4. Remove Main Records
        DB::table('products')->whereIn('id', $productIds)->delete();
        DB::table('ingredients')->whereIn('id', $ingredientIds)->delete();

        // 5. Cleanup Categories
        DB::table('categories')->where('name', 'like', 'Restored Category%')->delete();

        $this->info("✅ Surgery Complete.");
        $this->info("📊 Remaining: " . DB::table('products')->count() . " Products, " . DB::table('ingredients')->count() . " Ingredients.");
    }
}
