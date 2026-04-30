<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add social authentication columns to the users table.
     *
     * - provider: The OAuth provider name (google, facebook, apple).
     * - provider_id: The unique ID from the OAuth provider.
     * - provider_avatar: The profile picture URL from the provider.
     * - password: Made nullable so social-only users don't need one.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('role');
            $table->string('provider_id')->nullable()->after('provider');
            $table->string('provider_avatar')->nullable()->after('provider_id');

            // Make password nullable for social-only users
            $table->string('password')->nullable()->change();

            // Composite index for fast social lookups
            $table->index(['provider', 'provider_id'], 'users_social_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_social_lookup_index');
            $table->dropColumn(['provider', 'provider_id', 'provider_avatar']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
