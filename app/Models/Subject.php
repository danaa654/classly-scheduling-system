<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Subject extends Model
{
    protected $fillable = [
        'edp_code',
        'subject_code',
        'section',
        'description',
        'units',
        'department',
        'type', // 'Lecture' or 'Laboratory'
        'duration_hours',
        'meetings_per_week',
        'scheduled_hours',
    ];

    protected $casts = [
        'units' => 'float',
        'duration_hours' => 'float',
        'meetings_per_week' => 'integer',
        'scheduled_hours' => 'float',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    
    /**
     * A subject has many schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // ============================================================
    // SCOPES - Filtering & Querying
    // ============================================================

    /**
     * Filter subjects by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Filter subjects by type (Lecture/Laboratory)
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Search subjects by code or description
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('subject_code', 'like', "%{$term}%")
                     ->orWhere('description', 'like', "%{$term}%")
                     ->orWhere('edp_code', 'like', "%{$term}%");
    }

    /**
     * Get subjects that need more scheduling
     */
    public function scopeIncomplete($query)
    {
        return $query->whereRaw('scheduled_hours < (units * meetings_per_week)');
    }

    /**
     * Get subjects that are fully scheduled
     */
    public function scopeComplete($query)
    {
        return $query->whereRaw('scheduled_hours >= (units * meetings_per_week)');
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    /**
     * Calculate remaining hours needed
     */
    public function remainingHours(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, ($this->units * $this->meetings_per_week) - ($this->scheduled_hours ?? 0))
        )->shouldCache();
    }

    /**
     * Get scheduling progress percentage
     */
    public function schedulingProgress(): Attribute
    {
        $required = $this->units * $this->meetings_per_week;
        return Attribute::make(
            get: fn () => $required > 0 ? round((($this->scheduled_hours ?? 0) / $required) * 100, 2) : 0
        )->shouldCache();
    }

    /**
     * Get department color code
     */
    public function departmentColor(): Attribute
    {
        $colors = [
            'CCS' => 'yellow',    // College of Computer Studies
            'CTE' => 'blue',      // College of Teacher Education
            'COC' => 'violet',    // College of Criminology
            'SHTM' => 'orange',   // School of Hospitality & Tourism
        ];

        return Attribute::make(
            get: fn () => $colors[$this->department] ?? 'gray'
        )->shouldCache();
    }

    // ============================================================
    // VALIDATION & CONSTRAINT METHODS
    // ============================================================

    /**
     * Check if subject can be scheduled in a specific room type
     */
    public function canScheduleInRoomType(string $roomType): bool
    {
        // Laboratory subjects can only go in Lab rooms
        if ($this->type === 'Laboratory' && $roomType !== 'Laboratory') {
            return false;
        }

        // Lecture subjects can go in Lecture rooms
        if ($this->type === 'Lecture' && $roomType === 'Lecture') {
            return true;
        }

        return true;
    }

    /**
     * Check if subject needs more meetings
     */
    public function needsMoreMeetings(): bool
    {
        $requiredHours = $this->units * $this->meetings_per_week;
        return ($this->scheduled_hours ?? 0) < $requiredHours;
    }

    /**
     * Get count of scheduled meetings
     */
    public function scheduledMeetingsCount(): int
    {
        return $this->schedules()->count();
    }

    /**
     * Get total meeting slots needed
     */
    public function totalMeetingsNeeded(): int
    {
        return $this->meetings_per_week;
    }

    /**
     * Check if subject is fully scheduled
     */
    public function isFullyScheduled(): bool
    {
        return !$this->needsMoreMeetings();
    }

    /**
     * Get remaining slots needed for this subject
     */
    public function remainingMeetingSlots(): int
    {
        $scheduled = $this->scheduledMeetingsCount();
        return max(0, $this->totalMeetingsNeeded() - $scheduled);
    }
    /**
 * Get subject card styling
 */
public function getCardStyling(): array
{
    $styles = [
        'CCS' => 'yellow',
        'CTE' => 'blue',
        'COC' => 'violet',
        'SHTM' => 'orange',
    ];

    return [
        'color' => $styles[$this->department] ?? 'gray',
        'department' => $this->department,
    ];
}
public function getRemainingMeetings()
{
    // Logic to count existing schedules for this subject
    $scheduled = \App\Models\Schedule::where('subject_id', $this->id)->count();
    return max(0, $this->meetings_per_week - $scheduled);
}
}