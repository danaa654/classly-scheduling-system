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
     * Default maximum teaching load (units).
     * Deans can raise this in +3 increments up to HARD_CAP_UNITS.
     */
    public const BASE_MAX_UNITS = 30;

    /**
     * Absolute ceiling — no faculty can exceed this regardless of overload grants.
     * Raising the cap from 39 by 3 would yield 42 > 40, so 39 is the practical max
     * achievable through +3 increments.
     */
    public const HARD_CAP_UNITS = 40;

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

    // =========================================================================
    // LOAD CAPACITY — BASE 30, HARD CAP 40
    // =========================================================================

    /**
     * The effective unit ceiling for this faculty member.
     *
     * Always within [BASE_MAX_UNITS, HARD_CAP_UNITS]:
     *   • Floors at 30 so the default "21" rows from the old migration still work.
     *   • Ceils at 40 so no overload grant can push past the hard limit.
     */
    public function effectiveMaxUnits(): int
    {
        return min(
            self::HARD_CAP_UNITS,
            max(self::BASE_MAX_UNITS, (int) ($this->max_units ?? self::BASE_MAX_UNITS))
        );
    }

    /**
     * Calculate total units assigned to this faculty, including unscheduled subjects.
     * Used for pre-assignment validation to check max_units constraint.
     */
    public function calculateTotalAssignedUnits(): int
    {
        return $this->schedules()
            ->activeTerm()
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sum('units') ?? 0;
    }

    /**
     * Calculate remaining units available before hitting the effective ceiling.
     */
    public function remainingUnitCapacity(): int
    {
        return max(0, $this->effectiveMaxUnits() - $this->calculateTotalAssignedUnits());
    }

    /**
     * Check if adding a subject would exceed the effective load ceiling.
     */
    public function canAccommodateSubject(?Subject $subject): bool
    {
        if (!$subject) {
            return false;
        }

        $subjectUnits = (int) ($subject->units ?? 0);
        return ($this->calculateTotalAssignedUnits() + $subjectUnits) <= $this->effectiveMaxUnits();
    }

    /**
     * Whether the dean/admin can raise this faculty's cap by 3 more units.
     * Blocks when the next increment would exceed HARD_CAP_UNITS.
     */
    public function canReceiveOverloadGrant(): bool
    {
        return ($this->effectiveMaxUnits() + 3) <= self::HARD_CAP_UNITS;
    }

    /**
     * Whether the dean/admin can lower this faculty's cap by 3 units.
     * Blocks when the new cap would fall below current assigned units
     * (can't orphan existing assignments) or below BASE_MAX_UNITS.
     */
    public function canReduceOverloadGrant(): bool
    {
        $proposed = $this->effectiveMaxUnits() - 3;
        if ($proposed < self::BASE_MAX_UNITS) {
            return false;
        }

        return $proposed >= $this->calculateTotalAssignedUnits();
    }

    /**
     * Human-readable description of the overload status, e.g. "30 / 40 hard cap".
     * Useful for tooltip / aria labels.
     */
    public function overloadStatusLabel(): string
    {
        $eff  = $this->effectiveMaxUnits();
        $base = self::BASE_MAX_UNITS;
        $cap  = self::HARD_CAP_UNITS;

        if ($eff === $base) {
            return "Base load ({$base} units max)";
        }

        $granted = $eff - $base;
        return "Base {$base} + {$granted} overload = {$eff} units (hard cap {$cap})";
    }

    /**
     * Check daily max unit controls (new constraint for intra-day distribution)
     */
    public function getUnitsOnDay(string $day): int
    {
        return $this->schedules()
            ->activeTerm()
            ->where('day', $day)
            ->whereNotNull('day')
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sum('units') ?? 0;
    }

    /**
     * Check if faculty has availability window constraints
     */
    public function getAvailabilityWindow(): ?array
    {
        if (!$this->availability) {
            return null;
        }

        if (is_string($this->availability)) {
            return json_decode($this->availability, true);
        }

        return $this->availability;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

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

    // =========================================================================
    // IDENTITY & ELIGIBILITY
    // =========================================================================

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

        if ($subject->hasRoomOverride()) {
            return $this->isEligibleUnderFacultyOverride($subject);
        }

        if ($this->subjectIsMinorOrGenEd($subject)) {
            return $this->canTeachMinorSubjects();
        }

        if ($this->isGenEd()) {
            return false;
        }

        return $this->canTeachDepartment($subject->department);
    }

    /**
     * Faculty eligibility while a subject's Room Override is active.
     *
     * Each Eligible Faculty checkbox opens exactly one faculty pool, and the
     * three pools are additive (OR), not exclusive:
     *
     *   Department Faculty        → any non-GenEd faculty whose home
     *                                department matches the subject's own
     *                                department.
     *   General Education Faculty → faculty tagged faculty_scope = gened.
     *   Cross Department Faculty  → faculty EXPLICITLY tagged
     *                                faculty_scope = cross_department.
     */
    private function isEligibleUnderFacultyOverride(Subject $subject): bool
    {
        if ($subject->allow_department_faculty
            && ! $this->isGenEd()
            && $this->canTeachDepartment($subject->department)) {
            return true;
        }

        if ($subject->allow_gened_faculty && $this->isGenEd()) {
            return true;
        }

        if ($subject->allow_cross_department_faculty && $this->isCrossDepartment()) {
            return true;
        }

        return false;
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