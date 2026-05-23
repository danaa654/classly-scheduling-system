<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasIndex('subjects', 'subjects_edp_code_unique')) {
                $table->dropUnique('subjects_edp_code_unique');
            }

            if (! Schema::hasColumn('subjects', 'school_year')) {
                $table->string('school_year', 20)->nullable()->after('academic_year');
            }

            if (! Schema::hasColumn('subjects', 'workspace_key')) {
                $table->string('workspace_key', 80)->nullable()->after('archived_at');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'school_year')) {
                $table->string('school_year', 20)->nullable()->after('academic_year');
            }

            if (! Schema::hasColumn('schedules', 'workspace_key')) {
                $table->string('workspace_key', 80)->nullable()->after('archived_at');
            }
        });

        $this->backfillWorkspaceColumns('subjects');
        $this->backfillWorkspaceColumns('schedules');

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasIndex('subjects', 'idx_subjects_workspace_key')) {
                $table->index(['workspace_key', 'is_archived'], 'idx_subjects_workspace_key');
            }

            if (! Schema::hasIndex('subjects', 'idx_subjects_edp_workspace')) {
                $table->index(['major', 'semester', 'school_year', 'year_level', 'edp_code'], 'idx_subjects_edp_workspace');
            }
        });

        if ($this->hasWorkspaceEdpDuplicates()) {
            throw new RuntimeException('Duplicate subject EDP codes already exist inside the same semester workspace. Clean those rows before adding the workspace unique index.');
        }

        if (! Schema::hasIndex('subjects', 'uniq_subjects_edp_workspace')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique(['edp_code', 'school_year', 'semester'], 'uniq_subjects_edp_workspace');
            });
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasIndex('schedules', 'idx_schedules_workspace_key')) {
                $table->index(['workspace_key', 'is_archived'], 'idx_schedules_workspace_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasIndex('subjects', 'uniq_subjects_edp_workspace')) {
                $table->dropUnique('uniq_subjects_edp_workspace');
            }

            if (Schema::hasIndex('subjects', 'idx_subjects_workspace_key')) {
                $table->dropIndex('idx_subjects_workspace_key');
            }

            if (Schema::hasIndex('subjects', 'idx_subjects_edp_workspace')) {
                $table->dropIndex('idx_subjects_edp_workspace');
            }
        });

        if (! $this->hasGlobalEdpDuplicates() && ! Schema::hasIndex('subjects', 'subjects_edp_code_unique')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique('edp_code', 'subjects_edp_code_unique');
            });
        }

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'idx_schedules_workspace_key')) {
                $table->dropIndex('idx_schedules_workspace_key');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $columns = collect(['workspace_key', 'school_year'])
                ->filter(fn (string $column) => Schema::hasColumn('subjects', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            $columns = collect(['workspace_key', 'school_year'])
                ->filter(fn (string $column) => Schema::hasColumn('schedules', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function backfillWorkspaceColumns(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $currentSemester = $this->currentSemester();
        $currentSchoolYear = DB::table('settings')->where('key', 'school_year')->value('value') ?: '2026-2027';

        DB::table($table)
            ->whereNull('semester')
            ->update(['semester' => $currentSemester]);

        DB::table($table)
            ->whereNull('academic_year')
            ->update(['academic_year' => $currentSchoolYear]);

        DB::table($table)
            ->whereNull('school_year')
            ->update(['school_year' => DB::raw('academic_year')]);

        DB::table($table)
            ->whereNull('is_archived')
            ->update(['is_archived' => false]);

        DB::table($table)
            ->select('id', 'semester', 'school_year', 'academic_year')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $currentSemester, $currentSchoolYear) {
                foreach ($rows as $row) {
                    $semester = $this->normalizeSemester($row->semester ?? $currentSemester);
                    $schoolYear = $row->school_year ?: $row->academic_year ?: $currentSchoolYear;

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'semester' => $semester,
                            'school_year' => $schoolYear,
                            'academic_year' => $row->academic_year ?: $schoolYear,
                            'workspace_key' => Setting::workspaceKey($schoolYear, $semester),
                        ]);
                }
            });
    }

    private function hasWorkspaceEdpDuplicates(): bool
    {
        return DB::table('subjects')
            ->select('edp_code', 'school_year', 'semester')
            ->groupBy('edp_code', 'school_year', 'semester')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function hasGlobalEdpDuplicates(): bool
    {
        return DB::table('subjects')
            ->select('edp_code')
            ->groupBy('edp_code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function currentSemester(): string
    {
        return $this->normalizeSemester(DB::table('settings')->where('key', 'semester')->value('value') ?: '1st');
    }

    private function normalizeSemester(?string $semester): string
    {
        $semester = strtolower(trim((string) $semester));

        return match ($semester) {
            '2', '2nd', 'second', 'second semester', '2nd semester' => '2nd',
            'summer', 'summer semester' => 'Summer',
            default => '1st',
        };
    }
};
