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

        if (DB::getDriverName() === 'sqlite') {
            $this->backfillDurationFieldsForSqlite();

            return;
        }

        DB::table('schedules')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereNull('schedules.duration_hours')
            ->update([
                'schedules.duration_hours' => DB::raw('subjects.duration_hours'),
                'schedules.meetings_per_week' => DB::raw('subjects.meetings_per_week'),
            ]);
    }

    private function backfillDurationFieldsForSqlite(): void
    {
        DB::table('schedules')
            ->select('id', 'subject_id', 'duration_hours', 'meetings_per_week')
            ->whereNull('duration_hours')
            ->orderBy('id')
            ->chunkById(500, function ($schedules) {
                $subjects = DB::table('subjects')
                    ->whereIn('id', $schedules->pluck('subject_id')->filter()->unique())
                    ->get()
                    ->keyBy('id');

                foreach ($schedules as $schedule) {
                    $subject = $subjects->get($schedule->subject_id);

                    if (! $subject) {
                        continue;
                    }

                    DB::table('schedules')
                        ->where('id', $schedule->id)
                        ->update([
                            'duration_hours' => $schedule->duration_hours ?: $subject->duration_hours,
                            'meetings_per_week' => $schedule->meetings_per_week ?: $subject->meetings_per_week,
                        ]);
                }
            });
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
