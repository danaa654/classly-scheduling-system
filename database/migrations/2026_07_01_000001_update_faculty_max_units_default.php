<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Updates the faculties table to reflect the new unit-load policy:
 *
 *   Base load  = 30 units  (was 21 / hardcoded 36 in PHP)
 *   Hard cap   = 40 units  (enforced in Faculty::HARD_CAP_UNITS)
 *   Overload   = granted in +3 increments via FacultyLoading::adjustOverloadUnits()
 *
 * The max_units column already exists — this migration only:
 *   1. Changes the column default to 30.
 *   2. Bumps existing rows that still hold the old default (21 or 36) up to 30.
 *      Rows with a value ≥ 30 (manually set overloads) are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Change column default ──────────────────────────────────────────
        Schema::table('faculties', function (Blueprint $table) {
            $table->integer('max_units')->default(30)->change();
        });

        // ── 2. Normalise legacy rows ──────────────────────────────────────────
        // Rows created before this migration used the old default of 21,
        // or were hard-floored at 36 by the PHP model. Bring them all up to 30
        // so the new Faculty::effectiveMaxUnits() returns a consistent baseline.
        DB::table('faculties')
            ->where('max_units', '<', 30)
            ->update(['max_units' => 30]);
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->integer('max_units')->default(21)->change();
        });

        // We intentionally do NOT roll back individual row values — the old
        // default (21) was effectively overridden at the model layer anyway,
        // so reverting rows would create false data.
    }
};