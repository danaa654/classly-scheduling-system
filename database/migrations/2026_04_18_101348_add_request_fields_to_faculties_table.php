<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            // Add scheduling-related fields if they don't exist
            if (!Schema::hasColumn('faculties', 'employment_type')) {
                $table->string('employment_type')->default('Full-time')->after('department');
            }

            if (!Schema::hasColumn('faculties', 'teaching_specialization')) {
                $table->string('teaching_specialization')->default('Both')->after('employment_type');
            }

            if (!Schema::hasColumn('faculties', 'max_units')) {
                $table->integer('max_units')->default(21)->after('teaching_specialization');
            }

            // Ensure rejection_reason exists
            if (!Schema::hasColumn('faculties', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('max_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            if (Schema::hasColumn('faculties', 'employment_type')) {
                $table->dropColumn('employment_type');
            }
            if (Schema::hasColumn('faculties', 'teaching_specialization')) {
                $table->dropColumn('teaching_specialization');
            }
            if (Schema::hasColumn('faculties', 'max_units')) {
                $table->dropColumn('max_units');
            }
            if (Schema::hasColumn('faculties', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
