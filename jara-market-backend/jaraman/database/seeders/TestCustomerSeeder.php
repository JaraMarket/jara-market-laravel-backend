<?php

namespace Database\Seeders;

use App\Enums\UserPermissionsEnum;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@jaramarket.com'],
            [
                'password' => 'Test@1234', // Laravel's password cast hashes this automatically
                'firstname' => 'Test',
                'lastname' => 'Customer',
                'phone_number' => '08011112222',
                'role' => UserPermissionsEnum::CUSTOMER(),
                'email_verified_at' => now(),
                'is_active' => true,
                'referral_code' => Str::random(10),
            ]
        );

        Wallet::firstOrCreate(['user_id' => $user->id]);
    }
}
