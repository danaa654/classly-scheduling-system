<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_name',
        'type', // 'Lecture' or 'Laboratory'
        'capacity',
        'specialization',
        'floor',
        'room_type',
        'allowed_departments',
        'department_owner',
        'is_specialized',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'allowed_departments' => 'array',
        'is_specialized' => 'boolean',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * A room has many schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Subjects that have been preferred-assigned to this room
     * (linked via Subject.preferred_room_id FK).
     *
     * Eager-load with activeTerm scope in the controller/component to prevent
     * N+1 queries and to keep the result scoped to the active semester.
     *
     * Used for weekly-utilisation calculations on the room list view:
     *   totalHours = sum(duration_hours × meetings_per_week) across assigned subjects
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'preferred_room_id');
    }

    protected function roomName(): Attribute
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

    protected function floor(): Attribute
    {
        return Attribute::make(get: fn ($value) => self::cleanText($value));
    }

    protected function roomType(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::cleanText($value ?: ($this->attributes['type'] ?? null))
        );
    }

    protected function departmentOwner(): Attribute
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
     * Filter rooms by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Filter rooms by capacity range
     */
    public function scopeByCapacity($query, $minCapacity, $maxCapacity = null)
    {
        $query->where('capacity', '>=', $minCapacity);
        if ($maxCapacity) {
            $query->where('capacity', '<=', $maxCapacity);
        }
        return $query;
    }

    /**
     * Search rooms by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('room_name', 'like', "%{$term}%")
                     ->orWhere('specialization', 'like', "%{$term}%")
                     ->orWhere('floor', 'like', "%{$term}%");
    }

    /**
     * Get available rooms (not fully booked)
     */
    public function scopeAvailable($query)
    {
        return $query->where('capacity', '>', 0);
    }

    /**
     * Get lecture rooms only
     */
    public function scopeLectureRooms($query)
    {
        return $query->whereIn('type', ['Lecture', 'LECTURE']);
    }

    /**
     * Get laboratory rooms only
     */
    public function scopeLabRooms($query)
    {
        return $query->whereIn('type', ['Laboratory', 'LAB', 'Lab']);
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    /**
     * Get room utilization percentage
     */
    public function utilizationPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateUtilization()
        )->shouldCache();
    }

    /**
     * Get type display name with emoji
     */
    public function typeDisplay(): Attribute
    {
        $types = [
            'Lecture' => '🎓 Lecture Room',
            'Laboratory' => '🔬 Laboratory',
        ];

        return Attribute::make(
            get: fn () => $types[$this->type] ?? $this->type
        )->shouldCache();
    }

    // ============================================================
    // CONSTRAINT & VALIDATION METHODS
    // ============================================================

    /**
     * Calculate room utilization percentage
     * Based on total available time slots vs. scheduled slots
     */
    public function calculateUtilization(): float
    {
        $settings = \App\Models\Setting::getScheduleSettings();

        $startTime = $settings['start_time'];
        $endTime = $settings['end_time'];
        $slotDuration = (int) $settings['slot_duration_minutes'];
        $activeDays = $settings['active_days'];

        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        $minutesPerDay = $start->diffInMinutes($end);
        $slotsPerDay = floor($minutesPerDay / $slotDuration);
        $totalSlotsWeek = $slotsPerDay * max(1, count($activeDays));

        if ($totalSlotsWeek === 0) {
            return 0;
        }

        // Count scheduled slots for this room
        $scheduledSlots = $this->schedules()
            ->activeTerm()
            ->whereIn('day', $activeDays)
            ->count();

        return round(($scheduledSlots / $totalSlotsWeek) * 100, 2);
    }

    /**
     * Get all schedules for a specific day
     */
    public function getSchedulesForDay(string $day)
    {
        return $this->schedules()
                    ->activeTerm()
                    ->where('day', $day)
                    ->orderBy('start_time')
                    ->get();
    }

    /**
     * Check if room is available at a specific time on a specific day
     */
    public function isAvailableAtTime(string $day, string $startTime, string $endTime): bool
    {
        return !$this->hasConflict($day, $startTime, $endTime);
    }

    /**
     * Check for scheduling conflicts
     */
    public function hasConflict(string $day, string $startTime, string $endTime): bool
    {
        return $this->schedules()
                    ->activeTerm()
                    ->where('day', $day)
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->exists();
    }

    /**
     * Get next available time slot for this room on a given day
     */
    public function getNextAvailableSlot(string $day): ?array
    {
        $settings = \App\Models\Setting::getScheduleSettings();

        if (!in_array($day, $settings['active_days'], true)) {
            return null;
        }

        $startTime = $settings['start_time'];
        $endTime = $settings['end_time'];
        $slotDuration = (int) $settings['slot_duration_minutes'];
        $lunchStart = '12:00';
        $lunchEnd = '13:00';

        $current = \Carbon\Carbon::parse($startTime);
        $dayEnd = \Carbon\Carbon::parse($endTime);
        $lunch = \Carbon\Carbon::parse($lunchStart);
        $lunchEndTime = \Carbon\Carbon::parse($lunchEnd);

        while ($current < $dayEnd) {
            // Skip lunch break
            if ($current >= $lunch && $current < $lunchEndTime) {
                $current->addMinutes($slotDuration);
                continue;
            }

            $next = $current->copy()->addMinutes($slotDuration);
            if ($next > $dayEnd) break;

            if ($this->isAvailableAtTime($day, $current->format('H:i:s'), $next->format('H:i:s'))) {
                return [
                    'start_time' => $current->format('H:i:s'),
                    'end_time' => $next->format('H:i:s'),
                    'display' => $current->format('h:i A') . ' - ' . $next->format('h:i A'),
                ];
            }

            $current = $next;
        }

        return null;
    }

    /**
     * Get total hours scheduled this week
     */
    public function getTotalHoursScheduledThisWeek(): float
    {
        return $this->schedules()
                    ->activeTerm()
                    ->get()
                    ->sum(function ($schedule) {
                        $start = \Carbon\Carbon::parse($schedule->start_time);
                        $end = \Carbon\Carbon::parse($schedule->end_time);
                        return $start->diffInHours($end);
                    });
    }

    /**
     * Check if subject type is compatible with room type
     */
    public function isCompatibleWithSubject(Subject $subject): bool
    {
        return $this->compatibilityScoreForSubject($subject) > 0;
    }

    public function compatibilityScoreForSubject(Subject $subject): int
    {
        return app(\App\Services\AutoScheduleService::class)->compatibilityScore($this, $subject);
    }

    public function allowedDepartmentCodes(): array
    {
        $departments = $this->allowed_departments;

        if (is_string($departments)) {
            $decoded = json_decode($departments, true);
            $departments = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : preg_split('/[,|\/;]+/', $departments);
        }

        return collect($departments ?? [])
            ->map(fn ($code) => Department::normalizeCode((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}