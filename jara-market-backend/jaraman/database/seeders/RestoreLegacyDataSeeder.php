<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestoreLegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $dataDir = database_path('data');

        // 1. Restore Categories
        $categories = json_decode(file_get_contents("$dataDir/legacy_categories.json"), true);
        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['id' => $cat['id']],
                [
                    'name' => $cat['name'],
                    'category_type_id' => $cat['category_type_id'] ?? 1,
                    'description' => $cat['description'] ?? '',
                    'sort_by' => $cat['sort_by'] ?? 0,
                    'created_at' => $cat['created_at'],
                    'updated_at' => $cat['updated_at'],
                ]
            );
        }

        // 2. Restore Ingredients
        $ingredients = json_decode(file_get_contents("$dataDir/legacy_ingredients.json"), true);
        foreach ($ingredients as $ing) {
            DB::table('ingredients')->updateOrInsert(
                ['id' => $ing['id']],
                [
                    'category_id' => $ing['category_id'],
                    'name' => $ing['name'],
                    'description' => $ing['description'] ?? '',
                    'price' => $ing['price'],
                    'discounted_price' => $ing['discounted_price'],
                    'unit' => $ing['unit'],
                    'stock' => $ing['stock'] ?? 0,
                    'image_url' => $ing['image_url'],
                    'created_at' => $ing['created_at'],
                    'updated_at' => $ing['updated_at'],
                ]
            );
        }

        // 3. Restore Products
        $products = json_decode(file_get_contents("$dataDir/legacy_products.json"), true);
        foreach ($products as $prod) {
            DB::table('products')->updateOrInsert(
                ['id' => $prod['id']],
                [
                    'name' => $prod['name'],
                    'description' => $prod['description'] ?? '',
                    'price' => $prod['price'],
                    'discount_price' => $prod['discount_price'] ?? 0,
                    'stock' => $prod['stock'] ?? 0,
                    'preparation_steps' => $prod['preparation_steps'],
                    'rating' => $prod['rating'] ?? 0,
                    'image_url' => $prod['image_url'],
                    'created_at' => $prod['created_at'],
                    'updated_at' => $prod['updated_at'],
                ]
            );
        }

        // 4. Restore Category-Product Pivot
        $cpPivots = json_decode(file_get_contents("$dataDir/legacy_category_product.json"), true);
        foreach ($cpPivots as $cp) {
            DB::table('category_product')->updateOrInsert(
                ['category_id' => $cp['category_id'], 'product_id' => $cp['product_id']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // 5. Restore Ingredient-Product Pivot
        $ipPivots = json_decode(file_get_contents("$dataDir/legacy_ingredient_product.json"), true);
        foreach ($ipPivots as $ip) {
            DB::table('ingredient_product')->updateOrInsert(
                ['product_id' => $ip['product_id'], 'ingredient_id' => $ip['ingredient_id']],
                [
                    'quantity' => $ip['quantity'],
                    'unit' => $ip['unit'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
