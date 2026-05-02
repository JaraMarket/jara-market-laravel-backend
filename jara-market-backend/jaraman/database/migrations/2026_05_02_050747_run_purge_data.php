<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('app:purge-restored', [
            '--no-interaction' => true,
        ]);
    }

    public function down(): void
    {
        // No rollback needed for data purge
    }
};
