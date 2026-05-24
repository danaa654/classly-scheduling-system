<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Enforce new EDP code format
 *
 * This migration finalises the EDP code format transition by:
 *
 *  1. Extending edp_code column to varchar(20) — the new format is 10 chars
 *     (e.g. IT-2611001) but a wider column future-proofs longer major names.
 *
 *  2. Backfilling is_legacy_edp / edp_version on any remaining rows that
 *     were not yet classified by the previous migration (000010).
 *
 *  3. Logging a summary of legacy-format rows still present in the database
 *     so administrators know which records need manual conversion.
 *
 * IMPORTANT:
 *   - This migration does NOT delete or forcibly convert legacy rows.
 *     Existing data is preserved (backward compatibility).
 *   - Application-layer validation (EdpCodeService, Subject model, ManageSubjects
 *     Livewire component) already blocks creation of new legacy-format records.
 *   - A MySQL CHECK constraint is intentionally omitted because legacy rows
 *     already in the database would violate it on ALTER TABLE.
 *
 * New format:  [MAJOR]-[YY][SEM][LEVEL][SEQ]   e.g. IT-2611001  (7-digit numeric)
 * Old format:  [MAJOR]-[YY][SEM][LEVEL][SEQ]   e.g. IT-261001   (6-digit numeric)  ← rejected by app
 */
return new class extends Migration
{
    // Regex used inline — no App class dependency (safe in migrations)
    private const REGEX_NEW    = '/^[A-Z]{2,4}-\d{7}$/';
    private const REGEX_LEGACY = '/^[A-Z]{2,4}-\d{6}$/';

    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        // ----------------------------------------------------------------
        // 1. Widen edp_code column if still at the old size
        // ----------------------------------------------------------------
        // The original migration used string('edp_code') which defaults to
        // varchar(255) in Laravel — already wide enough. This explicit change
        // adds a database-visible comment documenting the enforced format.
        Schema::table('subjects', function (Blueprint $table) {
            // Change the column definition to add a meaningful comment.
            // The length stays at 20 — enough for any realistic major+sequence.
            $table->string('edp_code', 20)
                  ->comment('New format: [MAJOR]-[YY][SEM][LEVEL][SEQ] e.g. IT-2611001 (7-digit). Legacy 6-digit format is read-only.')
                  ->change();
        });

        // ----------------------------------------------------------------
        // 2. Ensure is_legacy_edp / edp_version columns exist
        //    (added by migration 000010; guard against out-of-order runs)
        // ----------------------------------------------------------------
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'is_legacy_edp')) {
                $table->boolean('is_legacy_edp')->default(false)->after('workspace_key');
            }

            if (! Schema::hasColumn('subjects', 'edp_version')) {
                $table->unsignedTinyInteger('edp_version')->default(2)->after('is_legacy_edp');
            }
        });

        // ----------------------------------------------------------------
        // 3. Backfill any rows not yet classified
        // ----------------------------------------------------------------
        $this->backfillLegacyFlags();

        // ----------------------------------------------------------------
        // 4. Log legacy row count for administrator awareness
        // ----------------------------------------------------------------
        $legacyCount = DB::table('subjects')
            ->where('is_legacy_edp', true)
            ->count();

        if ($legacyCount > 0) {
            \Log::warning(
                "[Classly] EDP format migration complete. "
                . "{$legacyCount} subject(s) still carry the old 6-digit EDP code format. "
                . "These records are read-only and cannot be re-created with the old format. "
                . "Run the admin EDP migration tool to convert them when convenient."
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        // Revert column comment only — we never drop data in down()
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('edp_code', 20)
                  ->comment('')
                  ->change();
        });
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private function backfillLegacyFlags(): void
    {
        DB::table('subjects')
            ->select('id', 'edp_code')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $code = strtoupper(trim((string) $row->edp_code));

                    if (preg_match(self::REGEX_NEW, $code)) {
                        DB::table('subjects')->where('id', $row->id)->update([
                            'is_legacy_edp' => false,
                            'edp_version'   => 2,
                        ]);
                    } elseif (preg_match(self::REGEX_LEGACY, $code)) {
                        DB::table('subjects')->where('id', $row->id)->update([
                            'is_legacy_edp' => true,
                            'edp_version'   => 1,
                        ]);
                    } else {
                        // Unrecognised format — flag as legacy for safety
                        DB::table('subjects')->where('id', $row->id)->update([
                            'is_legacy_edp' => true,
                            'edp_version'   => 1,
                        ]);
                    }
                }
            });
    }
};