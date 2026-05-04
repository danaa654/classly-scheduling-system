<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Add type column if it doesn't exist
            if (!Schema::hasColumn('subjects', 'type')) {
                $table->enum('type', ['Major', 'Minor'])->default('Major')->after('units');
            }
        });

        // ✅ Populate existing subjects with 'Major' as default
        if (Schema::hasColumn('subjects', 'type')) {
            DB::table('subjects')
                ->whereNull('type')
                ->orWhere('type', '')
                ->update(['type' => 'Major']);
        }
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};