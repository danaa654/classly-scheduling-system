<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `can_finalize_schedule` flag to the users table.
 *
 * Logic:
 *  - Admin always can finalize (handled in code, not DB).
 *  - Registrar can finalize ONLY when this column is TRUE.
 *  - All other roles cannot finalize regardless of this flag.
 *
 * Set via: Admin → User Management → toggle "Allow Registrar to Finalize Schedules"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Permission flag — default false (only Admin finalizes unless explicitly delegated)
            $table->boolean('can_finalize_schedule')
                  ->default(false)
                  ->after('is_active')
                  ->comment('When true, Registrar role can finalize block schedules. Admin always can.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_finalize_schedule');
        });
    }
};