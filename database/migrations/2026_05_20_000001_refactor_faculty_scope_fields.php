<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            if (! Schema::hasColumn('faculties', 'faculty_scope')) {
                $table->string('faculty_scope')->default('departmental')->after('employment_type');
            }

            if (! Schema::hasColumn('faculties', 'can_teach_minor')) {
                $table->boolean('can_teach_minor')->default(false)->after('faculty_scope');
            }

            if (! Schema::hasColumn('faculties', 'availability')) {
                $table->json('availability')->nullable()->after('max_units');
            }
        });

        $hasTeachingSpecialization = Schema::hasColumn('faculties', 'teaching_specialization');
        $columns = $hasTeachingSpecialization
            ? ['id', 'department', 'teaching_specialization']
            : ['id', 'department'];

        DB::table('faculties')
            ->select($columns)
            ->orderBy('id')
            ->chunkById(200, function ($faculties) use ($hasTeachingSpecialization) {
                foreach ($faculties as $faculty) {
                    $department = strtoupper(trim((string) ($faculty->department ?? '')));
                    $specialization = $hasTeachingSpecialization
                        ? strtolower(trim((string) ($faculty->teaching_specialization ?? '')))
                        : '';

                    $isGenEd = in_array($department, ['GENED', 'GENERAL EDUCATION', 'GENERAL_EDUCATION'], true);

                    DB::table('faculties')
                        ->where('id', $faculty->id)
                        ->update([
                            'department' => $isGenEd ? null : ($faculty->department ?: null),
                            'faculty_scope' => $isGenEd ? 'gened' : 'departmental',
                            'can_teach_minor' => $isGenEd || in_array($specialization, ['minor', 'both'], true),
                        ]);
                }
            });

        DB::table('faculties')
            ->whereNull('faculty_scope')
            ->orWhereNotIn('faculty_scope', ['gened', 'departmental', 'cross_department'])
            ->update(['faculty_scope' => 'departmental']);

        DB::table('faculties')
            ->whereNull('can_teach_minor')
            ->update(['can_teach_minor' => false]);

        Schema::table('faculties', function (Blueprint $table) use ($hasTeachingSpecialization) {
            if (Schema::hasColumn('faculties', 'department')) {
                $table->string('department')->nullable()->change();
            }

            if ($hasTeachingSpecialization && Schema::hasColumn('faculties', 'teaching_specialization')) {
                $table->dropColumn('teaching_specialization');
            }
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            if (! Schema::hasColumn('faculties', 'teaching_specialization')) {
                $table->string('teaching_specialization')->default('Both')->after('employment_type');
            }
        });

        DB::table('faculties')
            ->select('id', 'department', 'faculty_scope', 'can_teach_minor')
            ->orderBy('id')
            ->chunkById(200, function ($faculties) {
                foreach ($faculties as $faculty) {
                    $scope = (string) ($faculty->faculty_scope ?? 'departmental');
                    $canTeachMinor = (bool) ($faculty->can_teach_minor ?? false);

                    DB::table('faculties')
                        ->where('id', $faculty->id)
                        ->update([
                            'department' => $scope === 'gened'
                                ? ($faculty->department ?: 'GENED')
                                : ($faculty->department ?: 'Unassigned'),
                            'teaching_specialization' => $canTeachMinor ? 'Both' : 'Major',
                        ]);
                }
            });

        Schema::table('faculties', function (Blueprint $table) {
            if (Schema::hasColumn('faculties', 'faculty_scope')) {
                $table->dropColumn('faculty_scope');
            }

            if (Schema::hasColumn('faculties', 'can_teach_minor')) {
                $table->dropColumn('can_teach_minor');
            }

            if (Schema::hasColumn('faculties', 'department')) {
                $table->string('department')->nullable(false)->change();
            }
        });
    }
};
