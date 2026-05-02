<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = [
            ['code' => 'piece', 'name' => 'Piece'],
            ['code' => 'kg', 'name' => 'Kilogram'],
            ['code' => 'g', 'name' => 'Gram'],
            ['code' => 'l', 'name' => 'Liter'],
            ['code' => 'ml', 'name' => 'Milliliter'],
            ['code' => 'cup', 'name' => 'Cup'],
            ['code' => 'tbsp', 'name' => 'Tablespoon'],
            ['code' => 'tsp', 'name' => 'Teaspoon'],
            ['code' => 'por', 'name' => 'Portion'],
        ];

        foreach ($uoms as $uom) {
            DB::table('uoms')->updateOrInsert(
                ['code' => $uom['code']],
                array_merge($uom, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
