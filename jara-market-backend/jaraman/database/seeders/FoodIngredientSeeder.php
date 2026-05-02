<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodIngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            ['product_id' => 1, 'ingredient_id' => 1],
            ['product_id' => 1, 'ingredient_id' => 2],
            ['product_id' => 2, 'ingredient_id' => 1],
        ];

        foreach ($ingredients as $item) {
            DB::table('ingredient_product')->updateOrInsert(
                ['product_id' => $item['product_id'], 'ingredient_id' => $item['ingredient_id']],
                array_merge($item, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
