<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'edp_code')) {
                $table->string('edp_code', 30)->nullable()->after('id')
                    ->comment('Unique offering code, e.g. IT-271001');
            }

            if (! Schema::hasColumn('schedules', 'semester')) {
                $table->string('semester', 50)->nullable()->after('edp_code')
                    ->comment('e.g. "1st" | "2nd" | "Summer"');
            }

            if (! Schema::hasColumn('schedules', 'academic_year')) {
                $table->string('academic_year', 20)->nullable()->after('semester')
                    ->comment('e.g. "2026-2027"');
            }

            if (! Schema::hasColumn('schedules', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('status');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_active_term')) {
                $table->index(['semester', 'academic_year', 'is_archived'], 'idx_schedules_active_term');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_edp_code')) {
                $table->index('edp_code', 'idx_schedules_edp_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'idx_schedules_active_term')) {
                $table->dropIndex('idx_schedules_active_term');
            }

            if (Schema::hasIndex('schedules', 'idx_schedules_edp_code')) {
                $table->dropIndex('idx_schedules_edp_code');
            }

            $columns = collect(['edp_code', 'semester', 'academic_year', 'is_archived'])
                ->filter(fn (string $column) => Schema::hasColumn('schedules', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
