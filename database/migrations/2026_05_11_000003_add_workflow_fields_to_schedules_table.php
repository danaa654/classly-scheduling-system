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
            if (!Schema::hasColumn('schedules', 'faculty_id')) {
                $table->foreignId('faculty_id')
                    ->nullable()
                    ->after('room_id')
                    ->constrained('faculties')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('schedules', 'department')) {
                $table->string('department')->nullable()->after('user_id')->index();
            }

            if (!Schema::hasColumn('schedules', 'major')) {
                $table->string('major')->nullable()->after('department')->index();
            }

            if (!Schema::hasColumn('schedules', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->nullable()->after('major')->index();
            }

            if (!Schema::hasColumn('schedules', 'duration_hours')) {
                $table->decimal('duration_hours', 4, 2)->nullable()->after('end_time');
            }

            if (!Schema::hasColumn('schedules', 'meetings_per_week')) {
                $table->unsignedTinyInteger('meetings_per_week')->nullable()->after('duration_hours');
            }
        });

        if (Schema::hasColumn('schedules', 'status')) {
            DB::table('schedules')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'partial']);
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->backfillScheduleFieldsForSqlite();

            return;
        }

        DB::table('schedules')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereNull('schedules.department')
            ->update([
                'schedules.department' => DB::raw('subjects.department'),
                'schedules.major' => DB::raw('subjects.major'),
                'schedules.year_level' => DB::raw('subjects.year_level'),
                'schedules.duration_hours' => DB::raw('subjects.duration_hours'),
                'schedules.meetings_per_week' => DB::raw('subjects.meetings_per_week'),
            ]);

        DB::table('schedules')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereNull('schedules.duration_hours')
            ->update([
                'schedules.duration_hours' => DB::raw('subjects.duration_hours'),
                'schedules.meetings_per_week' => DB::raw('subjects.meetings_per_week'),
            ]);

        if (Schema::hasColumn('subjects', 'faculty_id') && Schema::hasColumn('schedules', 'faculty_id')) {
            DB::table('schedules')
                ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
                ->whereNull('schedules.faculty_id')
                ->whereNotNull('subjects.faculty_id')
                ->update(['schedules.faculty_id' => DB::raw('subjects.faculty_id')]);
        }
    }

    private function backfillScheduleFieldsForSqlite(): void
    {
        DB::table('schedules')
            ->select('id', 'subject_id', 'department', 'major', 'year_level', 'duration_hours', 'meetings_per_week', 'faculty_id')
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
                            'department' => $schedule->department ?: $subject->department,
                            'major' => $schedule->major ?: $subject->major,
                            'year_level' => $schedule->year_level ?: $subject->year_level,
                            'duration_hours' => $schedule->duration_hours ?: $subject->duration_hours,
                            'meetings_per_week' => $schedule->meetings_per_week ?: $subject->meetings_per_week,
                            'faculty_id' => $schedule->faculty_id ?: ($subject->faculty_id ?? null),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'faculty_id')) {
                $table->dropConstrainedForeignId('faculty_id');
            }

            if (Schema::hasColumn('schedules', 'year_level')) {
                $table->dropColumn('year_level');
            }

            if (Schema::hasColumn('schedules', 'major')) {
                $table->dropColumn('major');
            }

            if (Schema::hasColumn('schedules', 'department')) {
                $table->dropColumn('department');
            }

            if (Schema::hasColumn('schedules', 'meetings_per_week')) {
                $table->dropColumn('meetings_per_week');
            }

            if (Schema::hasColumn('schedules', 'duration_hours')) {
                $table->dropColumn('duration_hours');
            }
        });
    }
};
