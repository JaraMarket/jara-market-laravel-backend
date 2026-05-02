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
            ['id' => 1, 'name' => 'Cabohydrate', 'description' => 'nil'],
            ['id' => 2, 'name' => 'Protein', 'description' => 'nil'],
            ['id' => 3, 'name' => 'Vitamin', 'description' => 'nil'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
