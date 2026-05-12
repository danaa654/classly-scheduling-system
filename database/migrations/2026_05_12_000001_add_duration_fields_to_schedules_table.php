<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'duration_hours')) {
                $table->decimal('duration_hours', 4, 2)->nullable()->after('end_time');
            }

            if (!Schema::hasColumn('schedules', 'meetings_per_week')) {
                $table->unsignedTinyInteger('meetings_per_week')->nullable()->after('duration_hours');
            }
        });

        DB::table('schedules')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereNull('schedules.duration_hours')
            ->update([
                'schedules.duration_hours' => DB::raw('subjects.duration_hours'),
                'schedules.meetings_per_week' => DB::raw('subjects.meetings_per_week'),
            ]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'meetings_per_week')) {
                $table->dropColumn('meetings_per_week');
            }

            if (Schema::hasColumn('schedules', 'duration_hours')) {
                $table->dropColumn('duration_hours');
            }
        });
    }
};
