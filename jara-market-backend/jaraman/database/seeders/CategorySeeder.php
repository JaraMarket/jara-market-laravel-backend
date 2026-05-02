<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Carbohydrate', 'description' => 'nil', 'category_type_id' => 1, 'sort_by' => 1],
            ['id' => 2, 'name' => 'Protein', 'description' => 'nil', 'category_type_id' => 1, 'sort_by' => 2],
            ['id' => 3, 'name' => 'Vitamin', 'description' => 'nil', 'category_type_id' => 1, 'sort_by' => 3],
            ['id' => 4, 'name' => 'Grains', 'description' => 'nil', 'category_type_id' => 2, 'sort_by' => 4],
            ['id' => 5, 'name' => 'Tubers', 'description' => 'nil', 'category_type_id' => 2, 'sort_by' => 5],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
