<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'faculty_id')) {
                $table->dropForeign(['faculty_id']);
                $table->dropIndex('idx_schedules_faculty_day');
                $table->dropColumn('faculty_id');
            }
        });
    }
};