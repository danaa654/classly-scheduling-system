<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRevisionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'subject_id',
        'requested_by',
        'reviewed_by',
        'current_faculty_id',
        'requested_faculty_id',
        'schedule_ids',
        'reason',
        'status',
        'review_note',
        'semester',
        'school_year',
        'workspace_key',
        'reviewed_at',
    ];

    protected $casts = [
        'schedule_ids' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ScheduleRevisionRequest $request) {
            $period = Setting::getAcademicPeriod();

            if (blank($request->semester)) {
                $request->semester = $period['semester'];
            }

            $request->semester = Setting::normalizeSemester($request->semester);
            $request->school_year = $request->school_year ?: $period['school_year'];
            $request->workspace_key = Setting::workspaceKey($request->school_year, $request->semester);
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function currentFaculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'current_faculty_id');
    }

    public function requestedFaculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'requested_faculty_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForWorkspace($query, ?string $semester = null, ?string $schoolYear = null)
    {
        $period = Setting::getAcademicPeriod();
        $semester = Setting::normalizeSemester($semester ?? $period['semester']);
        $schoolYear = $schoolYear ?? $period['school_year'];
        $workspaceKey = Setting::workspaceKey($schoolYear, $semester);

        return $query->where('semester', $semester)
            ->where(function ($query) use ($schoolYear, $workspaceKey) {
                $query->where('workspace_key', $workspaceKey)
                    ->orWhere('school_year', $schoolYear);
            });
    }
}
