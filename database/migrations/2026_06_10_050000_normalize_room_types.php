<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize room types to consistent uppercase values (LECTURE / LAB)
     * 
     * This migration ensures all room type values are stored as either:
     *   - LECTURE (for classroom/lecture spaces)
     *   - LAB (for laboratory/specialized spaces)
     * 
     * Previously stored values like 'Lecture', 'Lab', 'Laboratory', etc.
     * will be normalized to match the expected format.
     */
    public function up(): void
    {
        // Normalize to LECTURE
        DB::table('rooms')
            ->where('type', 'Lecture')
            ->orWhere('type', 'lecture')
            ->update(['type' => 'LECTURE']);

        // Normalize to LAB
        DB::table('rooms')
            ->whereIn('type', ['Lab', 'lab', 'Laboratory', 'laboratory', 'LAB'])
            ->update(['type' => 'LAB']);
    }

    public function down(): void
    {
        // Reverse normalization if needed
        // This restores values to their original mixed-case forms
        // Note: This is a lossy operation if the original casing wasn't preserved
        DB::table('rooms')
            ->where('type', 'LECTURE')
            ->update(['type' => 'Lecture']);

        DB::table('rooms')
            ->where('type', 'LAB')
            ->update(['type' => 'Lab']);
    }
};