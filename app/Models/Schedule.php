<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Schedule extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FACULTY_ASSIGNED = 'faculty_assigned';
    public const STATUS_FINALIZED = 'finalized';

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
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'duration_hours' => 'float',
        'meetings_per_week' => 'integer',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * A schedule belongs to a subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * A schedule belongs to a room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * A schedule may be assigned to a faculty member after room/time scheduling.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * A schedule belongs to a user (instructor/admin)
     */
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

    /**
     * Filter schedules by room
     */
    public function scopeForRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Filter schedules by subject
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Filter schedules by day
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Filter schedules for a specific user/instructor
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get schedules within a time range
     */
    public function scopeWithinTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '<', $endTime)
                     ->where('end_time', '>', $startTime);
    }

    /**
     * Get schedules for a specific section
     */
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

    public static function sectionTimePreference(?string $section): ?string
    {
        return self::SECTION_TIME_PREFERENCES[strtoupper((string) $section)] ?? null;
    }

    /**
     * Filter schedules by department through subject relationship
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where(function ($query) use ($department) {
            $query->where('department', $department)
                ->orWhereHas('subject', function ($q) use ($department) {
                    $q->where('department', $department);
                });
        });
    }

    /**
     * Filter schedules by year level through subject relationship
     */
    public function scopeByYearLevel($query, $yearLevel)
    {
        return $query->where(function ($query) use ($yearLevel) {
            $query->where('year_level', (int) $yearLevel)
                ->orWhereHas('subject', function ($q) use ($yearLevel) {
                    $q->where('year_level', (int) $yearLevel);
                });
        });
    }

    /**
     * Get morning classes (before noon)
     */
    public function scopeMorningClasses($query)
    {
        return $query->where('start_time', '<', '12:00:00');
    }

    /**
     * Get afternoon classes
     */
    public function scopeAfternoonClasses($query)
    {
        return $query->where('start_time', '>=', '12:00:00');
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    /**
     * Get duration in hours
     */
    public function durationInHours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateDuration()
        )->shouldCache();
    }

    /**
     * Get formatted time display (e.g., "07:00 AM - 08:00 AM")
     */
    public function timeDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatTimeDisplay()
        )->shouldCache();
    }

    /**
     * Get full schedule display (e.g., "MON: 07:00 AM - 08:00 AM")
     */
    public function fullDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->day}: {$this->time_display}"
        )->shouldCache();
    }

    // ============================================================
    // VALIDATION & CONSTRAINT METHODS
    // ============================================================

    /**
     * Calculate duration in hours
     */
    private function calculateDuration(): float
    {
        try {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);
            return round($start->diffInMinutes($end) / 60, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format time display
     */
    private function formatTimeDisplay(): string
    {
        try {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);
            return $start->format('h:i A') . ' - ' . $end->format('h:i A');
        } catch (\Exception $e) {
            return 'Invalid time';
        }
    }

    /**
     * Check if this schedule conflicts with another
     */
    public function hasConflictWith(Schedule $other): bool
    {
        if ($this->room_id !== $other->room_id || $this->day !== $other->day) {
            return false;
        }

        return $this->start_time < $other->end_time && $this->end_time > $other->start_time;
    }

    /**
     * Check if subject type is compatible with room type
     */
    public function isCompatible(): bool
    {
        if (!$this->subject || !$this->room) {
            return false;
        }

        return $this->room->isCompatibleWithSubject($this->subject);
    }

    /**
     * Check if scheduled time respects hard-coded lunch break (12:00-13:00)
     */
    public function respectsLunchBreak(): bool
    {
        $lunchStart = Carbon::parse('12:00');
        $lunchEnd = Carbon::parse('13:00');

        $scheduleStart = Carbon::parse($this->start_time);
        $scheduleEnd = Carbon::parse($this->end_time);

        // Check if schedule overlaps with lunch break
        return !($scheduleStart < $lunchEnd && $scheduleEnd > $lunchStart);
    }

    /**
     * Check if scheduled time is within operational hours (master bounds)
     */
    public function isWithinOperationalHours(): bool
    {
        $bounds = Setting::getDayBounds();
        $dayStart = Carbon::parse($bounds['start']);
        $dayEnd = Carbon::parse($bounds['end']);

        $scheduleStart = Carbon::parse($this->start_time);
        $scheduleEnd = Carbon::parse($this->end_time);

        return $scheduleStart >= $dayStart && $scheduleEnd <= $dayEnd;
    }

    public function isOnActiveScheduleDay(): bool
    {
        return Setting::dayIsActive((string) $this->day);
    }

    /**
     * Validate schedule before saving
     */
    public function isValid(): bool
    {
        return $this->isOnActiveScheduleDay()
            && $this->isWithinOperationalHours()
            && $this->respectsLunchBreak()
            && $this->isCompatible();
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (!$this->isWithinOperationalHours()) {
            $errors[] = 'Schedule is outside operational hours.';
        }

        if (!$this->isOnActiveScheduleDay()) {
            $errors[] = 'Schedule day is disabled in global settings.';
        }

        if (!$this->respectsLunchBreak()) {
            $errors[] = 'Schedule overlaps with lunch break (12:00-13:00).';
        }

        if (!$this->isCompatible()) {
            $errors[] = 'Subject type is not compatible with room type.';
        }

        return $errors;
    }

    public function respectsSectionSession(): bool
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $lunchStart = Carbon::parse('12:00:00');
        $lunchEnd = Carbon::parse('13:00:00');

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
                      ->where('day', $day)
                      ->where(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                      });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return !$query->exists();
    }

    /**
     * Get all conflicts for this schedule
     */
    public function getConflicts()
    {
        return static::where('room_id', $this->room_id)
                    ->where('day', $this->day)
                    ->where('id', '!=', $this->id)
                    ->where(function ($q) {
                        $q->where('start_time', '<', $this->end_time)
                          ->where('end_time', '>', $this->start_time);
                    })
                    ->get();
    }
}
