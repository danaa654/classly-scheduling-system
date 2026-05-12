<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Subject extends Model
{
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
        'meetings_per_week',
        'faculty_id',
    ];

    protected $casts = [
        'units' => 'integer',
        'year_level' => 'integer',
        'duration_hours' => 'float',
        'meetings_per_week' => 'integer',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================
    
    /**
     * Get schedules associated with this subject
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the faculty member assigned to this subject
     * FIXED: Changed from User to Faculty model
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    // ============================================================
    // SCOPES - Filtering & Querying
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
        return $query->where('year_level', (int)$year);
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
                     ->orWhere('description', 'like', "%{$term}%")
                     ->orWhere('edp_code', 'like', "%{$term}%");
    }

    // ============================================================
    // ACCESSORS & ATTRIBUTES
    // ============================================================

    /**
     * Get department color code for the UI
     */
    public function departmentColor(): Attribute
    {
        $colors = [
            'IT' => 'yellow',
            'ACT' => 'yellow',
            'CCS' => 'yellow',
            'CTE' => 'blue',
            'ED' => 'blue',
            'COC' => 'violet',
            'FB' => 'violet',
            'LD' => 'violet',
            'QD' => 'violet',
            'SHTM' => 'orange',
            'HM' => 'orange',
            'TM' => 'orange',
        ];

        return Attribute::make(
            get: fn () => $colors[$this->department] ?? 'gray'
        )->shouldCache();
    }

    /**
     * Get subject card styling for MasterGrid
     */
    public function getCardStyling(): array
    {
        $styles = [
            'IT' => 'yellow',
            'ACT' => 'yellow',
            'CCS' => 'yellow',
            'CTE' => 'blue',
            'ED' => 'blue',
            'COC' => 'violet',
            'FB' => 'violet',
            'LD' => 'violet',
            'QD' => 'violet',
            'SHTM' => 'orange',
            'HM' => 'orange',
            'TM' => 'orange',
        ];

        return [
            'color' => $styles[$this->department] ?? 'gray',
            'department' => $this->department,
        ];
    }

    /**
     * Get the unique identifier for a student group
     * Used for section conflict checking
     * Combines Department + Major + Section
     */
    public function getStudentGroupIdentifier(): string
    {
        return "{$this->department}|{$this->major}|{$this->year_level}|{$this->section}";
    }

    /**
     * Get all subjects with the same student group
     */
    public static function getSubjectsForGroup($department, $major, $section)
    {
        return static::where('department', $department)
            ->where('major', $major)
            ->where('section', $section)
            ->get();
    }

    /**
     * Get remaining meetings for this subject
     */
    public function getRemainingMeetings()
    {
        $scheduled = Schedule::where('subject_id', $this->id)->count();
        return max(0, $this->meetings_per_week - $scheduled);
    }
}
