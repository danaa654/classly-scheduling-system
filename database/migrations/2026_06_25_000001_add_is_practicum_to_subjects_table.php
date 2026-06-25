<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `is_practicum` boolean flag to the subjects table.
 *
 * When true, the subject (OJT, Practicum, Student Teaching, etc.) is
 * deployed off-campus and does NOT require a physical classroom/room.
 * The auto-scheduler and block-schedule builder must skip room assignment
 * for any subject where is_practicum = true.
 *
 * Default: false — all existing subjects are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('is_practicum')
                  ->default(false)
                  ->after('preferred_room_type')
                  ->comment('If true, subject is off-campus (OJT/Practicum) and needs no room assignment.');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_practicum');
        });
    }
};