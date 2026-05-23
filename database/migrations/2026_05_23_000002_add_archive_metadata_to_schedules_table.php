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
            if (! Schema::hasColumn('schedules', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            if (! Schema::hasColumn('schedules', 'archive_batch')) {
                $table->string('archive_batch', 80)->nullable()->after('archived_at');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_archive_batch')) {
                $table->index('archive_batch', 'idx_schedules_archive_batch');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_term_archive')) {
                $table->index(['semester', 'academic_year', 'is_archived', 'archive_batch'], 'idx_schedules_term_archive');
            }
        });

        $semester = $this->currentSemester();
        $schoolYear = DB::table('settings')->where('key', 'school_year')->value('value') ?: '2026-2027';

        DB::table('schedules')
            ->whereNull('semester')
            ->update(['semester' => $semester]);

        DB::table('schedules')
            ->whereNull('academic_year')
            ->update(['academic_year' => $schoolYear]);

        DB::table('schedules')
            ->whereNull('is_archived')
            ->update(['is_archived' => false]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'idx_schedules_archive_batch')) {
                $table->dropIndex('idx_schedules_archive_batch');
            }

            if (Schema::hasIndex('schedules', 'idx_schedules_term_archive')) {
                $table->dropIndex('idx_schedules_term_archive');
            }

            $columns = collect(['archived_at', 'archive_batch'])
                ->filter(fn (string $column) => Schema::hasColumn('schedules', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function currentSemester(): string
    {
        $semester = strtolower((string) (DB::table('settings')->where('key', 'semester')->value('value') ?: '1st'));

        return match ($semester) {
            '2', '2nd', 'second', 'second semester', '2nd semester' => '2nd',
            'summer', 'summer semester' => 'Summer',
            default => '1st',
        };
    }
};
