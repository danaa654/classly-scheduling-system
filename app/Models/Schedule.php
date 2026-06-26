<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FACULTY_ASSIGNED = 'faculty_assigned';

    public const STATUS_FACULTY_LOCKED = 'faculty_locked';

    public const STATUS_PENDING_GENERATION = 'pending_generation';

    public const STATUS_FINALIZED = 'finalized';

    /**
     * Imported during semester retrieval but flagged for manual review.
     * Stored with validation_notes explaining why (inactive day, missing room, etc.).
     */
    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const SECTION_TIME_PREFERENCES = [
        'A' => 'morning',
        'B' => 'afternoon',
        'C' => 'flexible',
    ];

    protected $fillable = [
        'subject_id',
        'room_id',
        'faculty_id',
        'user_id',
        'department',
        'major',
        'year_level',
        'section',
        'day',
        'start_time',
        'end_time',
        'duration_hours',
        'meetings_per_week',
        'pairing_key',
        'status',
        'edp_code',
        'semester',
        'school_year',
        'academic_year',
        'is_archived',
        'archived_at',
        'workspace_key',
        'archive_batch',
        'validation_notes',   // ← populated when status = needs_review
    ];

    protected $casts = [
        'start_time'       => 'datetime:H:i:s',
        'end_time'         => 'datetime:H:i:s',
        'duration_hours'   => 'float',
        'meetings_per_week'=> 'integer',
        'is_archived'      => 'boolean',
        'archived_at'      => 'datetime',
        'validation_notes' => 'array',    // ← stored as JSON, cast to PHP array
    ];

    protected static function booted(): void
    {
        static::saving(function (Schedule $schedule) {
            $period = Setting::getAcademicPeriod();

            if (blank($schedule->semester)) {
                $schedule->semester = $period['semester'];
            }

            $schedule->semester = Setting::normalizeSemester($schedule->semester);

            $schoolYear   = $schedule->school_year ?: $schedule->academic_year ?: $period['school_year'];
            $academicYear = $schedule->academic_year ?: $schoolYear;

            if ($schedule->isDirty('academic_year') && ! $schedule->isDirty('school_year')) {
                $schoolYear = $academicYear;
            }

            if ($schedule->isDirty('school_year') && ! $schedule->isDirty('academic_year')) {
                $academicYear = $schoolYear;
            }

            if ($schoolYear !== $academicYear) {
                $academicYear = $schoolYear;
            }

            $schedule->school_year   = $schoolYear;
            $schedule->academic_year = $academicYear;

            if (blank($schedule->academic_year)) {
                $schedule->academic_year = $period['school_year'];
            }

            if (blank($schedule->workspace_key)) {
                $schedule->workspace_key = $period['workspace_key'];
            }
        });
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function day(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function section(): Attribute
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
    // SCOPES - Filtering & Querying
    // ============================================================

    public function scopeForRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithinTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }

    public function scopeForSection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopePartial($query)
    {
        return $query->where('status', self::STATUS_PARTIAL);
    }

    public function scopeFacultyAssigned($query)
    {
        return $query->where('status', self::STATUS_FACULTY_ASSIGNED);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    public function scopeAssignable($query)
    {
        return $query->whereIn('status', [self::STATUS_PARTIAL, self::STATUS_FACULTY_ASSIGNED]);
    }

    public function scopeFacultyLocked($query)
    {
        return $query->where('status', self::STATUS_FACULTY_LOCKED);
    }

    public function scopePendingGeneration($query)
    {
        return $query->where('status', self::STATUS_PENDING_GENERATION);
    }

    /**
     * Schedules imported during semester retrieval that need manual review.
     * Use in Faculty Loading and Block Schedule to surface flagged records.
     *
     *   Schedule::activeTerm($sem, $year)->needsReview()->get();
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('status', self::STATUS_NEEDS_REVIEW);
    }

    public function scopeUnscheduled($query)
    {
        return $query->whereNull('day')
            ->whereNull('start_time')
            ->whereNull('end_time')
            ->whereNull('room_id')
            ->whereNotNull('faculty_id');
    }

    public function scopeAwaitingFaculty($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('faculty_id')
                ->orWhere(function ($inner) {
                    $inner->whereNull('day')
                        ->whereNull('start_time')
                        ->whereNull('end_time')
                        ->whereNull('room_id')
                        ->whereNotNull('faculty_id');
                });
        });
    }

    /** Only rows visible on the active-term dashboards */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /** Only rows belonging to the given semester + year */
    public function scopeForTerm($query, string $semester, string $schoolYear)
    {
        return $query->forWorkspace($semester, $schoolYear);
    }

    /**
     * Alias for forTerm() used throughout the codebase.
     * Keeps non-archived records scoped to the active workspace.
     */
    public function scopeActiveTerm($query, ?string $semester = null, ?string $schoolYear = null)
    {
        if ($semester && $schoolYear) {
            return $query->forWorkspace($semester, $schoolYear)
                ->where('is_archived', false);
        }

        $period = Setting::getAcademicPeriod();

        return $query->forWorkspace($period['semester'], $period['school_year'])
            ->where('is_archived', false);
    }

    public function scopeForWorkspace($query, string $semester, string $schoolYear)
    {
        return $query->where('semester', $semester)
            ->where(function ($q) use ($schoolYear) {
                $q->where('school_year', $schoolYear)
                    ->orWhere('academic_year', $schoolYear);
            });
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Archived records for a specific batch and semester/year.
     */
    public function scopeArchivedTerm($query, string $semester, string $schoolYear)
    {
        return $query->forWorkspace($semester, $schoolYear)
            ->where('is_archived', true);
    }

    public static function sectionTimePreference(?string $section): ?string
    {
        return self::SECTION_TIME_PREFERENCES[strtoupper((string) $section)] ?? null;
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where(function ($query) use ($department) {
            $query->where('department', $department)
                ->orWhereHas('subject', function ($q) use ($department) {
                    $q->where('department', $department);
                });
        });
    }

    public function scopeByYearLevel($query, $yearLevel)
    {
        return $query->where(function ($query) use ($yearLevel) {
            $query->where('year_level', (int) $yearLevel)
                ->orWhereHas('subject', function ($q) use ($yearLevel) {
                    $q->where('year_level', (int) $yearLevel);
                });
        });
    }

    public function scopeMorningClasses($query)
    {
        return $query->where('start_time', '<', '12:00:00');
    }

    public function scopeAfternoonClasses($query)
    {
        return $query->where('start_time', '>=', '12:00:00');
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    public function durationInHours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateDuration()
        )->shouldCache();
    }

    public function timeDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatTimeDisplay()
        )->shouldCache();
    }

    public function fullDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->day}: {$this->time_display}"
        )->shouldCache();
    }

    // ============================================================
    // VALIDATION & CONSTRAINT METHODS
    // ============================================================

    private function calculateDuration(): float
    {
        try {
            $start = Carbon::parse($this->start_time);
            $end   = Carbon::parse($this->end_time);

            return round($start->diffInMinutes($end) / 60, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function formatTimeDisplay(): string
    {
        try {
            $start = Carbon::parse($this->start_time);
            $end   = Carbon::parse($this->end_time);

            return $start->format('h:i A') . ' - ' . $end->format('h:i A');
        } catch (\Exception $e) {
            return 'Invalid time';
        }
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    public function getDurationMinutes(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function hasConflict(self $other): bool
    {
        if ($this->day !== $other->day) {
            return false;
        }

        return $this->start_time < $other->end_time
            && $this->end_time > $other->start_time;
    }

    public function markAsArchived(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function isCompatible(): bool
    {
        if (! $this->subject || ! $this->room) {
            return false;
        }

        return $this->room->isCompatibleWithSubject($this->subject);
    }

    public function respectsLunchBreak(): bool
    {
        $lunchStart    = Carbon::parse('12:00');
        $lunchEnd      = Carbon::parse('13:00');
        $scheduleStart = Carbon::parse($this->start_time);
        $scheduleEnd   = Carbon::parse($this->end_time);

        return ! ($scheduleStart < $lunchEnd && $scheduleEnd > $lunchStart);
    }

    public function isWithinOperationalHours(): bool
    {
        $bounds        = Setting::getDayBounds();
        $dayStart      = Carbon::parse($bounds['start']);
        $dayEnd        = Carbon::parse($bounds['end']);
        $scheduleStart = Carbon::parse($this->start_time);
        $scheduleEnd   = Carbon::parse($this->end_time);

        return $scheduleStart >= $dayStart && $scheduleEnd <= $dayEnd;
    }

    public function isOnActiveScheduleDay(): bool
    {
        return Setting::dayIsActive((string) $this->day);
    }

    public function isValid(): bool
    {
        return $this->isOnActiveScheduleDay()
            && $this->isWithinOperationalHours()
            && $this->respectsLunchBreak()
            && $this->isCompatible();
    }

    public function getValidationErrors(): array
    {
        $errors = [];

        if (! $this->isWithinOperationalHours()) {
            $errors[] = 'Schedule is outside operational hours.';
        }

        if (! $this->isOnActiveScheduleDay()) {
            $errors[] = 'Schedule day is disabled in global settings.';
        }

        if (! $this->respectsLunchBreak()) {
            $errors[] = 'Schedule overlaps with lunch break (12:00-13:00).';
        }

        if (! $this->isCompatible()) {
            $errors[] = 'Subject type is not compatible with room type.';
        }

        return $errors;
    }

    /**
     * True when this schedule was flagged during retrieval and has stored reasons.
     * Use in Block Schedule and Faculty Loading to highlight records needing attention.
     */
    public function hasValidationIssues(): bool
    {
        return $this->status === self::STATUS_NEEDS_REVIEW
            && ! empty($this->validation_notes);
    }

    public function respectsSectionSession(): bool
    {
        $start      = Carbon::parse($this->start_time);
        $end        = Carbon::parse($this->end_time);
        $lunchStart = Carbon::parse('12:00:00');
        $lunchEnd   = Carbon::parse('13:00:00');

        if (strtoupper((string) $this->section) === 'A') {
            return $start->lt($lunchStart) && $end->lte($lunchStart);
        }

        if (strtoupper((string) $this->section) === 'B') {
            return $start->gte($lunchEnd) && $end->gt($lunchEnd);
        }

        return true;
    }

    public static function roomIsAvailable(int $roomId, string $day, string $startTime, string $endTime, ?int $excludeScheduleId = null): bool
    {
        $query = static::where('room_id', $roomId)
            ->activeTerm()
            ->where('day', $day)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return ! $query->exists();
    }

    public function getConflicts()
    {
        return static::where('room_id', $this->room_id)
            ->activeTerm()
            ->where('day', $this->day)
            ->where('id', '!=', $this->id)
            ->where(function ($q) {
                $q->where('start_time', '<', $this->end_time)
                    ->where('end_time', '>', $this->start_time);
            })
            ->get();
    }

    // ============================================================
    // PRE-ASSIGNMENT / PLACEHOLDER HELPERS
    // (used by FacultyLoading and BlockSchedule UI)
    // ============================================================

    /**
     * True when the schedule has a faculty assigned but no timeslot or room —
     * i.e. a "pre-assignment placeholder" created before auto-scheduling runs.
     *
     * Mirrors the $isUnscheduled logic already used inside FacultyLoading:
     *   day === null && start_time === null && end_time === null
     *
     * Note: room_id may also be null, but the three time fields are the
     * authoritative signal because retrieved KEEP_FACULTY_ROOM rows can
     * carry a room while still lacking a timeslot.
     */
    public function isPreAssignmentPlaceholder(): bool
    {
        return $this->faculty_id !== null
            && $this->day === null
            && $this->start_time === null
            && $this->end_time === null;
    }

    /**
     * True when day, start_time, end_time, AND room_id are all populated.
     */
    public function isFullyScheduled(): bool
    {
        return $this->day !== null
            && $this->start_time !== null
            && $this->end_time !== null
            && $this->room_id !== null;
    }

    /**
     * Display-safe faculty name for blade/array outputs.
     */
    public function getFacultyName(): string
    {
        return $this->faculty?->full_name ?? '—';
    }

    /**
     * Display-safe day label; shows "TBA" for placeholder rows.
     */
    public function getDayDisplay(): string
    {
        return $this->day ? ucfirst((string) $this->day) : 'TBA';
    }

    /**
     * Display-safe time range; shows "TBA" for placeholder rows.
     */
    public function getTimeDisplay(): string
    {
        if (! $this->start_time || ! $this->end_time) {
            return 'TBA';
        }

        try {
            return Carbon::parse($this->start_time)->format('h:i A')
                . ' – '
                . Carbon::parse($this->end_time)->format('h:i A');
        } catch (\Exception) {
            return 'TBA';
        }
    }

    /**
     * Display-safe room name; shows "TBA" for placeholder rows.
     */
    public function getRoomDisplay(): string
    {
        return $this->room?->room_name ?? 'TBA';
    }

    /**
     * Human-readable status label for the UI.
     */
    public function getStatusDisplay(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => 'Draft',
            self::STATUS_PARTIAL            => 'Partial',
            self::STATUS_FACULTY_ASSIGNED   => 'Faculty Assigned',
            self::STATUS_FACULTY_LOCKED     => 'Pre-Assigned',
            self::STATUS_PENDING_GENERATION => 'Pending Generation',
            self::STATUS_FINALIZED          => 'Finalized',
            self::STATUS_NEEDS_REVIEW       => 'Needs Review',
            default                         => ucfirst((string) ($this->status ?? 'Unknown')),
        };
    }
}