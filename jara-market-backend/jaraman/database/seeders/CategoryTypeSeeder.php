<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Food'],
            ['id' => 2, 'name' => 'Vendor'],
        ];

        foreach ($types as $type) {
            DB::table('category_types')->updateOrInsert(
                ['id' => $type['id']],
                array_merge($type, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
