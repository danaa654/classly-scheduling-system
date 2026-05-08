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
    
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    // ============================================================
    // SCOPES - Filtering & Querying
    // ============================================================

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
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

    public function getRemainingMeetings()
    {
        $scheduled = \App\Models\Schedule::where('subject_id', $this->id)->count();
        return max(0, $this->meetings_per_week - $scheduled);
    }
}
