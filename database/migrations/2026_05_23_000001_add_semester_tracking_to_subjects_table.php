<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'semester')) {
                $table->string('semester', 50)->nullable()->after('edp_code');
            }

            if (! Schema::hasColumn('subjects', 'academic_year')) {
                $table->string('academic_year', 20)->nullable()->after('semester');
            }

            if (! Schema::hasColumn('subjects', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('academic_year');
            }

            if (! Schema::hasColumn('subjects', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            if (! Schema::hasColumn('subjects', 'copied_from_id')) {
                $table->foreignId('copied_from_id')
                    ->nullable()
                    ->after('archived_at')
                    ->constrained('subjects')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('subjects', 'archive_batch')) {
                $table->string('archive_batch', 80)->nullable()->after('copied_from_id');
            }

            if (! Schema::hasIndex('subjects', 'idx_subjects_active_term')) {
                $table->index(['semester', 'academic_year', 'is_archived'], 'idx_subjects_active_term');
            }

            if (! Schema::hasIndex('subjects', 'idx_subjects_archive_batch')) {
                $table->index('archive_batch', 'idx_subjects_archive_batch');
            }

            if (! Schema::hasIndex('subjects', 'idx_subjects_edp_generation')) {
                $table->index(['major', 'year_level', 'edp_code'], 'idx_subjects_edp_generation');
            }
        });

        $semester = $this->currentSemester();
        $schoolYear = DB::table('settings')->where('key', 'school_year')->value('value') ?: '2026-2027';

        DB::table('subjects')
            ->whereNull('semester')
            ->update(['semester' => $semester]);

        DB::table('subjects')
            ->whereNull('academic_year')
            ->update(['academic_year' => $schoolYear]);

        DB::table('subjects')
            ->whereNull('is_archived')
            ->update(['is_archived' => false]);
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasIndex('subjects', 'idx_subjects_active_term')) {
                $table->dropIndex('idx_subjects_active_term');
            }

            if (Schema::hasIndex('subjects', 'idx_subjects_archive_batch')) {
                $table->dropIndex('idx_subjects_archive_batch');
            }

            if (Schema::hasIndex('subjects', 'idx_subjects_edp_generation')) {
                $table->dropIndex('idx_subjects_edp_generation');
            }

            if (Schema::hasColumn('subjects', 'copied_from_id')) {
                $table->dropConstrainedForeignId('copied_from_id');
            }

            $table->dropColumn([
                'semester',
                'academic_year',
                'is_archived',
                'archived_at',
                'archive_batch',
            ]);
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
