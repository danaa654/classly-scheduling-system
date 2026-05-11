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
        });

        if (Schema::hasColumn('schedules', 'status')) {
            DB::table('schedules')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'partial']);
        }

        DB::table('schedules')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereNull('schedules.department')
            ->update([
                'schedules.department' => DB::raw('subjects.department'),
                'schedules.major' => DB::raw('subjects.major'),
                'schedules.year_level' => DB::raw('subjects.year_level'),
            ]);

        if (Schema::hasColumn('subjects', 'faculty_id') && Schema::hasColumn('schedules', 'faculty_id')) {
            DB::table('schedules')
                ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
                ->whereNull('schedules.faculty_id')
                ->whereNotNull('subjects.faculty_id')
                ->update(['schedules.faculty_id' => DB::raw('subjects.faculty_id')]);
        }
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
        });
    }
};
