<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add faculty_id column if it doesn't exist
        if (!Schema::hasColumn('subjects', 'faculty_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('faculty_id')
                    ->nullable()
                    ->after('meetings_per_week')
                    ->constrained('faculties')
                    ->onDelete('set null');
            });
        }

        // FIX: Map college codes to department codes
        // This converts CCS → IT, CTE → ED, SHTM → HM/TM, COC → FB/LD/QD
        DB::statement("
            UPDATE subjects 
            SET department = CASE 
                WHEN department = 'CCS' THEN 'IT'
                WHEN department = 'CTE' THEN 'ED'
                WHEN department = 'SHTM' AND major = 'TM' THEN 'TM'
                WHEN department = 'SHTM' AND major = 'HM' THEN 'HM'
                WHEN department = 'SHTM' THEN 'HM'
                WHEN department = 'COC' AND major IN ('FB', 'LD', 'QD') THEN major
                WHEN department = 'COC' THEN 'QD'
                ELSE department
            END
            WHERE department IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'faculty_id')) {
                $table->dropForeign(['faculty_id']);
            }
        });
    }
};
