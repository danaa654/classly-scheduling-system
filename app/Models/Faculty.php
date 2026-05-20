<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    public const SCOPE_GENED = 'gened';

    public const SCOPE_DEPARTMENTAL = 'departmental';

    public const SCOPE_CROSS_DEPARTMENT = 'cross_department';

    public const FACULTY_SCOPES = [
        self::SCOPE_GENED,
        self::SCOPE_DEPARTMENTAL,
        self::SCOPE_CROSS_DEPARTMENT,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'full_name',
        'email',
        'department',
        'status',
        'employment_type',
        'faculty_scope',
        'can_teach_minor',
        'max_units',
        'availability',
        'requested_by',
        'rejection_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'can_teach_minor' => 'boolean',
        'max_units' => 'integer',
        'availability' => 'array',
    ];

    /**
     * Get the user account associated with this faculty member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A faculty member can be assigned to many subjects (schedules).
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'faculty_id');
    }

    /**
     * Scheduled teaching assignments owned by Faculty Loading.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the logs for this faculty member
     */
    public function logs()
    {
        return $this->hasMany(FacultyLog::class);
    }

    /**
     * Get the user who requested this faculty
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope to get all approved faculties
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get all pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get all rejected faculties
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        if (in_array($user->role, ['admin', 'registrar', 'associate_dean'], true)) {
            return $query;
        }

        if (in_array($user->role, ['dean', 'oic'], true)) {
            return $query->where(function (Builder $visibility) use ($user) {
                $visibility->whereIn('department', Department::aliasesFor($user->department))
                    ->orWhere('faculty_scope', self::SCOPE_GENED);
            });
        }

        return $query->whereIn('department', Department::aliasesFor($user->department));
    }

    public function scopeEligibleForMinor(Builder $query): Builder
    {
        return $query->where(function (Builder $eligible) {
            $eligible->where('faculty_scope', self::SCOPE_GENED)
                ->orWhere('can_teach_minor', true);
        });
    }

    public function isGenEd(): bool
    {
        return $this->faculty_scope === self::SCOPE_GENED;
    }

    public function isDepartmental(): bool
    {
        return $this->faculty_scope === self::SCOPE_DEPARTMENTAL;
    }

    public function isCrossDepartment(): bool
    {
        return $this->faculty_scope === self::SCOPE_CROSS_DEPARTMENT;
    }

    public function canTeachMinorSubjects(): bool
    {
        return $this->isGenEd() || $this->isCrossDepartment() || (bool) $this->can_teach_minor;
    }

    public function canTeachDepartment(?string $department): bool
    {
        if ($this->isGenEd()) {
            return true;
        }

        return Department::codesMatch($this->department, $department);
    }

    public function isEligibleForSubject(?Subject $subject): bool
    {
        if (! $subject) {
            return false;
        }

        if ($this->subjectIsMinorOrGenEd($subject)) {
            return $this->canTeachMinorSubjects();
        }

        if ($this->isGenEd()) {
            return false;
        }

        return $this->canTeachDepartment($subject->department);
    }

    public function scopeLabel(): string
    {
        return match ($this->faculty_scope) {
            self::SCOPE_GENED => 'GENED',
            self::SCOPE_CROSS_DEPARTMENT => 'CROSS-DEPARTMENT',
            default => 'DEPARTMENTAL',
        };
    }

    public function displayDepartment(): string
    {
        return $this->isGenEd() || blank($this->department)
            ? 'Institution-wide'
            : (string) Department::normalizeCode($this->department);
    }

    private function subjectIsMinorOrGenEd(Subject $subject): bool
    {
        $type = strtolower(trim((string) $subject->type));
        $subjectType = strtolower(trim((string) ($subject->subject_type ?? '')));
        $department = Department::normalizeCode($subject->department);
        $major = Department::normalizeCode($subject->major);

        return $type === 'minor'
            || $subjectType === 'minor'
            || $department === 'GENED'
            || $major === 'GENED'
            || str_contains(strtoupper((string) $subject->subject_code), 'NSTP')
            || str_contains(strtoupper((string) $subject->subject_code), 'PATHFIT');
    }

}
