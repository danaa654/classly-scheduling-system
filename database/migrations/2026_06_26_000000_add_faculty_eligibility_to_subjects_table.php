<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor Room Override to Support Faculty Eligibility
 *
 * Adds the three "Eligible Faculty" checkboxes that only take effect when a
 * subject's Room Override is active (see Subject::hasRoomOverride()):
 *
 *   allow_department_faculty        — Department Faculty pool
 *   allow_gened_faculty              — General Education Faculty pool
 *   allow_cross_department_faculty  — Cross Department Faculty pool
 *
 * These columns are read by Faculty::isEligibleForSubject() ONLY when
 * Subject::hasRoomOverride() is true. When Room Override is OFF, they are
 * ignored completely and the existing Major → Department Faculty /
 * Minor → General Education Faculty default routing applies exactly as
 * before — this migration changes no behavior for any existing subject.
 *
 * Defaults intentionally mirror the Eligible Faculty wireframe's starting
 * state (Department + GenEd checked, Cross Department unchecked), so
 * flipping Room Override on for the first time on any subject — including
 * subjects that existed before this column did — produces a sane starting
 * point rather than an empty, dead-end faculty pool.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('allow_department_faculty')->default(true)->after('preferred_room_type');
            $table->boolean('allow_gened_faculty')->default(true)->after('allow_department_faculty');
            $table->boolean('allow_cross_department_faculty')->default(false)->after('allow_gened_faculty');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'allow_department_faculty',
                'allow_gened_faculty',
                'allow_cross_department_faculty',
            ]);
        });
    }
};