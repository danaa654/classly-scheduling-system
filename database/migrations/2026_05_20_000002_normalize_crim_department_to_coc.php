<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_CODES = ['CRIM', 'Crim', 'crim', 'CRIMINOLOGY', 'Criminology', 'criminology'];

    public function up(): void
    {
        $this->normalizeDepartmentColumns();
        $this->normalizeDepartmentsTable();
    }

    public function down(): void
    {
        // Intentionally no-op: converting COC back to the invalid CRIM code would corrupt current data.
    }

    private function normalizeDepartmentColumns(): void
    {
        foreach (['users', 'faculties', 'subjects', 'schedules', 'faculty_logs'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'department')) {
                continue;
            }

            DB::table($table)
                ->whereIn('department', self::LEGACY_CODES)
                ->update(['department' => 'COC']);
        }
    }

    private function normalizeDepartmentsTable(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        $hasCoc = DB::table('departments')->where('code', 'COC')->exists();
        $legacyRows = DB::table('departments')
            ->whereIn('code', self::LEGACY_CODES)
            ->orWhereIn('name', self::LEGACY_CODES)
            ->get();

        foreach ($legacyRows as $legacy) {
            if ($hasCoc && strtoupper((string) $legacy->code) !== 'COC') {
                DB::table('departments')->where('id', $legacy->id)->delete();

                continue;
            }

            DB::table('departments')
                ->where('id', $legacy->id)
                ->update([
                    'code' => 'COC',
                    'name' => $legacy->name && ! in_array($legacy->name, self::LEGACY_CODES, true)
                        ? $legacy->name
                        : 'College of Criminology',
                ]);

            $hasCoc = true;
        }
    }
};
