<?php

namespace App\Services;

/**
 * EdpCodeService
 *
 * Single source of truth for EDP code generation, validation, parsing,
 * and legacy detection across the entire Classly application.
 *
 * ============================================================
 * OFFICIAL FORMAT  (enforced for ALL new records)
 * ============================================================
 *
 *   [MAJOR]-[YY][SEM][LEVEL][SEQ]
 *
 *   Segment      Length   Example   Meaning
 *   ─────────────────────────────────────────────────────────
 *   MAJOR        2–4 chr  IT        Department major code
 *   -            1        -         Literal dash separator
 *   YY           2 dig    26        Last 2 digits of school-year start
 *                                   (2026-2027 → "26")
 *   SEM          1 dig    1         Semester:  1=1st · 2=2nd · 3=Summer
 *   LEVEL        1 dig    1         Year level: 1=1st · 2=2nd · 3=3rd · 4=4th
 *   SEQ          3 dig    001       Zero-padded sequence, per-prefix counter
 *   ─────────────────────────────────────────────────────────
 *   Full example: IT-2611001
 *
 * ============================================================
 * WORKED EXAMPLES
 * ============================================================
 *
 *   1st Sem · 1st Year → IT-2611001, IT-2611002, IT-2611003 …
 *   2nd Sem · 1st Year → IT-2621001, IT-2621002 …
 *   Summer  · 1st Year → IT-2631001 …
 *   1st Sem · 2nd Year → IT-2612001, IT-2612002 …
 *   1st Sem · 3rd Year → IT-2613001 …
 *   1st Sem · 4th Year → IT-2614001 …
 *
 * ============================================================
 * LEGACY FORMAT  (read-only — existing DB rows only)
 * ============================================================
 *
 *   6-digit numeric part, e.g.  IT-261001
 *   ← REJECTED for any new creation or CSV import.
 *
 * ============================================================
 * RULE SUMMARY
 * ============================================================
 *   • New records MUST use the 7-digit format.
 *   • Existing legacy rows may stay but cannot be re-created.
 *   • Editing a legacy code requires converting to new format first.
 *   • validate() / isValidForCreation() enforce the new format ONLY.
 */
class EdpCodeService
{
    // ---------------------------------------------------------------
    // Regex constants
    // ---------------------------------------------------------------

    /**
     * New 7-digit format: e.g. IT-2611001
     * Major: 2–4 uppercase letters; numeric part: exactly 7 digits.
     */
    public const REGEX_NEW = '/^[A-Z]{2,4}-\d{7}$/';

    /**
     * Legacy 6-digit format: e.g. IT-261001
     * Used for detection/migration only — NOT accepted for new records.
     */
    public const REGEX_LEGACY = '/^[A-Z]{2,4}-\d{6}$/';

    /** Canonical example shown in all user-facing messages. */
    public const EXAMPLE = 'IT-2611001';

    // ---------------------------------------------------------------
    // Semester digit map  (canonical lowercase key → digit string)
    // ---------------------------------------------------------------

    /**
     * Every plausible semester string that may arrive from the DB,
     * a CSV column, or a UI dropdown — all mapped to the single digit
     * used in EDP codes.
     *
     *   "1" → 1st Semester
     *   "2" → 2nd Semester
     *   "3" → Summer
     */
    private const SEMESTER_MAP = [
        // ── 1st Semester ─────────────────────────────────────
        '1'                 => '1',
        '1st'               => '1',
        'first'             => '1',
        '1st semester'      => '1',
        'first semester'    => '1',
        'semester 1'        => '1',
        'sem 1'             => '1',
        'sem1'              => '1',
        's1'                => '1',

        // ── 2nd Semester ─────────────────────────────────────
        '2'                 => '2',
        '2nd'               => '2',
        'second'            => '2',
        '2nd semester'      => '2',
        'second semester'   => '2',
        'semester 2'        => '2',
        'sem 2'             => '2',
        'sem2'              => '2',
        's2'                => '2',

        // ── Summer ────────────────────────────────────────────
        '3'                 => '3',
        'summer'            => '3',
        'summer semester'   => '3',
        'summer term'       => '3',
        'sem 3'             => '3',
        'sem3'              => '3',
        's3'                => '3',
        'semester 3'        => '3',
    ];

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------

    /**
     * Build the EDP code prefix for the given combination.
     *
     * Returns the prefix WITHOUT the 3-digit sequence so callers can
     * run  LIKE "$prefix%"  queries or append the sequence themselves.
     *
     * Example:
     *   major=IT, academicYear=2026-2027, semester="1st", yearLevel=1
     *   → "IT-2611"
     *
     * @param  string      $major        e.g. "IT", "FB", "HM"
     * @param  string      $academicYear e.g. "2026-2027"
     * @param  string      $semester     Any supported semester string (see SEMESTER_MAP)
     * @param  int|string  $yearLevel    1–4 or string such as "1st Year"
     */
    public function prefix(
        string     $major,
        string     $academicYear,
        string     $semester,
        int|string $yearLevel
    ): string {
        $major = strtoupper(trim($major));
        $yy    = $this->yearPrefix($academicYear);
        $sem   = $this->semesterDigit($semester);
        $level = $this->yearLevelDigit($yearLevel);

        return "{$major}-{$yy}{$sem}{$level}";
    }

    /**
     * Generate the NEXT available EDP code in the new format.
     *
     * $existingCodes must contain all edp_code values already present in
     * the same workspace for the same major + year level.
     *
     * @param  string      $major
     * @param  string      $academicYear
     * @param  string      $semester
     * @param  int|string  $yearLevel
     * @param  string[]    $existingCodes
     * @return string      e.g. "IT-2611003"
     */
    public function generate(
        string     $major,
        string     $academicYear,
        string     $semester,
        int|string $yearLevel,
        array      $existingCodes = []
    ): string {
        $prefix  = $this->prefix($major, $academicYear, $semester, $yearLevel);
        $maxSeq  = 0;
        $upperCodes = array_map('strtoupper', $existingCodes);

        foreach ($upperCodes as $code) {
            $code = strtoupper(trim((string) $code));
            if (str_starts_with($code, $prefix)) {
                $seq = substr($code, strlen($prefix));
                if (ctype_digit($seq)) {
                    $maxSeq = max($maxSeq, (int) $seq);
                }
            }
        }

        // Advance until we find a sequence not already taken
        do {
            $maxSeq++;
            $candidate = $prefix . str_pad((string) $maxSeq, 3, '0', STR_PAD_LEFT);
        } while (in_array($candidate, $upperCodes, true));

        return $candidate;
    }

    /**
     * Return true if $edpCode matches the new 7-digit format.
     *
     * This is the canonical check for "is this a valid EDP code?"
     *
     * Valid:   IT-2611001  FB-2621001  HM-2614001
     * Invalid: IT-261001 (legacy 6-digit)
     *          IT2611001  (missing dash)
     *          IT-26A1001 (alpha in numeric part)
     */
    public function isNew(string $edpCode): bool
    {
        return (bool) preg_match(self::REGEX_NEW, strtoupper(trim($edpCode)));
    }

    /**
     * Alias for isNew() — used in creation/import validation contexts.
     */
    public function isValidForCreation(string $edpCode): bool
    {
        return $this->isNew($edpCode);
    }

    /**
     * Return true if $edpCode matches the legacy 6-digit format.
     *
     * Legacy codes are tolerated for existing DB rows ONLY.
     * They must NOT be used when creating or importing new subjects.
     */
    public function isLegacy(string $edpCode): bool
    {
        $code = strtoupper(trim($edpCode));

        return (bool) preg_match(self::REGEX_LEGACY, $code)
            && ! (bool) preg_match(self::REGEX_NEW, $code);
    }

    /**
     * Strict validation: ONLY the new 7-digit format passes.
     *
     * Breaking change from the old implementation: previously accepted
     * both formats. Now rejects legacy codes.
     */
    public function validate(string $edpCode): bool
    {
        return $this->isNew($edpCode);
    }

    /**
     * Parse a new-format EDP code into its component parts.
     * Returns null for legacy codes, invalid codes, or empty strings.
     *
     * @return array{
     *     major: string,
     *     year_prefix: string,
     *     semester_digit: string,
     *     year_level: int,
     *     sequence: int,
     *     academic_year: string,
     *     semester: string
     * }|null
     */
    public function parse(string $edpCode): ?array
    {
        $code = strtoupper(trim($edpCode));

        // Pattern: MAJOR - YY SEM LEVEL SEQ(3)
        if (! preg_match('/^([A-Z]{2,4})-(\d{2})(\d)(\d)(\d{3})$/', $code, $m)) {
            return null;
        }

        [, $major, $yy, $semDigit, $level, $seq] = $m;

        // Reverse-map digit → semester label
        $semLabel = match ($semDigit) {
            '2'     => '2nd',
            '3'     => 'Summer',
            default => '1st',
        };

        $startYear = (int) ('20' . $yy);

        return [
            'major'          => $major,
            'year_prefix'    => $yy,
            'semester_digit' => $semDigit,
            'year_level'     => (int) $level,
            'sequence'       => (int) $seq,
            'academic_year'  => "{$startYear}-" . ($startYear + 1),
            'semester'       => $semLabel,
        ];
    }

    /**
     * Return a human-readable format label — useful in admin UIs and logs.
     *
     * @return 'new'|'legacy'|'invalid'
     */
    public function formatLabel(string $edpCode): string
    {
        if ($this->isNew($edpCode))    return 'new';
        if ($this->isLegacy($edpCode)) return 'legacy';

        return 'invalid';
    }

    // ---------------------------------------------------------------
    // Semester-workspace validation
    // ---------------------------------------------------------------

    /**
     * Extract the semester digit embedded inside a valid EDP code.
     *
     * Format:  [MAJOR]-[YY][SEM][LEVEL][SEQ]
     *   Numeric part after dash: position 0-1 = YY, position 2 = SEM, …
     *
     * Examples:
     *   IT-2611001 → "1"  (1st Semester)
     *   IT-2621001 → "2"  (2nd Semester)
     *   IT-2631001 → "3"  (Summer)
     *
     * Returns null if the code does not match the new 7-digit format.
     */
    public function getSemesterDigit(string $edpCode): ?string
    {
        $code = strtoupper(trim($edpCode));

        // Must be valid new format first
        if (! preg_match('/^[A-Z]{2,4}-(\d{7})$/', $code, $m)) {
            return null;
        }

        // Numeric part: YYSEM LEVEL SEQ → index 2 is the semester digit
        return $m[1][2];
    }

    /**
     * Convert an active-workspace semester string to the EDP digit it
     * should produce.  Delegates to semesterDigit() — same mapping.
     *
     * Examples:
     *   "1st"    → "1"
     *   "2nd"    → "2"
     *   "Summer" → "3"
     */
    public function getWorkspaceSemesterDigit(string $workspaceSemester): string
    {
        return $this->semesterDigit($workspaceSemester);
    }

    /**
     * Return true if the semester digit embedded in $edpCode matches
     * the digit expected for $workspaceSemester.
     *
     * Always returns false for legacy or invalid codes (they fail the
     * format check first, so this is a belt-and-suspenders guard).
     *
     * @param  string  $edpCode           e.g. "IT-2621001"
     * @param  string  $workspaceSemester  e.g. "2nd", "1st", "Summer"
     */
    public function validateSemesterMatch(string $edpCode, string $workspaceSemester): bool
    {
        $codeDigit      = $this->getSemesterDigit($edpCode);
        $workspaceDigit = $this->getWorkspaceSemesterDigit($workspaceSemester);

        return $codeDigit !== null && $codeDigit === $workspaceDigit;
    }

    /**
     * Build a user-friendly error message for a semester-digit mismatch.
     *
     * Example output (workspace = 2nd, code digit = 1):
     *   "Row 4: EDP code "IT-2611001" has semester digit "1" (1st Semester)
     *    but the active workspace is 2nd Semester.
     *    Expected format for this workspace: IT-2621001"
     *
     * @param  string    $edpCode
     * @param  string    $workspaceSemester  e.g. "2nd", "Summer"
     * @param  int|null  $csvRow            When set, prefixes with "Row N: "
     */
    public function semesterMismatchMessage(
        string  $edpCode,
        string  $workspaceSemester,
        ?int    $csvRow = null
    ): string {
        $code           = strtoupper(trim($edpCode));
        $codeDigit      = $this->getSemesterDigit($code) ?? '?';
        $workspaceDigit = $this->getWorkspaceSemesterDigit($workspaceSemester);
        $prefix         = $csvRow !== null ? "Row {$csvRow}: " : '';

        // Human-readable labels for the digits
        $digitLabel = static function (string $d): string {
            return match ($d) {
                '2'     => '2nd Semester',
                '3'     => 'Summer',
                default => '1st Semester',
            };
        };

        $codeLabel      = $digitLabel($codeDigit);
        $workspaceLabel = $digitLabel($workspaceDigit);

        // Build a concrete corrected example by swapping just the semester digit
        $parsed = $this->parse($code);
        if ($parsed) {
            $correctedExample = $parsed['major'] . '-'
                . $parsed['year_prefix']
                . $workspaceDigit
                . $parsed['year_level']
                . str_pad((string) $parsed['sequence'], 3, '0', STR_PAD_LEFT);
        } else {
            $correctedExample = 'e.g. IT-' . substr($code, strpos($code, '-') + 1, 2)
                . $workspaceDigit . '1001';
        }

        return "{$prefix}EDP code \"{$code}\" has semester digit \"{$codeDigit}\" ({$codeLabel}) "
            . "but the active workspace is {$workspaceLabel}. "
            . "Semester digit must be \"{$workspaceDigit}\" for this workspace. "
            . "Expected format: {$correctedExample}";
    }

    /**
     * Build a user-friendly error message for an invalid EDP code.
     * Includes a contextual hint when the code looks like a legacy format.
     *
     * @param  string    $edpCode
     * @param  int|null  $csvRow  When set, prefixes the message with "Row N: "
     */
    public function validationMessage(string $edpCode, ?int $csvRow = null): string
    {
        $code   = strtoupper(trim($edpCode));
        $prefix = $csvRow !== null ? "Row {$csvRow}: " : '';

        if ($this->isLegacy($code)) {
            return "{$prefix}EDP code \"{$code}\" uses the old 6-digit format. "
                . 'Please update it to the new 7-digit format (e.g. ' . self::EXAMPLE . ').';
        }

        return "{$prefix}Invalid EDP code \"{$code}\". "
            . 'Required format: [MAJOR]-[YY][SEM][LEVEL][SEQ] (e.g. ' . self::EXAMPLE . ').';
    }

    // ---------------------------------------------------------------
    // Helpers — all public so they can be used from models / controllers
    // ---------------------------------------------------------------

    /**
     * Extract the 2-digit year prefix from an academic-year string.
     *
     * "2026-2027" → "26"
     * "2026"      → "26"
     * ""          → last 2 digits of the current year
     */
    public function yearPrefix(string $academicYear): string
    {
        $clean = trim($academicYear);

        // Grab the first 4-digit year found in the string
        if (preg_match('/(\d{4})/', $clean, $m)) {
            return substr($m[1], 2, 2);
        }

        return substr((string) date('Y'), 2, 2);
    }

    /**
     * Convert any semester string to the single digit used in EDP codes.
     *
     * Mapping:
     *   1st Semester (and all aliases) → "1"
     *   2nd Semester (and all aliases) → "2"
     *   Summer       (and all aliases) → "3"
     *
     * Unrecognised strings default to "1" (1st Semester).
     */
    public function semesterDigit(string $semester): string
    {
        $key = strtolower(trim($semester));

        return self::SEMESTER_MAP[$key] ?? '1';
    }

    /**
     * Normalise a year-level value to the single digit used in EDP codes.
     *
     * Accepts:
     *   • integer   1–4  (returned as-is, clamped to 1–4)
     *   • string    "1", "2", "3", "4"
     *   • string    "1st", "2nd", "3rd", "4th"
     *   • string    "1st year", "2nd year", "3rd year", "4th year"
     *   • string    "year 1", "year 2", "year 3", "year 4"
     *   • string    "first year", "second year", "third year", "fourth year"
     *
     * Unrecognised strings fall back to 1.
     */
    public function yearLevelDigit(int|string $yearLevel): int
    {
        if (is_int($yearLevel)) {
            return max(1, min(4, $yearLevel));
        }

        $key = strtolower(trim((string) $yearLevel));

        // Map ordinal/word forms → integer
        $map = [
            '1'           => 1,  '1st'         => 1,  'first'      => 1,
            '1st year'    => 1,  'year 1'       => 1,  'first year' => 1,

            '2'           => 2,  '2nd'         => 2,  'second'     => 2,
            '2nd year'    => 2,  'year 2'       => 2,  'second year'=> 2,

            '3'           => 3,  '3rd'         => 3,  'third'      => 3,
            '3rd year'    => 3,  'year 3'       => 3,  'third year' => 3,

            '4'           => 4,  '4th'         => 4,  'fourth'     => 4,
            '4th year'    => 4,  'year 4'       => 4,  'fourth year'=> 4,
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        // Try extracting the leading digit as a last resort
        if (preg_match('/^(\d)/', $key, $m)) {
            return max(1, min(4, (int) $m[1]));
        }

        return 1; // safe default
    }
}