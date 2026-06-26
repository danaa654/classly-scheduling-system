<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `validation_notes` to the schedules table so retrieved schedules
 * that fail compatibility checks can store their "Needs Review" reasons
 * inline — no separate pivot table needed.
 *
 * The `status` column already exists as a varchar; the new value
 * 'needs_review' is purely additive and requires no schema change on
 * databases that use unconstrained varchars (MySQL/MariaDB/SQLite).
 *
 * If your project uses a CHECK constraint or a DB-level enum on `status`,
 * you may need to widen that constraint here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'validation_notes')) {
                $table->json('validation_notes')
                    ->nullable()
                    ->after('status')
                    ->comment('Populated during semester retrieval when a schedule is flagged needs_review. Contains array of human-readable reason strings.');
            }

            if (! Schema::hasIndex('schedules', 'idx_schedules_needs_review')) {
                $table->index(
                    ['status', 'semester', 'academic_year'],
                    'idx_schedules_needs_review'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasIndex('schedules', 'idx_schedules_needs_review')) {
                $table->dropIndex('idx_schedules_needs_review');
            }

            if (Schema::hasColumn('schedules', 'validation_notes')) {
                $table->dropColumn('validation_notes');
            }
        });
    }
};