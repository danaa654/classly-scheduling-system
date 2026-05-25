<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\PermissionLog;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\ScheduleConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * BlockSchedule – Livewire Component
 *
 * Displays the official study-load (block schedule) for a chosen
 * department / year-level / section, and handles:
 *  - Inline faculty assignment with conflict detection
 *  - Finalization workflow (with pre-flight validation)
 *  - Role-based access control (Admin always; Registrar if delegated)
 *  - Registrar finalization permission toggle (Admin-only)
 *  - Department-based faculty filtering (CCS vs COC vs GenEd)
 *  - Full audit logging via PermissionLog
 *  - Print-ready output
 */
class BlockSchedule extends Component
{
    // ── Filter State ─────────────────────────────────────────────────────────
    public string $selectedDepartment = '';
    public string $selectedYear       = '1';
    public string $selectedSection    = 'A';
    public string $schoolYear         = '2026-2027';
    public string $semester           = '1st';
    public string $semesterName       = 'First Semester 2026-2027';

    // ── Faculty Assignment Modal ──────────────────────────────────────────────
    public bool   $showFacultyModal   = false;
    public string $modalPairingKey    = '';
    public array  $modalScheduleIds   = [];
    public ?int   $modalSubjectId     = null;
    public string $facultySearch      = '';
    public string $assignError        = '';
    public ?int   $currentFacultyId   = null;

    // ── Permission Toggle Modal ───────────────────────────────────────────────
    /** Controls visibility of the Admin permission toggle panel */
    public bool $showPermissionPanel  = false;

    // ── Flash / Status ────────────────────────────────────────────────────────
    public string $flashMessage       = '';
    public string $flashType          = '';   // 'success' | 'error' | 'warning'
    public array  $finalizationErrors = [];

    // ── Department Constants ──────────────────────────────────────────────────

    /**
     * Maps college codes → their managed majors.
     * Used for Dean/OIC scoping AND department-based faculty filtering.
     */
    protected const COLLEGE_DEPARTMENTS = [
        'CCS' => ['IT', 'ACT'],
        'COC' => ['FB', 'LD', 'QD'],
        'CED' => ['ED'],
        'CHM' => ['HM', 'TM'],
    ];

    protected const ALL_DEPARTMENTS = [
        'IT'  => 'IT - Information Technology',
        'ACT' => 'ACT - Associate in Computer Technology',
        'ED'  => 'ED - Education',
        'HM'  => 'HM - Hospitality Management',
        'TM'  => 'TM - Tourism Management',
        'FB'  => 'FB - Forensic Biology',
        'LD'  => 'LD - Lie Detection',
        'QD'  => 'QD - Questioned Document',
    ];

    protected $listeners = [
        'settings-updated'     => 'loadSettings',
        'refreshBlockSchedule' => '$refresh',
    ];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadSettings();
        $available = array_keys($this->getAvailableDepartments());
        $this->selectedDepartment = $available[0] ?? 'IT';
    }

    public function loadSettings(): void
    {
        $this->schoolYear   = Setting::getValue('school_year', '2026-2027');
        $this->semester     = Setting::getValue('semester', '1st');
        $this->semesterName = Setting::getValue('semester_name', 'First Semester 2026-2027');
    }

    // ── Role / Permission Helpers ─────────────────────────────────────────────

    public function getAvailableDepartments(): array
    {
        $user = Auth::user();

        if (! $user) {
            return self::ALL_DEPARTMENTS;
        }

        if (in_array($user->role, ['admin', 'registrar', 'associate_dean'], true)) {
            return self::ALL_DEPARTMENTS;
        }

        if (in_array($user->role, ['dean', 'oic'], true)) {
            $college  = strtoupper(trim((string) ($user->department ?? '')));
            $allowed  = self::COLLEGE_DEPARTMENTS[$college] ?? null;

            if ($allowed) {
                return array_intersect_key(self::ALL_DEPARTMENTS, array_flip($allowed));
            }
        }

        return self::ALL_DEPARTMENTS;
    }

    /**
     * Finalization permission.
     *
     * - Admin: always allowed.
     * - Registrar: only if `can_finalize_schedule` flag is TRUE on their user record.
     *   This flag is toggled by Admin via the permission panel on this page.
     * - All other roles: never.
     */
    public function canFinalizeSchedule(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        // Registrar delegation: Admin must explicitly enable this flag
        if ($user->role === 'registrar') {
            return (bool) ($user->can_finalize_schedule ?? false);
        }

        return false;
    }

    /**
     * Whether the current user can see and use the Admin permission toggle.
     * Only Admins see this control.
     */
    public function canManageRegistrarPermission(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    /**
     * Check if any Registrar currently has finalization permission enabled.
     * Returns the first matching registrar user or null.
     */
    public function getRegistrarWithPermission(): ?User
    {
        return User::where('role', 'registrar')
            ->where('can_finalize_schedule', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * All active Registrar accounts (for the permission panel list).
     */
    public function getAllRegistrars(): Collection
    {
        return User::where('role', 'registrar')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Dean, OIC, Associate Dean, Admin, and Registrar can assign faculty.
     * Faculty assignment is separate from finalization.
     */
    public function canAssignFaculty(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['admin', 'registrar', 'dean', 'oic', 'associate_dean'], true);
    }

    public function getDepartmentName(string $code): string
    {
        return self::ALL_DEPARTMENTS[$code] ?? $code;
    }

    /**
     * Determine which college owns a given department/major code.
     * Returns null for cross-college (GenEd) subjects.
     */
    protected function getCollegeForDepartment(string $deptCode): ?string
    {
        foreach (self::COLLEGE_DEPARTMENTS as $college => $depts) {
            if (in_array(strtoupper($deptCode), $depts, true)) {
                return $college;
            }
        }
        return null;
    }

    // ── Permission Toggle ─────────────────────────────────────────────────────

    /**
     * Open the registrar permission management panel.
     * Admin-only action.
     */
    public function openPermissionPanel(): void
    {
        if (! $this->canManageRegistrarPermission()) {
            $this->flashMessage = 'Only Administrators can manage Registrar permissions.';
            $this->flashType    = 'error';
            return;
        }

        $this->showPermissionPanel = true;
    }

    public function closePermissionPanel(): void
    {
        $this->showPermissionPanel = false;
    }

    /**
     * Grant finalization permission to a specific Registrar.
     *
     * Business rules:
     *  - Only one Registrar should have permission at a time (previous ones are revoked first)
     *  - Full audit log entry is created
     *
     * @param int $registrarUserId  The User ID of the Registrar to grant access to.
     */
    public function grantRegistrarPermission(int $registrarUserId): void
    {
        if (! $this->canManageRegistrarPermission()) {
            $this->flashMessage = 'Unauthorized: Only Administrators can grant permissions.';
            $this->flashType    = 'error';
            return;
        }

        $admin     = Auth::user();
        $registrar = User::where('role', 'registrar')
            ->where('is_active', true)
            ->find($registrarUserId);

        if (! $registrar) {
            $this->flashMessage = 'Registrar account not found or inactive.';
            $this->flashType    = 'error';
            return;
        }

        DB::transaction(function () use ($admin, $registrar) {
            // Revoke from all other registrars first (only one at a time)
            $previouslyGranted = User::where('role', 'registrar')
                ->where('can_finalize_schedule', true)
                ->where('id', '!=', $registrar->id)
                ->get();

            foreach ($previouslyGranted as $prev) {
                $prev->update(['can_finalize_schedule' => false]);

                // Log the implicit revocation
                PermissionLog::record(
                    action: PermissionLog::ACTION_REVOKE,
                    performer: $admin,
                    target: $prev,
                    context: ['reason' => 'Auto-revoked when permission transferred to another registrar'],
                    description: "{$admin->name} auto-revoked finalization access from {$prev->name} (transferred to {$registrar->name}).",
                );
            }

            // Grant to the selected registrar
            $registrar->update(['can_finalize_schedule' => true]);

            // Audit log
            PermissionLog::record(
                action: PermissionLog::ACTION_GRANT,
                performer: $admin,
                target: $registrar,
                context: [
                    'admin_name'     => $admin->name,
                    'registrar_name' => $registrar->name,
                    'registrar_id'   => $registrar->id,
                    'timestamp'      => now()->toIso8601String(),
                ],
                description: "{$admin->name} granted schedule finalization access to Registrar {$registrar->name}.",
            );
        });

        $this->flashMessage = "✅ Finalization access granted to {$registrar->name}. They can now finalize block schedules.";
        $this->flashType    = 'success';
        $this->closePermissionPanel();
    }

    /**
     * Revoke finalization permission from a specific Registrar.
     *
     * @param int $registrarUserId  The User ID of the Registrar to revoke from.
     */
    public function revokeRegistrarPermission(int $registrarUserId): void
    {
        if (! $this->canManageRegistrarPermission()) {
            $this->flashMessage = 'Unauthorized: Only Administrators can revoke permissions.';
            $this->flashType    = 'error';
            return;
        }

        $admin     = Auth::user();
        $registrar = User::find($registrarUserId);

        if (! $registrar) {
            $this->flashMessage = 'Registrar account not found.';
            $this->flashType    = 'error';
            return;
        }

        DB::transaction(function () use ($admin, $registrar) {
            $registrar->update(['can_finalize_schedule' => false]);

            PermissionLog::record(
                action: PermissionLog::ACTION_REVOKE,
                performer: $admin,
                target: $registrar,
                context: [
                    'admin_name'     => $admin->name,
                    'registrar_name' => $registrar->name,
                    'registrar_id'   => $registrar->id,
                    'timestamp'      => now()->toIso8601String(),
                ],
                description: "{$admin->name} revoked schedule finalization access from Registrar {$registrar->name}.",
            );
        });

        $this->flashMessage = "🚫 Finalization access revoked from {$registrar->name}. Only Administrators can now finalize.";
        $this->flashType    = 'warning';
        $this->closePermissionPanel();
    }

    // ── Faculty Assignment Modal ──────────────────────────────────────────────

    /**
     * Open the inline faculty assignment modal for a schedule group.
     */
    public function openFacultyModal(string $pairingKey, array $scheduleIds, int $subjectId): void
    {
        if (! $this->canAssignFaculty()) {
            $this->flashMessage = 'You do not have permission to assign faculty.';
            $this->flashType    = 'error';
            return;
        }

        // Block editing finalized schedules
        $isFinalized = Schedule::whereIn('id', $scheduleIds)
            ->where('status', Schedule::STATUS_FINALIZED)
            ->exists();

        if ($isFinalized) {
            $this->flashMessage = 'Finalized schedules are read-only. Ask an administrator to reopen.';
            $this->flashType    = 'error';
            return;
        }

        $this->modalPairingKey  = $pairingKey;
        $this->modalScheduleIds = $scheduleIds;
        $this->modalSubjectId   = $subjectId;
        $this->facultySearch    = '';
        $this->assignError      = '';
        $this->currentFacultyId = Schedule::find($scheduleIds[0] ?? null)?->faculty_id;
        $this->showFacultyModal = true;
    }

    public function closeFacultyModal(): void
    {
        $this->showFacultyModal  = false;
        $this->modalPairingKey   = '';
        $this->modalScheduleIds  = [];
        $this->modalSubjectId    = null;
        $this->facultySearch     = '';
        $this->assignError       = '';
        $this->currentFacultyId  = null;
    }

    /**
     * Assign a faculty member with full conflict detection.
     */
    public function assignFaculty(int $facultyId): void
    {
        if (! $this->canAssignFaculty()) {
            $this->assignError = 'You do not have permission to assign faculty.';
            return;
        }

        if (empty($this->modalScheduleIds) || ! $this->modalSubjectId) {
            $this->assignError = 'Invalid schedule group. Please close and try again.';
            return;
        }

        $faculty = Faculty::approved()->find($facultyId);
        $subject = Subject::find($this->modalSubjectId);

        if (! $faculty) {
            $this->assignError = 'Selected faculty not found or not approved.';
            return;
        }

        if (! $subject) {
            $this->assignError = 'Subject not found.';
            return;
        }

        // ── Eligibility check (department + scope) ──────────────────────────
        if (! $faculty->isEligibleForSubject($subject)) {
            $this->assignError = "Faculty {$faculty->full_name} is not eligible to teach "
                . "{$subject->subject_code} ({$subject->department}). "
                . "Check faculty department and scope settings.";
            return;
        }

        // ── Time conflict check across all schedule slots in the group ───────
        $schedules = Schedule::whereIn('id', $this->modalScheduleIds)->get();

        foreach ($schedules as $slot) {
            $clash = Schedule::activeTerm($this->semester, $this->schoolYear)
                ->where('faculty_id', $facultyId)
                ->where('day', $slot->day)
                ->whereNotIn('id', $this->modalScheduleIds)
                ->where('start_time', '<', $slot->end_time)
                ->where('end_time', '>', $slot->start_time)
                ->with('subject:id,subject_code')
                ->first();

            if ($clash) {
                $conflictCode = $clash->subject?->subject_code ?? "Schedule #{$clash->id}";
                $this->assignError = "Conflict detected: {$faculty->full_name} is already assigned "
                    . "to {$conflictCode} on {$slot->day} during this time slot. "
                    . "Please choose a different faculty.";
                return;
            }
        }

        // ── Persist assignment ────────────────────────────────────────────────
        DB::transaction(function () use ($facultyId, $subject) {
            Schedule::whereIn('id', $this->modalScheduleIds)
                ->update([
                    'faculty_id' => $facultyId,
                    'status'     => Schedule::STATUS_FACULTY_ASSIGNED,
                ]);
        });

        $this->flashMessage = "✓ {$faculty->full_name} assigned to {$subject->subject_code}.";
        $this->flashType    = 'success';
        $this->closeFacultyModal();
    }

    /**
     * Remove faculty assignment from a schedule group.
     */
    public function removeFacultyAssignment(): void
    {
        if (! $this->canAssignFaculty()) {
            $this->assignError = 'You do not have permission to modify faculty assignments.';
            return;
        }

        if (empty($this->modalScheduleIds)) {
            $this->assignError = 'No schedule selected.';
            return;
        }

        DB::transaction(function () {
            Schedule::whereIn('id', $this->modalScheduleIds)
                ->update([
                    'faculty_id' => null,
                    'status'     => Schedule::STATUS_PARTIAL,
                ]);
        });

        $this->flashMessage = 'Faculty assignment removed.';
        $this->flashType    = 'warning';
        $this->closeFacultyModal();
    }

    // ── Finalization ──────────────────────────────────────────────────────────

    /**
     * Finalize all schedule rows for the current filter selection.
     *
     * Pre-flight checks:
     *  ✅ All subjects have assigned faculty
     *  ✅ No faculty time conflicts
     *  ✅ No room time conflicts
     *  ✅ No duplicate subject assignments within the block
     */
    public function finalizeSchedule(): void
    {
        if (! $this->canFinalizeSchedule()) {
            $this->flashMessage = 'You do not have permission to finalize schedules.';
            $this->flashType    = 'error';
            return;
        }

        $errors = $this->runFinalizationPreflight();

        if (! empty($errors)) {
            $this->finalizationErrors = $errors;
            $this->flashMessage = 'Cannot finalize: resolve the listed issues first.';
            $this->flashType    = 'error';
            return;
        }

        // All checks passed — lock the schedules
        $scheduleIds = Schedule::activeTerm($this->semester, $this->schoolYear)
            ->whereIn('status', [Schedule::STATUS_PARTIAL, Schedule::STATUS_FACULTY_ASSIGNED])
            ->where('section', $this->selectedSection)
            ->whereHas('subject', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('department', $this->selectedDepartment)
                       ->orWhere('major', $this->selectedDepartment);
                })->where('year_level', (int) $this->selectedYear);
            })
            ->pluck('id');

        $user = Auth::user();

        DB::transaction(function () use ($scheduleIds, $user) {
            Schedule::whereIn('id', $scheduleIds)->update([
                'status' => Schedule::STATUS_FINALIZED,
            ]);

            // ── Audit Log: who finalized, which block ────────────────────────
            PermissionLog::record(
                action: PermissionLog::ACTION_FINALIZED,
                performer: $user,
                target: null,
                context: [
                    'department'   => $this->selectedDepartment,
                    'year_level'   => $this->selectedYear,
                    'section'      => $this->selectedSection,
                    'semester'     => $this->semester,
                    'school_year'  => $this->schoolYear,
                    'schedule_ids' => $scheduleIds->toArray(),
                    'count'        => $scheduleIds->count(),
                    'finalized_by' => $user?->name,
                    'role'         => $user?->role,
                    'timestamp'    => now()->toIso8601String(),
                ],
                description: "{$user?->name} ({$user?->role}) finalized {$scheduleIds->count()} schedule(s) for "
                    . "{$this->selectedDepartment} Year {$this->selectedYear} Section {$this->selectedSection} "
                    . "— {$this->semesterName}.",
            );
        });

        $this->finalizationErrors = [];
        $this->flashMessage       = '🔒 Schedule finalized successfully and locked for editing.';
        $this->flashType          = 'success';
    }

    /**
     * Run all pre-finalization validation checks.
     * Returns array of human-readable error strings (empty = all clear).
     */
    protected function runFinalizationPreflight(): array
    {
        $errors    = [];
        $cs        = app(ScheduleConflictService::class);

        $schedules = Schedule::activeTerm($this->semester, $this->schoolYear)
            ->whereIn('status', [Schedule::STATUS_PARTIAL, Schedule::STATUS_FACULTY_ASSIGNED])
            ->where('section', $this->selectedSection)
            ->whereHas('subject', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('department', $this->selectedDepartment)
                       ->orWhere('major', $this->selectedDepartment);
                })->where('year_level', (int) $this->selectedYear);
            })
            ->with(['subject:id,subject_code,units', 'faculty:id,full_name', 'room:id,room_name'])
            ->get();

        if ($schedules->isEmpty()) {
            $errors[] = 'No schedules found for the selected filters.';
            return $errors;
        }

        // Check 1: All subjects must have a faculty assigned
        $unassigned = $schedules->whereNull('faculty_id');
        foreach ($unassigned as $slot) {
            $errors[] = "Missing faculty: {$slot->subject?->subject_code} "
                . "on {$slot->day} has no assigned faculty.";
        }

        // Check 2: Faculty time conflicts within this block
        $byFaculty = $schedules->whereNotNull('faculty_id')->groupBy('faculty_id');
        foreach ($byFaculty as $facultyId => $slots) {
            $slotList = $slots->values();
            for ($i = 0; $i < $slotList->count(); $i++) {
                for ($j = $i + 1; $j < $slotList->count(); $j++) {
                    $a = $slotList[$i];
                    $b = $slotList[$j];
                    if ($a->day === $b->day && $cs->hasTimeOverlap(
                        (string) $a->start_time, (string) $a->end_time,
                        (string) $b->start_time, (string) $b->end_time
                    )) {
                        $name = $a->faculty?->full_name ?? "Faculty #{$facultyId}";
                        $errors[] = "Faculty conflict: {$name} is double-booked on {$a->day} "
                            . "({$a->subject?->subject_code} vs {$b->subject?->subject_code}).";
                    }
                }
            }
        }

        // Check 3: Room time conflicts within this block
        $byRoom = $schedules->groupBy('room_id');
        foreach ($byRoom as $roomId => $slots) {
            $slotList = $slots->values();
            for ($i = 0; $i < $slotList->count(); $i++) {
                for ($j = $i + 1; $j < $slotList->count(); $j++) {
                    $a = $slotList[$i];
                    $b = $slotList[$j];
                    if ($a->day === $b->day && $cs->hasTimeOverlap(
                        (string) $a->start_time, (string) $a->end_time,
                        (string) $b->start_time, (string) $b->end_time
                    )) {
                        $roomName = $a->room?->room_name ?? "Room #{$roomId}";
                        $errors[] = "Room conflict: {$roomName} is double-booked on {$a->day} "
                            . "({$a->subject?->subject_code} vs {$b->subject?->subject_code}).";
                    }
                }
            }
        }

        return array_unique($errors);
    }

    // ── Faculty List for Modal ────────────────────────────────────────────────

    /**
     * Returns eligible, searchable faculty for the modal's subject.
     */
    public function getEligibleFacultyForModal(): Collection
    {
        if (! $this->modalSubjectId) {
            return collect();
        }

        $subject = Subject::find($this->modalSubjectId);

        if (! $subject) {
            return collect();
        }

        $query = Faculty::approved()
            ->orderBy('full_name')
            ->select(['id', 'full_name', 'department', 'faculty_scope',
                      'max_units', 'employment_type', 'can_teach_minor']);

        if (! blank($this->facultySearch)) {
            $term = '%' . $this->facultySearch . '%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)
                  ->orWhere('department', 'like', $term);
            });
        }

        return $query->get()
            ->filter(fn ($f) => $f->isEligibleForSubject($subject))
            ->values();
    }

    /**
     * Calculate units already assigned to a faculty this term,
     * excluding the currently open schedule group to avoid double-counting.
     */
    public function getFacultyCurrentUnits(int $facultyId): int
    {
        return Schedule::activeTerm($this->semester, $this->schoolYear)
            ->where('faculty_id', $facultyId)
            ->whereNotIn('id', $this->modalScheduleIds)
            ->with('subject:id,units')
            ->get()
            ->unique('subject_id')
            ->sum(fn ($s) => (int) ($s->subject?->units ?? 0));
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $available = $this->getAvailableDepartments();

        if (! array_key_exists($this->selectedDepartment, $available)) {
            $this->selectedDepartment = array_key_first($available) ?? 'IT';
        }

        $allSchedules = Schedule::activeTerm($this->semester, $this->schoolYear)
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->where('section', $this->selectedSection)
            ->with(['subject', 'room', 'faculty'])
            ->get();

        $filteredSchedules = $allSchedules->filter(function ($schedule) {
            if (! $schedule->subject) {
                return false;
            }

            return (
                $schedule->subject->department === $this->selectedDepartment
                || $schedule->subject->major    === $this->selectedDepartment
            ) && (int) $schedule->subject->year_level === (int) $this->selectedYear;
        })->values();

        $dayOrder = Setting::getActiveDays();

        // Build grouped display rows (one row per subject pairing_key group)
        $scheduleRows = $filteredSchedules
            ->whereIn('day', $dayOrder)
            ->groupBy(function ($schedule) {
                return $schedule->pairing_key ?: implode('|', [
                    $schedule->subject_id,
                    $schedule->room_id,
                    $schedule->start_time,
                    $schedule->end_time,
                ]);
            })
            ->map(function ($group, $pairingKey) use ($dayOrder) {
                $first = $group->first();
                $days  = $group->pluck('day')
                    ->filter()
                    ->unique()
                    ->sortBy(fn ($day) => array_search($day, $dayOrder, true))
                    ->values()
                    ->all();

                // Quick per-row conflict detection for visual highlighting
                $hasConflict    = false;
                $conflictReason = '';

                if ($first->faculty_id) {
                    foreach ($group as $slot) {
                        $clash = Schedule::activeTerm($this->semester, $this->schoolYear)
                            ->where('faculty_id', $first->faculty_id)
                            ->where('day', $slot->day)
                            ->where('id', '!=', $slot->id)
                            ->where('start_time', '<', $slot->end_time)
                            ->where('end_time', '>', $slot->start_time)
                            ->exists();

                        if ($clash) {
                            $hasConflict    = true;
                            $conflictReason = 'Faculty has a time conflict on ' . $slot->day;
                            break;
                        }
                    }
                }

                return (object) [
                    'pairing_key'     => $pairingKey,
                    'ids'             => $group->pluck('id')->all(),
                    'subject'         => $first->subject,
                    'room'            => $first->room,
                    'faculty'         => $first->faculty,
                    'status'          => $first->status,
                    'start_time'      => $first->start_time,
                    'end_time'        => $first->end_time,
                    'days'            => $days,
                    'day_display'     => implode(' / ', $days),
                    'sort_day'        => $days[0] ?? $first->day,
                    'has_conflict'    => $hasConflict,
                    'conflict_reason' => $conflictReason,
                ];
            })
            ->sort(function ($a, $b) use ($dayOrder) {
                $dayCompare = array_search($a->sort_day, $dayOrder, true)
                          <=> array_search($b->sort_day, $dayOrder, true);

                return $dayCompare !== 0
                    ? $dayCompare
                    : strcmp((string) $a->start_time, (string) $b->start_time);
            })
            ->values();

        $schedules      = $filteredSchedules->whereIn('day', $dayOrder)->groupBy('day');
        $departmentName = $this->getDepartmentName($this->selectedDepartment);

        $allFinalized    = $scheduleRows->isNotEmpty()
            && $scheduleRows->every(fn ($r) => $r->status === Schedule::STATUS_FINALIZED);

        $unassignedCount = $scheduleRows->filter(fn ($r) => is_null($r->faculty))->count();
        $conflictCount   = $scheduleRows->filter(fn ($r) => $r->has_conflict)->count();

        $modalFaculty = $this->showFacultyModal ? $this->getEligibleFacultyForModal() : collect();
        $modalSubject = $this->modalSubjectId ? Subject::find($this->modalSubjectId) : null;

        // ── Permission panel data (Admin-only) ───────────────────────────────
        $allRegistrars          = $this->canManageRegistrarPermission() ? $this->getAllRegistrars() : collect();
        $registrarWithPermission = $this->getRegistrarWithPermission();

        // ── Recent permission audit log (last 10 events, Admin-only) ─────────
        $recentPermissionLogs = $this->canManageRegistrarPermission()
            ? PermissionLog::with(['performer:id,name', 'targetUser:id,name'])
                ->latest()
                ->take(10)
                ->get()
            : collect();

        return view('livewire.block-schedule', [
            'schedules'               => $schedules,
            'scheduleRows'            => $scheduleRows,
            'departmentName'          => $departmentName,
            'availableDepts'          => $available,
            'canFinalize'             => $this->canFinalizeSchedule(),
            'canAssign'               => $this->canAssignFaculty(),
            'canManagePermission'     => $this->canManageRegistrarPermission(),
            'allFinalized'            => $allFinalized,
            'unassignedCount'         => $unassignedCount,
            'conflictCount'           => $conflictCount,
            'totalRows'               => $scheduleRows->count(),
            'modalFaculty'            => $modalFaculty,
            'modalSubject'            => $modalSubject,
            'allRegistrars'           => $allRegistrars,
            'registrarWithPermission' => $registrarWithPermission,
            'recentPermissionLogs'    => $recentPermissionLogs,
        ]);
    }
}