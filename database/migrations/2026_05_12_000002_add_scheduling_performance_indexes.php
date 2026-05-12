<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['department', 'major', 'year_level', 'section', 'day'], 'schedules_group_day_index');
            $table->index(['faculty_id', 'day', 'start_time', 'end_time'], 'schedules_faculty_time_index');
            $table->index(['room_id', 'day', 'start_time', 'end_time'], 'schedules_room_time_index');
            $table->index(['status', 'faculty_id'], 'schedules_status_faculty_index');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->index(['department', 'major', 'year_level', 'section'], 'subjects_group_index');
            $table->index(['type', 'meetings_per_week'], 'subjects_type_meetings_index');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['type', 'specialization'], 'rooms_type_specialization_index');
            $table->index('capacity', 'rooms_capacity_index');
        });

        Schema::table('faculties', function (Blueprint $table) {
            if (!Schema::hasColumn('faculties', 'availability')) {
                $table->json('availability')->nullable()->after('max_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            if (Schema::hasColumn('faculties', 'availability')) {
                $table->dropColumn('availability');
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_capacity_index');
            $table->dropIndex('rooms_type_specialization_index');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex('subjects_type_meetings_index');
            $table->dropIndex('subjects_group_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_status_faculty_index');
            $table->dropIndex('schedules_room_time_index');
            $table->dropIndex('schedules_faculty_time_index');
            $table->dropIndex('schedules_group_day_index');
        });
    }
};
