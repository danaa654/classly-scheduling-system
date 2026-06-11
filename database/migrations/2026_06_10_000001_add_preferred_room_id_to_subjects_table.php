<?php

/**
 * MIGRATION: Add preferred_room_id to subjects table
 *
 * Paste this file into:  database/migrations/
 * Run with:              php artisan migrate
 *
 * Adds a nullable FK so the "Assign Subjects" modal in Manage Rooms can
 * bind subjects to a preferred room before the auto-scheduler runs.
 * ON DELETE SET NULL ensures a deleted room never orphans a subject row.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Nullable: a subject may have no preferred room yet
            // onDelete('set null'): removing a room never breaks subject records
            $table->foreignId('preferred_room_id')
                ->nullable()
                ->after('preferred_room_type')   // column added by migration 000002
                ->constrained('rooms')
                ->onDelete('set null');

            // Speeds up "which subjects are assigned to Room X?" queries
            $table->index('preferred_room_id', 'idx_subjects_preferred_room');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'preferred_room_id')) {
                $table->dropIndex('idx_subjects_preferred_room');
                $table->dropForeign(['preferred_room_id']);
                $table->dropColumn('preferred_room_id');
            }
        });
    }
};