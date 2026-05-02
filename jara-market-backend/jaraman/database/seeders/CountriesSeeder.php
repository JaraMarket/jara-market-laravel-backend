<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        $path = base_path('database/data/countries.json');
        
        if (!file_exists($path)) {
            return;
        }

        $json = file_get_contents($path);
        $countries = json_decode($json, true);

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['name' => $country['country']],
                ['code' => '', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
