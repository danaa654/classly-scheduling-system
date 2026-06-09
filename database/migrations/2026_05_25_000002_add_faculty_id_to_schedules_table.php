<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds the `faculty_id` foreign key to the schedules table.
 *
 * The Schedule model, BlockSchedule Livewire component, and
 * ScheduleConflictService all reference `faculty_id`, but the
 * original create_schedules_table migration only included `user_id`.
 *
 * This migration adds the dedicated faculty assignment column so
 * faculty can be assigned independently of the system user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Only add if it doesn't already exist (safe re-run)
            if (! Schema::hasColumn('schedules', 'faculty_id')) {
                $table->foreignId('faculty_id')
                      ->nullable()
                      ->after('user_id')
                      ->constrained('faculties')
                      ->onDelete('set null')
                      ->comment('Faculty member assigned to teach this schedule slot.');

                // Index for quick conflict lookup by faculty + day
                $table->index(['faculty_id', 'day'], 'idx_schedules_faculty_day');
            }
        });
    }

    public function down(): void
    {
        // Check if column exists before attempting to drop
        if (! Schema::hasColumn('schedules', 'faculty_id')) {
            return;
        }

        // Drop foreign key by checking INFORMATION_SCHEMA first
        $this->dropForeignKeyIfExists('schedules', 'faculty_id');

        // Drop index if it exists
        if (Schema::hasIndex('schedules', 'idx_schedules_faculty_day')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropIndex('idx_schedules_faculty_day');
            });
        }

        // Drop the column
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('faculty_id');
        });
    }

    /**
     * Safely drop a foreign key by checking if it exists first.
     * Queries INFORMATION_SCHEMA to find the actual constraint name.
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        // Find the actual foreign key constraint name from INFORMATION_SCHEMA
        $constraint = DB::select(
            "SELECT CONSTRAINT_NAME 
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND COLUMN_NAME = ? 
             AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$database, $table, $column]
        );

        // If found, drop it
        if (!empty($constraint)) {
            $constraintName = $constraint[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
        }
    }
};