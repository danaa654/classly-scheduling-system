<?php

namespace App\Models;

use App\Services\EdpCodeService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{    
    use HasFactory;
    
    protected $fillable = [
        'edp_code',
        'subject_code',
        'section',
        'description',
        'major',
        'year_level',
        'department',
        'units',
        'duration_hours',
        'type',
        'subject_type',
        'requires_lab',
        'preferred_room_type',
        'preferred_room_id',
        // Eligible Faculty (only consulted when hasRoomOverride() is true —
        // see "Refactor Room Override to Support Faculty Eligibility")
        'allow_department_faculty',
        'allow_gened_faculty',
        'allow_cross_department_faculty',
        'specialization',
        'meetings_per_week',
        'faculty_id',
        'semester',
        'school_year',
        'academic_year',
        'is_archived',
        'archived_at',
        'workspace_key',
        'copied_from_id',
        'archive_batch',
        // Legacy-tracking columns (added by migration 000010)
        'is_legacy_edp',
        'edp_version',
        // Off-campus / no-room subjects (OJT, Practicum, Student Teaching, etc.)
        'is_practicum',
    ];

    protected $casts = [
        'units'            => 'integer',
        'year_level'       => 'integer',
        'duration_hours'   => 'float',
        'meetings_per_week'=> 'integer',
        'requires_lab'     => 'boolean',
        'allow_department_faculty'       => 'boolean',
        'allow_gened_faculty'             => 'boolean',
        'allow_cross_department_faculty' => 'boolean',
        'is_archived'      => 'boolean',
        'archived_at'      => 'datetime',
        'is_legacy_edp'    => 'boolean',
        'edp_version'      => 'integer',
        'is_practicum'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Subject $subject) {
            // ============================================================
            // 1. EDP CODE FORMAT ENFORCEMENT
            // ============================================================
            // Enforce the new 7-digit EDP format on every create OR whenever
            // the edp_code column is being changed on an existing record.
            //
            // New format: [MAJOR]-[YY][SEM][LEVEL][SEQ]  e.g. IT-2611001
            // Old format: IT-261001  — REJECTED for all new operations.
            //
            // Existing rows with legacy codes are allowed to remain in the
            // database (is_legacy_edp=true) but CANNOT be re-saved with
            // a legacy code; the user must supply the new format first.
            // ============================================================
            if (filled($subject->edp_code)) {
                $isNewRecord    = ! $subject->exists;
                $edpCodeChanged = $subject->isDirty('edp_code');

                if ($isNewRecord || $edpCodeChanged) {
                    $edpService = app(EdpCodeService::class);
                    $clean      = strtoupper(trim((string) $subject->edp_code));

                    if (! $edpService->isNew($clean)) {
                        $hint = $edpService->isLegacy($clean)
                            ? " The old 6-digit format (e.g. IT-261001) is no longer accepted."
                            : '';

                        throw new \InvalidArgumentException(
                            "EDP code \"{$clean}\" is not in the required format.{$hint} "
                            . 'Use the 7-digit format: [MAJOR]-[YY][SEM][LEVEL][SEQ] (e.g. IT-2611001).'
                        );
                    }

                    // Keep the legacy-tracking columns in sync automatically.
                    $subject->is_legacy_edp = false;
                    $subject->edp_version   = 2;
                }
            }

            // ============================================================
            // 2. SEMESTER / SCHOOL-YEAR NORMALISATION (unchanged)
            // ============================================================
            $period = Setting::getAcademicPeriod();

            if (blank($subject->semester)) {
                $subject->semester = $period['semester'];
            }

            $subject->semester = Setting::normalizeSemester($subject->semester);

            $schoolYear   = $subject->school_year   ?: $subject->academic_year ?: $period['school_year'];
            $academicYear = $subject->academic_year ?: $schoolYear;

            if ($subject->isDirty('academic_year') && ! $subject->isDirty('school_year')) {
                $schoolYear = $academicYear;
            }

            if ($subject->isDirty('school_year') && ! $subject->isDirty('academic_year')) {
                $academicYear = $schoolYear;
            }

            if ($schoolYear !== $academicYear) {
                $academicYear = $schoolYear;
            }

            $subject->school_year   = $schoolYear;
            $subject->academic_year = $academicYear;

            if (blank($subject->academic_year)) {
                $subject->academic_year = $period['school_year'];
            }

            if ($subject->is_archived === null) {
                $subject->is_archived = false;
            }

            $subject->workspace_key = Setting::workspaceKey($subject->school_year, $subject->semester);
        });
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Get schedules associated with this subject.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the faculty member assigned to this subject.
     */
    public function faculty(): BelongsTo
{
    return $this->belongsTo(Faculty::class);
}

    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copied_from_id');
    }

    public function copiedSubjects(): HasMany
    {
        return $this->hasMany(self::class, 'copied_from_id');
    }

    // ============================================================
    // ATTRIBUTE CASTING (text sanitisation)
    // ============================================================

    protected function edpCode(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function subjectCode(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function description(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function section(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function major(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function department(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function type(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function specialization(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function preferredRoomType(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    private static function cleanText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00C2}", "\u{00A0}", "\xC2\xA0"], ' ', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    // ============================================================
    // SCOPES — Filtering & Querying
    // ============================================================

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByMajor($query, $major)
    {
        return $query->where('major', $major);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year_level', (int) $year);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('subject_code', 'like', "%{$term}%")
            ->orWhere('description',  'like', "%{$term}%")
            ->orWhere('edp_code',     'like', "%{$term}%");
    }

    public function scopeCurrentSemester($query)
    {
        $period = Setting::getAcademicPeriod();

        return $query->activeTerm($period['semester'], $period['school_year']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForWorkspace($query, ?string $semester = null, ?string $schoolYear = null)
    {
        $period     = Setting::getAcademicPeriod();
        $semester   ??= $period['semester'];
        $schoolYear ??= $period['school_year'];
        $semester   = Setting::normalizeSemester($semester);
        $workspaceKey = Setting::workspaceKey($schoolYear, $semester);

        return $query->where('semester', $semester)
            ->where(function ($query) use ($schoolYear, $workspaceKey) {
                $query->where('workspace_key', $workspaceKey)
                    ->orWhere('school_year',   $schoolYear)
                    ->orWhere('academic_year', $schoolYear);
            });
    }

    public function scopeActiveWorkspace($query)
    {
        $period = Setting::getAcademicPeriod();

        return $query->activeTerm($period['semester'], $period['school_year']);
    }

    public function scopeActiveTerm($query, ?string $semester = null, ?string $schoolYear = null)
    {
        return $query->forWorkspace($semester, $schoolYear)
            ->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeArchivedWorkspace($query, ?string $semester = null, ?string $schoolYear = null)
    {
        return $query->forWorkspace($semester, $schoolYear)
            ->where('is_archived', true);
    }

    // ============================================================
    // EDP CODE GENERATION
    // ============================================================

    /**
     * Auto-generate the next available EDP code in the new 7-digit format.
     *
     * Format: [MAJOR]-[YY][SEM][LEVEL][SEQ]
     *
     *   Segment  Example  Meaning
     *   ───────────────────────────────────────────────────────
     *   MAJOR    IT       Department major code (2–4 letters)
     *   YY       26       Last 2 digits of school-year start
     *                     (2026-2027 → "26")
     *   SEM      1        Semester digit: 1=1st · 2=2nd · 3=Summer
     *   LEVEL    1        Year level:     1=1st · 2=2nd · 3=3rd · 4=4th
     *   SEQ      001      3-digit zero-padded auto-increment per prefix
     *
     * Worked examples:
     *   IT + 2026-2027 + 1st + Year 1 → IT-2611001, IT-2611002, …
     *   IT + 2026-2027 + 2nd + Year 1 → IT-2621001, IT-2621002, …
     *   IT + 2026-2027 + Summer + Yr1 → IT-2631001, …
     *   IT + 2026-2027 + 1st + Year 2 → IT-2612001, IT-2612002, …
     *
     * @param  string       $major           e.g. "IT", "FB", "HM"
     * @param  int          $yearLevel        1–4
     * @param  string|null  $schoolYear       e.g. "2026-2027" (defaults to active workspace)
     * @param  string|null  $semester         "1st"|"2nd"|"Summer" (defaults to active workspace)
     * @param  int|null     $ignoreSubjectId  Exclude this subject's existing code from sequence tracking
     */
    public static function generateEdpCode(
        string  $major,
        int     $yearLevel,
        ?string $schoolYear       = null,
        ?string $semester         = null,
        ?int    $ignoreSubjectId  = null
    ): string {
        $period     = Setting::getAcademicPeriod();
        $schoolYear = $schoolYear ?? $period['school_year'];
        $semester   = Setting::normalizeSemester($semester ?? $period['semester']);
        $major      = strtoupper(trim($major ?: 'GEN'));
        $yearLevel  = max(1, min(4, $yearLevel));   // Year levels are 1–4 only

        // Build prefix directly via EdpCodeService so the semester digit is
        // always derived from the actual semester string, not from
        // Setting::edpCodePrefix() which was hardcoding "1".
        //
        // Format: [MAJOR]-[YY][SEM][LEVEL]
        //   e.g.  IT-2621  (2nd Semester 2026-2027, Year 1)
        //         IT-2611  (1st Semester 2026-2027, Year 1)
        //         IT-2631  (Summer       2026-2027, Year 1)
        /** @var \App\Services\EdpCodeService $edpService */
        $edpService = app(\App\Services\EdpCodeService::class);
        $codePrefix = $edpService->prefix($major, $schoolYear, $semester, $yearLevel);

        $maxSequence = static::query()
            ->forWorkspace($semester, $schoolYear)
            ->where('major', $major)
            ->where('year_level', $yearLevel)
            ->where('edp_code', 'like', $codePrefix . '%')
            ->when($ignoreSubjectId, fn ($q) => $q->whereKeyNot($ignoreSubjectId))
            ->pluck('edp_code')
            ->map(function ($edpCode) use ($codePrefix) {
                $sequence = substr((string) $edpCode, strlen($codePrefix));

                return ctype_digit($sequence) ? (int) $sequence : 0;
            })
            ->max() ?? 0;

        do {
            $maxSequence++;
            $candidate = $codePrefix . str_pad((string) $maxSequence, 3, '0', STR_PAD_LEFT);
        } while (static::edpExistsInWorkspace($candidate, $schoolYear, $semester, $ignoreSubjectId));

        return $candidate;
    }

    /**
     * Check whether an EDP code is already in use for the given workspace.
     * Scoped to the same semester + school year (workspace-level uniqueness).
     */
    public static function edpExistsInWorkspace(
        string  $edpCode,
        ?string $schoolYear       = null,
        ?string $semester         = null,
        ?int    $ignoreSubjectId  = null
    ): bool {
        $period     = Setting::getAcademicPeriod();
        $schoolYear = $schoolYear ?? $period['school_year'];
        $semester   = Setting::normalizeSemester($semester ?? $period['semester']);

        return static::query()
            ->forWorkspace($semester, $schoolYear)
            ->where('edp_code', strtoupper(trim($edpCode)))
            ->when($ignoreSubjectId, fn ($q) => $q->whereKeyNot($ignoreSubjectId))
            ->exists();
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    /**
     * Get department colour code for the UI.
     */
    public function departmentColor(): Attribute
    {
        $colors = [
            'IT'   => 'yellow',
            'ACT'  => 'yellow',
            'CCS'  => 'yellow',
            'CTE'  => 'blue',
            'ED'   => 'blue',
            'COC'  => 'violet',
            'FB'   => 'violet',
            'LD'   => 'violet',
            'QD'   => 'violet',
            'SHTM' => 'orange',
            'HM'   => 'orange',
            'TM'   => 'orange',
        ];

        return Attribute::make(
            get: fn () => $colors[$this->department] ?? 'gray'
        )->shouldCache();
    }

    /**
     * Get subject card styling for MasterGrid.
     */
    public function getCardStyling(): array
    {
        $styles = [
            'IT'   => 'yellow',
            'ACT'  => 'yellow',
            'CCS'  => 'yellow',
            'CTE'  => 'blue',
            'ED'   => 'blue',
            'COC'  => 'violet',
            'FB'   => 'violet',
            'LD'   => 'violet',
            'QD'   => 'violet',
            'SHTM' => 'orange',
            'HM'   => 'orange',
            'TM'   => 'orange',
        ];

        return [
            'color'      => $styles[$this->department] ?? 'gray',
            'department' => $this->department,
        ];
    }

    /**
     * Get the unique identifier for a student group.
     * Used for section conflict checking.
     * Combines Department + Major + Year Level + Section.
     */
    public function getStudentGroupIdentifier(): string
    {
        return "{$this->department}|{$this->major}|{$this->year_level}|{$this->section}";
    }

    /**
     * Get all subjects that share the same student group.
     */
    public static function getSubjectsForGroup($department, $major, $section)
    {
        return static::activeTerm()
            ->where('department', $department)
            ->where('major', $major)
            ->where('section', $section)
            ->get();
    }

    /**
 * The faculty preferred for scheduling this subject.
 * Set via the "Assign Preferred Faculty" modal in Manage Subjects.
 */
public function preferredFaculty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\Faculty::class, 'preferred_faculty_id');
}
 
/**
 * The room preferred for scheduling this subject.
 * Set via the "Assign Preferred Room" modal in Manage Subjects.
 */
public function preferredRoom(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\Room::class, 'preferred_room_id');
}

    /**
     * Get remaining meeting slots for this subject.
     */
    public function getRemainingMeetings()
    {
        $scheduled = Schedule::activeTerm()
            ->where('subject_id', $this->id)
            ->count();

        return max(0, $this->meetings_per_week - $scheduled);
    }

    // ============================================================
    // ROOM OVERRIDE → FACULTY ELIGIBILITY
    // ============================================================

    /**
     * Whether this subject's Room Override is currently active.
     *
     * preferred_room_type is the single source of truth — this mirrors the
     * exact derivation used in ManageSubjects::editSubject() / executeSave()
     * so the checkbox state, the stored room type, and faculty eligibility
     * can never drift out of sync:
     *
     *   Major + preferred_room_type === 'LECTURE' → override ON (bypass lab routing)
     *   Minor + preferred_room_type === 'LAB'      → override ON (use dept lab)
     *   Anything else                              → override OFF (auto)
     *
     * Deliberately NOT a stored column of its own — deriving it keeps one
     * source of truth instead of two values that could disagree.
     */
    public function hasRoomOverride(): bool
    {
        $isMajor       = strtolower((string) $this->type) === 'major';
        $savedRoomType = strtoupper(trim((string) $this->preferred_room_type));

        return $isMajor
            ? $savedRoomType === 'LECTURE'
            : $savedRoomType === 'LAB';
    }

    /**
     * Human-readable list of the faculty groups eligible to teach this subject
     * while Room Override is active (e.g. "Department Faculty or General
     * Education Faculty"). Returns an empty string when no group is checked —
     * which means nobody can currently be assigned.
     *
     * Only meaningful when hasRoomOverride() is true; the default Major/Minor
     * routing ignores these flags entirely.
     */
    public function eligibleFacultyGroupLabels(): string
    {
        $labels = [];

        if ($this->allow_department_faculty) {
            $labels[] = 'Department Faculty';
        }
        if ($this->allow_gened_faculty) {
            $labels[] = 'General Education Faculty';
        }
        if ($this->allow_cross_department_faculty) {
            $labels[] = 'Cross Department Faculty';
        }

        return implode(' or ', $labels);
    }
}