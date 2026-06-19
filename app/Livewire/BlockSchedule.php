<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\PermissionLog;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleRevisionRequest;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\RevisionRequestNotification;
use App\Services\ScheduleConflictService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class BlockSchedule extends Component
{
    // ── Filter State ────────────────────────────────────────────────────────
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
    public array  $facultyAssignmentSuggestions = [];

    // ── Permission Toggle Modal ───────────────────────────────────────────────
    public bool $showPermissionPanel  = false;

    // ── Flash / Status ───────────────────────────────────────────────────────
    public string $flashMessage       = '';
    public string $flashType          = '';   // 'success' | 'error' | 'warning'
    public array  $finalizationErrors = [];

    // ── Workspace Editing ────────────────────────────────────────────────────
    public bool   $workspaceEditMode = false;
    public bool   $workspaceValidationActive = false;
    public array  $workspaceEdits = [];
    public array  $workspaceSnapshot = [];  // NEW: stores original state for cancel
    public array  $workspaceRealTimeConflicts = [];  // NEW: real-time conflict tracking
    public array  $workspaceConflictErrors = [];
    public array  $workspaceConflictKeys = [];
    public array  $workspaceRecommendations = [];
    public bool   $showWorkspaceConflictModal = false;

    // ── Revision Modal ──────────────────────────────────────────────────────
    public bool $showRevisionModal = false;
    public array $revisionScheduleIds = [];
    public ?int $revisionSubjectId = null;
    public ?int $revisionCurrentFacultyId = null;
    public ?int $revisionRequestedFacultyId = null;
    public string $revisionReason = '';
    public string $revisionError = '';
    public string $revisionRejectionNote = '';  // Optional note when rejecting a pending request

    // ── Department Constants ──────────────────────────────────────────────────

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

    // ── Lifecycle ──────────────────────────────────────────────────────────

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

    public function canFinalizeSchedule(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'registrar') {
            return (bool) ($user->can_finalize_schedule ?? false);
        }

        return false;
    }

    public function canManageRegistrarPermission(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    public function getRegistrarWithPermission(): ?User
    {
        return User::where('role', 'registrar')
            ->where('can_finalize_schedule', true)
            ->where('is_active', true)
            ->first();
    }

    public function getAllRegistrars(): Collection
    {
        return User::where('role', 'registrar')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function canAssignFaculty(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['admin', 'registrar', 'dean', 'oic', 'associate_dean'], true);
    }

    /**
     * UPDATED: Admin, Registrar, Dean, OIC, and Associate Dean can edit workspace.
     * This allows them to reorganize schedules, edit rooms, faculty, days, and times.
     * Conflict detection runs in REAL TIME during editing.
     */
    public function canEditWorkspace(): bool
    {
        $role = Auth::user()?->role;

        return in_array($role, ['admin', 'registrar', 'dean', 'oic', 'associate_dean'], true);
    }

    public function canRequestRevision(): bool
    {
        $role = Auth::user()?->role;

        return in_array($role, ['dean', 'oic', 'associate_dean'], true);
    }

    public function canReviewRevisionRequests(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->role === 'admin'
            || ($user->role === 'registrar' && (bool) ($user->can_finalize_schedule ?? false));
    }

    public function getDepartmentName(string $code): string
    {
        return self::ALL_DEPARTMENTS[$code] ?? $code;
    }

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
            $previouslyGranted = User::where('role', 'registrar')
                ->where('can_finalize_schedule', true)
                ->where('id', '!=', $registrar->id)
                ->get();

            foreach ($previouslyGranted as $prev) {
                $prev->update(['can_finalize_schedule' => false]);

                PermissionLog::record(
                    action: PermissionLog::ACTION_REVOKE,
                    performer: $admin,
                    target: $prev,
                    context: ['reason' => 'Auto-revoked when permission transferred to another registrar'],
                    description: "{$admin->name} auto-revoked finalization access from {$prev->name} (transferred to {$registrar->name}).",
                );
            }

            $registrar->update(['can_finalize_schedule' => true]);

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

    // ── Workspace Editing ──────────────────────────────────────────────────────

    /**
     * NEW: Start workspace edit mode with snapshot capture.
     *
     * - Creates snapshot of current workspace state
     * - Enables real-time conflict detection
     * - Allows free editing without blocking
     */
    public function startWorkspaceEdit(): void
    {
        if (! $this->canEditWorkspace()) {
            $this->flashMessage = 'Only authorized roles (Admin, Registrar, Dean, OIC, Associate Dean) can edit the scheduling workspace.';
            $this->flashType = 'error';
            return;
        }

        // Create snapshot before starting edits
        $this->workspaceSnapshot = $this->buildWorkspaceEditState();
        $this->workspaceEdits = $this->buildWorkspaceEditState();
        $this->workspaceRealTimeConflicts = [];
        $this->workspaceConflictErrors = [];
        $this->workspaceConflictKeys = [];
        $this->workspaceRecommendations = [];
        $this->workspaceValidationActive = false;
        $this->showWorkspaceConflictModal = false;
        $this->workspaceEditMode = true;

        $this->flashMessage = 'Edit Mode Active. Conflicts are detected in real time while you edit.';
        $this->flashType = 'info';
    }

    /**
     * Livewire lifecycle hook — fires automatically whenever any nested key
     * inside $workspaceEdits changes (room_id, faculty_id, day_string,
     * start_time, end_time).  This is the entry-point for REAL-TIME conflict
     * detection: no button click required.
     *
     * Livewire v3 calls updatedWorkspaceEdits($value, $key) where $key is the
     * dot-notation sub-path that changed, e.g. "abc123.room_id".
     * We ignore both parameters and simply re-scan all rows so every row is
     * always up to date with the current edit state.
     */
    public function updatedWorkspaceEdits(): void
    {
        $this->updateWorkspaceRealTimeConflicts();
    }

    /**
     * Re-evaluates all workspace rows for conflicts and stores the result in
     * $workspaceRealTimeConflicts so the blade can highlight rows immediately.
     *
     * Called automatically via updatedWorkspaceEdits() on every field change,
     * and also manually from startWorkspaceEdit() to initialise the state.
     * Never blocks editing — conflict data is purely informational.
     */
    public function updateWorkspaceRealTimeConflicts(): void
    {
        if (! $this->workspaceEditMode) {
            return;
        }

        $this->workspaceRealTimeConflicts = $this->detectWorkspaceConflicts($this->workspaceRowsForValidation());
    }

    /**
     * NEW: Detect conflicts in real-time while editing (non-blocking).
     *
     * Returns array keyed by edit_key with conflict type and messages.
     */
    private function detectWorkspaceConflicts(array $rows): array
    {
        $conflicts = [];
        $cs = app(ScheduleConflictService::class);

        foreach ($rows as $row) {
            $editKey = $row['edit_key'] ?? '';
            $conflictTypes = [];
            $conflictMessages = [];

            if (empty($editKey)) {
                continue;
            }

            $roomId = filled($row['room_id'] ?? null) ? (int) $row['room_id'] : null;
            $facultyId = filled($row['faculty_id'] ?? null) ? (int) $row['faculty_id'] : null;
            $days = $row['days'] ?? [];

            if (! $roomId || empty($days)) {
                continue;
            }

            // Check room conflicts
            foreach ($days as $day) {
                $roomConflict = Schedule::activeTerm($this->semester, $this->schoolYear)
                    ->where('room_id', $roomId)
                    ->where('day', $day)
                    ->whereNotIn('pairing_key', [$row['pairing_key'] ?? ''])
                    ->where('start_time', '<', $row['end_time'] ?? '23:59')
                    ->where('end_time', '>', $row['start_time'] ?? '00:00')
                    ->where('status', '!=', Schedule::STATUS_FINALIZED)
                    ->exists();

                if ($roomConflict) {
                    $conflictTypes[] = 'ROOM CONFLICT';
                    $conflictMessages[] = "Room is occupied on {$day} during this time.";
                    break;
                }
            }

            // Check faculty conflicts
            if ($facultyId && empty($conflictTypes)) {
                foreach ($days as $day) {
                    $facultyConflict = Schedule::activeTerm($this->semester, $this->schoolYear)
                        ->where('faculty_id', $facultyId)
                        ->where('day', $day)
                        ->whereNotIn('pairing_key', [$row['pairing_key'] ?? ''])
                        ->where('start_time', '<', $row['end_time'] ?? '23:59')
                        ->where('end_time', '>', $row['start_time'] ?? '00:00')
                        ->where('status', '!=', Schedule::STATUS_FINALIZED)
                        ->exists();

                    if ($facultyConflict) {
                        $conflictTypes[] = 'FACULTY CONFLICT';
                        $conflictMessages[] = "Faculty is double-booked on {$day}.";
                        break;
                    }
                }
            }

            // Check section time conflicts
            if (empty($conflictTypes)) {
                $subject = Subject::find($row['subject_id'] ?? null);
                if ($subject) {
                    foreach ($days as $day) {
                        $sectionConflict = Schedule::activeTerm($this->semester, $this->schoolYear)
                            ->where('day', $day)
                            ->whereNotIn('pairing_key', [$row['pairing_key'] ?? ''])
                            ->where('department', $subject->department)
                            ->where('major', $subject->major)
                            ->where('year_level', $subject->year_level)
                            ->where('section', $subject->section)
                            ->where('start_time', '<', $row['end_time'] ?? '23:59')
                            ->where('end_time', '>', $row['start_time'] ?? '00:00')
                            ->where('status', '!=', Schedule::STATUS_FINALIZED)
                            ->exists();

                        if ($sectionConflict) {
                            $conflictTypes[] = 'TIME CONFLICT';
                            $conflictMessages[] = "Section has another class on {$day}.";
                            break;
                        }
                    }
                }
            }

            if (! empty($conflictTypes)) {
                $conflicts[$editKey] = [
                    'types' => array_unique($conflictTypes),
                    'messages' => $conflictMessages,
                    'severity' => count($conflictTypes) > 1 ? 'critical' : 'warning',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * NEW: Cancel all edits and restore snapshot.
     */
    public function cancelWorkspaceEdit(): void
    {
        if (! $this->workspaceEditMode) {
            return;
        }

        $this->workspaceEditMode = false;
        $this->workspaceSnapshot = [];
        $this->workspaceEdits = [];
        $this->workspaceRealTimeConflicts = [];
        $this->workspaceConflictErrors = [];
        $this->workspaceConflictKeys = [];
        $this->workspaceRecommendations = [];
        $this->workspaceValidationActive = false;
        $this->showWorkspaceConflictModal = false;

        $this->flashMessage = 'Edit mode cancelled. All changes discarded.';
        $this->flashType = 'warning';
    }

    /**
     * UPDATED: Finish workspace edit and validate against ALL conflicts.
     *
     * When user clicks "Done Editing":
     * 1. Re-run full conflict validation
     * 2. If conflicts found: show summary modal, don't save
     * 3. If clean: persist edits and reset state
     */
    public function finishWorkspaceEdit(): void
    {
        if (! $this->workspaceEditMode || ! $this->canEditWorkspace()) {
            return;
        }

        $validation = app(ScheduleConflictService::class)->validateEditableWorkspace(
            $this->workspaceRowsForValidation(),
            $this->semester,
            $this->schoolYear
        );

        if (! ($validation['valid'] ?? false)) {
            $this->workspaceConflictErrors = $validation['errors'] ?? [];
            $this->workspaceConflictKeys = array_fill_keys($validation['conflict_keys'] ?? [], true);
            $this->workspaceRecommendations = $validation['recommendations'] ?? [];
            $this->workspaceValidationActive = true;
            $this->showWorkspaceConflictModal = true;
            $this->flashMessage = 'Conflicts found. Fix highlighted rows, then try Done Editing again.';
            $this->flashType = 'error';
            return;
        }

        DB::transaction(fn () => $this->persistWorkspaceEdits());

        $this->workspaceEditMode = false;
        $this->workspaceSnapshot = [];
        $this->workspaceEdits = [];
        $this->workspaceRealTimeConflicts = [];
        $this->workspaceValidationActive = false;
        $this->workspaceConflictErrors = [];
        $this->workspaceConflictKeys = [];
        $this->workspaceRecommendations = [];
        $this->showWorkspaceConflictModal = false;

        $this->flashMessage = '✅ Workspace edits saved successfully. Conflict detection re-enabled.';
        $this->flashType = 'success';
    }

    public function closeWorkspaceConflictModal(): void
    {
        $this->showWorkspaceConflictModal = false;
    }

    private function buildWorkspaceEditState(): array
    {
        $dayOrder = Setting::getActiveDays();

        return $this->currentBlockSchedules()
            ->where('status', '!=', Schedule::STATUS_FINALIZED)
            ->groupBy(fn (Schedule $schedule) => $schedule->pairing_key ?: implode('|', [
                $schedule->subject_id,
                $schedule->room_id,
                $schedule->start_time,
                $schedule->end_time,
            ]))
            ->mapWithKeys(function (Collection $group, string $pairingKey) use ($dayOrder) {
                $first = $group->first();
                $days = $group->pluck('day')
                    ->filter()
                    ->unique()
                    ->sortBy(fn ($day) => array_search($day, $dayOrder, true))
                    ->values()
                    ->all();
                $editKey = $this->workspaceEditKey($pairingKey);

                return [
                    $editKey => [
                        'edit_key' => $editKey,
                        'pairing_key' => $pairingKey,
                        'schedule_ids' => $group->pluck('id')->all(),
                        'subject_id' => $first->subject_id,
                        'room_id' => $first->room_id,
                        'faculty_id' => $first->faculty_id,
                        'day_string' => implode(', ', $days),
                        'start_time' => Carbon::parse($first->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($first->end_time)->format('H:i'),
                        'label' => $first->subject?->subject_code ?? 'Schedule row',
                    ],
                ];
            })
            ->all();
    }

    private function workspaceRowsForValidation(): array
    {
        return collect($this->workspaceEdits)
            ->map(function (array $row) {
                $row['days'] = $this->parseWorkspaceDays($row['day_string'] ?? '');
                $row['room_id'] = filled($row['room_id'] ?? null) ? (int) $row['room_id'] : null;
                $row['faculty_id'] = filled($row['faculty_id'] ?? null) ? (int) $row['faculty_id'] : null;

                return $row;
            })
            ->values()
            ->all();
    }

    private function persistWorkspaceEdits(): void
    {
        foreach ($this->workspaceRowsForValidation() as $row) {
            $subject = Subject::find($row['subject_id'] ?? null);
            $roomId = (int) ($row['room_id'] ?? 0);
            $facultyId = filled($row['faculty_id'] ?? null) ? (int) $row['faculty_id'] : null;
            $days = array_values($row['days'] ?? []);
            $scheduleIds = array_values($row['schedule_ids'] ?? []);

            if (! $subject || ! $roomId || empty($days)) {
                continue;
            }

            $start = Carbon::parse($row['start_time'])->format('H:i:s');
            $end = Carbon::parse($row['end_time'])->format('H:i:s');
            $pairingKey = $row['pairing_key'] ?? ('workspace-' . $subject->id);

            foreach ($days as $index => $day) {
                $schedule = isset($scheduleIds[$index])
                    ? Schedule::activeTerm($this->semester, $this->schoolYear)->whereKey($scheduleIds[$index])->first()
                    : new Schedule([
                        'subject_id' => $subject->id,
                        'department' => $subject->department,
                        'major' => $subject->major,
                        'year_level' => $subject->year_level,
                        'section' => $subject->section,
                        'pairing_key' => $pairingKey,
                        'edp_code' => $subject->edp_code,
                        'semester' => $this->semester,
                        'school_year' => $this->schoolYear,
                        'academic_year' => $this->schoolYear,
                        'workspace_key' => Setting::workspaceKey($this->schoolYear, $this->semester),
                        'is_archived' => false,
                    ]);

                if (! $schedule || $schedule->status === Schedule::STATUS_FINALIZED) {
                    continue;
                }

                $schedule->fill([
                    'room_id' => $roomId,
                    'faculty_id' => $facultyId,
                    'day' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_hours' => round(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60, 2),
                    'meetings_per_week' => count($days),
                    'status' => $facultyId ? Schedule::STATUS_FACULTY_ASSIGNED : Schedule::STATUS_PARTIAL,
                ])->save();
            }

            $extraIds = array_slice($scheduleIds, count($days));
            if (! empty($extraIds)) {
                Schedule::activeTerm($this->semester, $this->schoolYear)
                    ->whereIn('id', $extraIds)
                    ->where('status', '!=', Schedule::STATUS_FINALIZED)
                    ->delete();
            }
        }
    }

    private function parseWorkspaceDays(string $value): array
    {
        return collect(preg_split('/[,|\/]+/', $value) ?: [])
            ->map(fn ($day) => Setting::normalizeDayName(trim($day)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function workspaceEditKey(string $pairingKey): string
    {
        return md5($pairingKey);
    }

    private function currentBlockSchedules(): Collection
    {
        return Schedule::activeTerm($this->semester, $this->schoolYear)
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->where('section', $this->selectedSection)
            ->with(['subject', 'room', 'faculty'])
            ->get()
            ->filter(function (Schedule $schedule) {
                if (! $schedule->subject) {
                    return false;
                }

                return (
                    $schedule->subject->department === $this->selectedDepartment
                    || $schedule->subject->major === $this->selectedDepartment
                ) && (int) $schedule->subject->year_level === (int) $this->selectedYear;
            })
            ->values();
    }

    // ── Faculty Assignment Modal ───────────────────────────────────────────────

    public function openFacultyModal(string $pairingKey, array $scheduleIds, int $subjectId): void
    {
        if (! $this->canAssignFaculty()) {
            $this->flashMessage = 'You do not have permission to assign faculty.';
            $this->flashType    = 'error';
            return;
        }

        $hasFinalizedRows = Schedule::whereIn('id', $scheduleIds)
            ->where('status', Schedule::STATUS_FINALIZED)
            ->exists();

        if ($hasFinalizedRows && $this->canRequestRevision() && ! $this->canReviewRevisionRequests()) {
            $this->flashMessage = 'Finalized faculty changes must be submitted as revision requests.';
            $this->flashType = 'warning';
            return;
        }

        $this->modalPairingKey  = $pairingKey;
        $this->modalScheduleIds = $scheduleIds;
        $this->modalSubjectId   = $subjectId;
        $this->facultySearch    = '';
        $this->assignError      = '';
        $this->facultyAssignmentSuggestions = [];
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
        $this->facultyAssignmentSuggestions = [];
        $this->currentFacultyId  = null;
    }

    public function openRevisionModal(array $scheduleIds, int $subjectId): void
    {
        if (! $this->canRequestRevision()) {
            $this->flashMessage = 'Only Dean, OIC, and Associate Dean accounts can request faculty revisions.';
            $this->flashType = 'error';
            return;
        }

        $schedule = Schedule::whereIn('id', $scheduleIds)
            ->where('status', Schedule::STATUS_FINALIZED)
            ->first();

        if (! $schedule) {
            $this->flashMessage = 'Revision requests are only required for finalized schedules.';
            $this->flashType = 'warning';
            return;
        }

        $this->revisionScheduleIds = $scheduleIds;
        $this->revisionSubjectId = $subjectId;
        $this->revisionCurrentFacultyId = $schedule->faculty_id;
        $this->revisionRequestedFacultyId = null;
        $this->revisionReason = '';
        $this->revisionError = '';
        $this->showRevisionModal = true;
    }

    public function closeRevisionModal(): void
    {
        $this->showRevisionModal            = false;
        $this->revisionScheduleIds          = [];
        $this->revisionSubjectId            = null;
        $this->revisionCurrentFacultyId     = null;
        $this->revisionRequestedFacultyId   = null;
        $this->revisionReason               = '';
        $this->revisionError                = '';
        $this->revisionRejectionNote        = '';
    }

    public function submitRevisionRequest(): void
    {
        $user = Auth::user();
        $subject = Subject::find($this->revisionSubjectId);
        $requestedFaculty = $this->revisionRequestedFacultyId
            ? Faculty::approved()->find($this->revisionRequestedFacultyId)
            : null;
        $currentFaculty = $this->revisionCurrentFacultyId
            ? Faculty::find($this->revisionCurrentFacultyId)
            : null;

        if (! $user || ! $this->canRequestRevision() || ! $subject || ! $requestedFaculty) {
            $this->revisionError = 'Choose a valid requested faculty member.';
            return;
        }

        if (trim($this->revisionReason) === '') {
            $this->revisionError = 'A reason is required for finalized schedule revisions.';
            return;
        }

        if (! $requestedFaculty->isEligibleForSubject($subject)) {
            $this->revisionError = "{$requestedFaculty->full_name} is not eligible for {$subject->subject_code}.";
            return;
        }

        // ── Faculty scheduling conflict check ─────────────────────────────────
        // Verify the requested faculty has no overlapping schedule in the same
        // time slots this revision would assign them to.
        if (! empty($this->revisionScheduleIds)) {
            $conflictingSlots = Schedule::activeTerm($this->semester, $this->schoolYear)
                ->whereIn('id', $this->revisionScheduleIds)
                ->get(['id', 'day', 'start_time', 'end_time', 'pairing_key']);

            foreach ($conflictingSlots as $slot) {
                $startTime = \Carbon\Carbon::parse($slot->start_time)->format('H:i:s');
                $endTime   = \Carbon\Carbon::parse($slot->end_time)->format('H:i:s');

                // Check if the requested faculty is already teaching in this day/time window,
                // excluding the current slot itself (and its paired slots via pairing_key).
                $facultyBusy = Schedule::activeTerm($this->semester, $this->schoolYear)
                    ->where('faculty_id', $requestedFaculty->id)
                    ->where('day', $slot->day)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->when(
                        filled($slot->pairing_key),
                        fn ($q) => $q->where('pairing_key', '!=', $slot->pairing_key)
                    )
                    ->whereNotIn('id', $this->revisionScheduleIds)
                    ->with('subject:id,subject_code,description')
                    ->first();

                if ($facultyBusy) {
                    $conflictSubjectCode = $facultyBusy->subject?->subject_code ?? 'another subject';
                    $conflictDay         = $slot->day;
                    $conflictStart       = \Carbon\Carbon::parse($slot->start_time)->format('h:i A');
                    $conflictEnd         = \Carbon\Carbon::parse($slot->end_time)->format('h:i A');

                    $this->revisionError =
                        "⚠ Faculty conflict detected: {$requestedFaculty->full_name} is already assigned to "
                        . "{$conflictSubjectCode} on {$conflictDay} from {$conflictStart} to {$conflictEnd}. "
                        . "Please select a different faculty.";
                    return;
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        $existingPending = ScheduleRevisionRequest::forWorkspace($this->semester, $this->schoolYear)
            ->where('subject_id', $subject->id)
            ->where('requested_faculty_id', $requestedFaculty->id)
            ->pending()
            ->exists();

        if ($existingPending) {
            $this->revisionError = 'A matching revision request is already pending review.';
            return;
        }

        $revisionRequest = app(ScheduleService::class)->generateRevisionRequest(
            $subject,
            $currentFaculty,
            $requestedFaculty,
            $this->revisionScheduleIds,
            $user,
            $this->revisionReason
        );

        // ── Notify Admins / Registrars with full subject + faculty context ────
        $roleLabel        = $this->formatRoleLabel($user->role);
        $currentFacName   = $currentFaculty?->full_name ?? 'Unassigned';
        $requestedFacName = $requestedFaculty->full_name;
        $subjectLabel     = "{$subject->subject_code} — {$subject->description}";

        $recipients = User::where('role', 'admin')
            ->orWhere(function ($query) {
                $query->where('role', 'registrar')
                    ->where('can_finalize_schedule', true);
            })
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new GeneralNotification([
                'title'             => 'Faculty Revision Request',
                'message'           => "{$roleLabel} {$user->name} requested a faculty change for"
                                    . " {$subjectLabel}."
                                    . " Current: {$currentFacName} → Requested: {$requestedFacName}."
                                    . " Reason: {$this->revisionReason}",
                'type'              => 'warning',
                'url'               => route('block-schedule'),
                'sender_name'       => $user->name,

                // Structured fields stored in the notification data column
                // for richer rendering in the notification center.
                'subject_code'      => $subject->subject_code,
                'subject_name'      => $subject->description,
                'section'           => $subject->section,
                'year_level'        => $subject->year_level,
                'semester'          => $this->semester,
                'school_year'       => $this->schoolYear,
                'current_faculty'   => $currentFacName,
                'requested_faculty' => $requestedFacName,
                'requester_name'    => $user->name,
                'requester_role'    => $roleLabel,
                'request_id'        => $revisionRequest->id,
            ]));
        }

        // ── Auto-close modal and return user to Block Schedule ────────────────
        $this->closeRevisionModal();

        $this->flashMessage = '✅ Revision request submitted successfully. Admin/Registrar will review shortly.';
        $this->flashType    = 'success';

        // Refresh the notification center if it is mounted on the same page.
        $this->dispatch('notify');
    }

    public function approveRevisionRequest(int $requestId): void
    {
        if (! $this->canReviewRevisionRequests()) {
            $this->flashMessage = 'You do not have permission to approve revision requests.';
            $this->flashType    = 'error';
            return;
        }

        $request = ScheduleRevisionRequest::pending()
            ->with([
                'subject:id,subject_code,description,section,year_level',
                'requester:id,name,role',
                'currentFaculty:id,full_name',
                'requestedFaculty:id,full_name',
            ])
            ->find($requestId);

        if (! $request) {
            $this->flashMessage = 'Revision request not found or already reviewed.';
            $this->flashType    = 'warning';
            return;
        }

        $reviewer = Auth::user();

        app(ScheduleService::class)->approveRevisionRequest($request, $reviewer);

        // ── Notify the original requester ─────────────────────────────────────
        $requester = $request->requester;
        if ($requester) {
            $requester->notify(RevisionRequestNotification::approved([
                'subject_code'      => $request->subject?->subject_code ?? 'N/A',
                'subject_name'      => $request->subject?->description  ?? '',
                'current_faculty'   => $request->currentFaculty?->full_name   ?? 'Unassigned',
                'requested_faculty' => $request->requestedFaculty?->full_name ?? 'N/A',
                'requester_name'    => $requester->name,
                'requester_role'    => $requester->role,
                'reviewer_name'     => $reviewer->name,
                'review_note'       => $request->review_note,
                'url'               => route('block-schedule'),
            ]));

            // Tell the notification center to reload on the requester's session.
            // (Works if both users share the same broadcast channel / polling.)
            $this->dispatch('notify');
        }

        $subjectCode      = $request->subject?->subject_code      ?? 'subject';
        $requestedFacName = $request->requestedFaculty?->full_name ?? 'faculty';

        $this->flashMessage = "✅ Revision approved. {$requestedFacName} is now assigned to {$subjectCode}.";
        $this->flashType    = 'success';
    }

    public function rejectRevisionRequest(int $requestId): void
    {
        if (! $this->canReviewRevisionRequests()) {
            $this->flashMessage = 'You do not have permission to reject revision requests.';
            $this->flashType    = 'error';
            return;
        }

        $request = ScheduleRevisionRequest::pending()
            ->with([
                'subject:id,subject_code,description,section,year_level',
                'requester:id,name,role',
                'currentFaculty:id,full_name',
                'requestedFaculty:id,full_name',
            ])
            ->find($requestId);

        if (! $request) {
            $this->flashMessage = 'Revision request not found or already reviewed.';
            $this->flashType    = 'warning';
            return;
        }

        $reviewer = Auth::user();

        app(ScheduleService::class)->rejectRevisionRequest($request, $reviewer);

        // ── Notify the original requester ─────────────────────────────────────
        $requester = $request->requester;
        if ($requester) {
            $requester->notify(RevisionRequestNotification::rejected([
                'subject_code'      => $request->subject?->subject_code ?? 'N/A',
                'subject_name'      => $request->subject?->description  ?? '',
                'current_faculty'   => $request->currentFaculty?->full_name   ?? 'Unassigned',
                'requested_faculty' => $request->requestedFaculty?->full_name ?? 'N/A',
                'requester_name'    => $requester->name,
                'requester_role'    => $requester->role,
                'reviewer_name'     => $reviewer->name,
                'review_note'       => $request->review_note,
                'url'               => route('block-schedule'),
            ]));

            $this->dispatch('notify');
        }

        $subjectCode      = $request->subject?->subject_code      ?? 'subject';
        $requestedFacName = $request->requestedFaculty?->full_name ?? 'faculty';

        $this->flashMessage = "Revision request for {$requestedFacName} / {$subjectCode} has been rejected.";
        $this->flashType    = 'warning';
    }

    public function assignFaculty(int $facultyId): void
    {
        $this->facultyAssignmentSuggestions = [];

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

        if (! $faculty->isEligibleForSubject($subject)) {
            $this->assignError = "Faculty {$faculty->full_name} is not eligible to teach "
                . "{$subject->subject_code} ({$subject->department}). "
                . "Check faculty department and scope settings.";
            return;
        }

        $schedules = Schedule::whereIn('id', $this->modalScheduleIds)
            ->with(['subject:id,subject_code,department,major,year_level,section', 'room:id,room_name'])
            ->get();

        if ($schedules->isEmpty()) {
            $this->assignError = 'No schedule rows found for this subject.';
            return;
        }

        $conflictService = app(ScheduleConflictService::class);

        foreach ($schedules as $slot) {
            $roomConflict = Schedule::activeTerm($this->semester, $this->schoolYear)
                ->where('room_id', $slot->room_id)
                ->where('day', $slot->day)
                ->whereNotIn('id', $this->modalScheduleIds)
                ->where('start_time', '<', $slot->end_time)
                ->where('end_time', '>', $slot->start_time)
                ->with('subject:id,subject_code')
                ->first();

            if ($roomConflict) {
                $this->assignError = "Room conflict detected: {$slot->room?->room_name} is already occupied on {$slot->day} during this time slot.";
                $this->facultyAssignmentSuggestions = $this->facultyAssignmentRecommendations($subject, $schedules, $facultyId);
                return;
            }

            $sectionConflict = Schedule::activeTerm($this->semester, $this->schoolYear)
                ->where('day', $slot->day)
                ->whereNotIn('id', $this->modalScheduleIds)
                ->where('department', $slot->department)
                ->where('major', $slot->major)
                ->where('year_level', $slot->year_level)
                ->where('section', $slot->section)
                ->where('start_time', '<', $slot->end_time)
                ->where('end_time', '>', $slot->start_time)
                ->with('subject:id,subject_code')
                ->first();

            if ($sectionConflict) {
                $this->assignError = "Section conflict detected: {$slot->section} already has another class on {$slot->day} during this time slot.";
                $this->facultyAssignmentSuggestions = $this->facultyAssignmentRecommendations($subject, $schedules, $facultyId);
                return;
            }

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
                $this->facultyAssignmentSuggestions = $this->facultyAssignmentRecommendations($subject, $schedules, $facultyId);
                return;
            }

            $availability = $conflictService->checkFacultyAvailability(
                $faculty,
                $slot->day,
                (string) $slot->start_time,
                (string) $slot->end_time,
                $subject,
                $slot->room,
                $slot->id
            );

            if (($availability['status'] ?? true) === false) {
                $this->assignError = $availability['message'] ?? "{$faculty->full_name} is not available for one or more selected schedule slots.";
                $this->facultyAssignmentSuggestions = $this->facultyAssignmentRecommendations($subject, $schedules, $facultyId);
                return;
            }
        }

        DB::transaction(function () use ($facultyId, $schedules) {
            foreach ($schedules as $slot) {
                $updates = ['faculty_id' => $facultyId];

                if ($slot->status !== Schedule::STATUS_FINALIZED) {
                    $updates['status'] = Schedule::STATUS_FACULTY_ASSIGNED;
                }

                $slot->update($updates);
            }
        });

        $this->flashMessage = "✓ {$faculty->full_name} assigned to {$subject->subject_code}.";
        $this->flashType    = 'success';
        $this->closeFacultyModal();
    }

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

        $hasFinalizedRows = Schedule::whereIn('id', $this->modalScheduleIds)
            ->where('status', Schedule::STATUS_FINALIZED)
            ->exists();

        if ($hasFinalizedRows) {
            $this->assignError = 'Finalized schedules need a replacement faculty instead of removing the assignment.';
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

    // ── Finalization ────────────────────────────────────────────────────────

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

        $scheduledSubjectIds = $schedules->pluck('subject_id')->filter()->unique()->values();
        $unscheduledSubjects = Subject::activeTerm($this->semester, $this->schoolYear)
            ->where('section', $this->selectedSection)
            ->where('year_level', (int) $this->selectedYear)
            ->where(function ($q) {
                $q->where('department', $this->selectedDepartment)
                    ->orWhere('major', $this->selectedDepartment);
            })
            ->when($scheduledSubjectIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $scheduledSubjectIds))
            ->get(['id', 'subject_code']);

        foreach ($unscheduledSubjects as $subject) {
            $errors[] = "Not scheduled: {$subject->subject_code} has no room, time, or faculty assignment.";
        }

        $unassigned = $schedules->whereNull('faculty_id');
        foreach ($unassigned as $slot) {
            $errors[] = "Missing faculty: {$slot->subject?->subject_code} "
                . "on {$slot->day} has no assigned faculty.";
        }

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

    // ── Role label helper ────────────────────────────────────────────────────

    /**
     * Convert an internal role slug to a human-readable label.
     * Used in notification messages so we never show raw "associate_dean" etc.
     */
    protected function formatRoleLabel(string $role): string
    {
        return match ($role) {
            'dean'           => 'Dean',
            'oic'            => 'OIC',
            'associate_dean' => 'Associate Dean',
            'admin'          => 'Admin',
            'registrar'      => 'Registrar',
            default          => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    // ── Render ──────────────────────────────────────────────────────────────

    private function facultyAssignmentRecommendations(Subject $subject, Collection $schedules, ?int $excludeFacultyId = null): array
    {
        return Faculty::approved()
            ->select(['id', 'full_name', 'department', 'faculty_scope', 'max_units', 'availability', 'can_teach_minor'])
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Faculty $faculty) => (int) $faculty->id !== (int) $excludeFacultyId)
            ->filter(fn (Faculty $faculty) => $faculty->isEligibleForSubject($subject))
            ->filter(fn (Faculty $faculty) => $this->facultyCanTakeScheduleGroup($faculty, $subject, $schedules))
            ->map(function (Faculty $faculty) use ($subject) {
                $currentUnits = $this->getFacultyCurrentUnits((int) $faculty->id);
                $maxUnits = (int) ($faculty->max_units ?? 21);
                $score = 100 + max(0, 30 - $currentUnits);

                if ($faculty->department === $subject->department || $faculty->department === $subject->major) {
                    $score += 40;
                }

                return [
                    'id' => $faculty->id,
                    'name' => $faculty->full_name,
                    'department' => $faculty->displayDepartment(),
                    'load' => $currentUnits + (int) ($subject->units ?? 0),
                    'max_units' => $maxUnits,
                    'match_label' => $score >= 140 ? 'BEST MATCH' : ($score >= 115 ? 'GOOD MATCH' : 'FALLBACK'),
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->all();
    }

    private function facultyCanTakeScheduleGroup(Faculty $faculty, Subject $subject, Collection $schedules): bool
    {
        $conflictService = app(ScheduleConflictService::class);

        foreach ($schedules as $slot) {
            $clash = Schedule::activeTerm($this->semester, $this->schoolYear)
                ->where('faculty_id', $faculty->id)
                ->where('day', $slot->day)
                ->whereNotIn('id', $this->modalScheduleIds)
                ->where('start_time', '<', $slot->end_time)
                ->where('end_time', '>', $slot->start_time)
                ->exists();

            if ($clash) {
                return false;
            }

            $availability = $conflictService->checkFacultyAvailability(
                $faculty,
                $slot->day,
                (string) $slot->start_time,
                (string) $slot->end_time,
                $subject,
                $slot->room,
                $slot->id
            );

            if (($availability['status'] ?? true) === false) {
                return false;
            }
        }

        return ($this->getFacultyCurrentUnits((int) $faculty->id) + (int) ($subject->units ?? 0))
            <= (int) ($faculty->max_units ?? 21);
    }


    // ── Edit Workspace: Smart Per-Row Option Building ───────────────────────────

    /**
     * Build the complete set of filtered dropdown options for a single edit-mode
     * workspace row.
     *
     * Called once per row in render() only while $workspaceEditMode is true.
     *
     * Rules applied:
     *  • Faculty  — reuse Faculty::isEligibleForSubject() (already encodes GenEd,
     *               departmental, cross-department, minor rules).  Each faculty
     *               entry is annotated with occupied/available status for the
     *               currently selected day+time so the blade can show labels.
     *  • Rooms    — matched by Room::isCompatibleWithSubject() (Lab vs Lecture),
     *               then annotated as available/occupied for the selected slot.
     *  • Days     — only Setting::getActiveDays() values are offered.
     *  • Times    — Setting::getTimeSlots() (master-grid bricks, lunch excluded).
     */
    private function buildEditOptionsForRow(
        array      $row,
        ?Subject   $subject,
        \Illuminate\Support\Collection $allRooms,
        \Illuminate\Support\Collection $allFaculty,
        array      $activeDays,
        array      $timeSlots
    ): array {
        $pairingKey = $row['pairing_key'] ?? '';
        $scheduleIds = $row['schedule_ids'] ?? [];

        // Parse current edit values so we can annotate availability.
        $selectedDays  = $this->parseWorkspaceDays($row['day_string'] ?? '');
        $startTime     = $row['start_time'] ?? null;
        $endTime       = $row['end_time']   ?? null;
        $primaryDay    = $selectedDays[0] ?? null;

        // ── Faculty options ──────────────────────────────────────────────────
        $facultyOptions = [];
        foreach ($allFaculty as $faculty) {
            // Eligibility check reuses the existing Faculty model method.
            if ($subject && ! $faculty->isEligibleForSubject($subject)) {
                continue;
            }

            $occupied = false;
            if ($primaryDay && $startTime && $endTime) {
                $occupied = Schedule::activeTerm($this->semester, $this->schoolYear)
                    ->where('faculty_id', $faculty->id)
                    ->where('day', $primaryDay)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time',   '>', $startTime)
                    ->whereNotIn('pairing_key', array_filter([$pairingKey]))
                    ->exists();
            }

            $facultyOptions[] = [
                'id'       => $faculty->id,
                'label'    => $faculty->full_name,
                'scope'    => $faculty->scopeLabel(),
                'occupied' => $occupied,
                'status'   => $occupied ? 'OCCUPIED' : 'AVAILABLE',
            ];
        }

        // Sort: available first, then occupied; alpha within each group.
        usort($facultyOptions, fn ($a, $b) =>
            $a['occupied'] <=> $b['occupied'] ?: strcmp($a['label'], $b['label'])
        );

        // ── Room options ─────────────────────────────────────────────────────
        $roomOptions = [];
        foreach ($allRooms as $room) {
            // Type-compatibility: reuse the existing Room method if it exists,
            // otherwise fall back to a local check.
            $compatible = $subject ? $this->roomCompatibleWithSubject($room, $subject) : true;
            if (! $compatible) {
                continue;
            }

            $occupied = false;
            if ($primaryDay && $startTime && $endTime) {
                $occupied = Schedule::activeTerm($this->semester, $this->schoolYear)
                    ->where('room_id', $room->id)
                    ->where('day', $primaryDay)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time',   '>', $startTime)
                    ->whereNotIn('pairing_key', array_filter([$pairingKey]))
                    ->exists();
            }

            $roomOptions[] = [
                'id'       => $room->id,
                'label'    => $room->room_name,
                'type'     => $room->type ?? '',
                'occupied' => $occupied,
                'status'   => $occupied ? 'OCCUPIED' : 'AVAILABLE',
            ];
        }

        // Sort: available first, then occupied; alpha within each group.
        usort($roomOptions, fn ($a, $b) =>
            $a['occupied'] <=> $b['occupied'] ?: strcmp($a['label'], $b['label'])
        );

        return [
            'faculty'   => $facultyOptions,
            'rooms'     => $roomOptions,
            'days'      => $activeDays,       // Setting::getActiveDays() list
            'timeSlots' => $timeSlots,        // Setting::getTimeSlots() list
        ];
    }

    /**
     * Determine whether a room is compatible with a subject.
     *
     * Uses Room::isCompatibleWithSubject() when that method exists on the Room
     * model; otherwise applies a local heuristic based on `type` and
     * `requires_lab` / `subject_type` / `preferred_room_type`.
     *
     * This keeps us from duplicating the existing Room model logic while still
     * gracefully handling projects where it hasn't been defined yet.
     */
    private function roomCompatibleWithSubject(Room $room, Subject $subject): bool
    {
        // Prefer the Room model's own method if it exists (DRY principle).
        if (method_exists($room, 'isCompatibleWithSubject')) {
            return $room->isCompatibleWithSubject($subject);
        }

        // ── Local heuristic (fallback) ───────────────────────────────────────
        $roomType    = strtolower(trim((string) ($room->type ?? '')));
        $isLabRoom   = str_contains($roomType, 'lab');
        $isLectRoom  = ! $isLabRoom;

        // Subject signals it needs a lab?
        $needsLab = (bool) ($subject->requires_lab ?? false);

        // Extra heuristics from subject_type / preferred_room_type.
        $subjectType = strtolower(trim((string) ($subject->subject_type ?? '')));
        $prefRoom    = strtolower(trim((string) ($subject->preferred_room_type ?? '')));

        if (! $needsLab && str_contains($subjectType, 'lab')) {
            $needsLab = true;
        }

        if (! $needsLab && str_contains($prefRoom, 'lab')) {
            $needsLab = true;
        }

        // Specialization match (e.g. ICT Lab only for ICT subjects).
        $roomSpec    = strtolower(trim((string) ($room->specialization ?? '')));
        $subjectSpec = strtolower(trim((string) ($subject->specialization ?? '')));

        if ($roomSpec !== '' && $subjectSpec !== '' && ! str_contains($roomSpec, $subjectSpec)) {
            return false;
        }

        // Lab subject must go to a lab room; lecture subject must go to a
        // lecture room. If the room has no type data, allow it everywhere.
        if ($roomType === '') {
            return true;
        }

        return $needsLab ? $isLabRoom : $isLectRoom;
    }

    public function render()
    {
        $available = $this->getAvailableDepartments();

        if (! array_key_exists($this->selectedDepartment, $available)) {
            $this->selectedDepartment = array_key_first($available) ?? 'IT';
        }

        $filteredSchedules = $this->currentBlockSchedules();
        $dayOrder = Setting::getActiveDays();

        // UPDATED: Always detect conflicts, even in edit mode
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
                $editKey = $this->workspaceEditKey((string) $pairingKey);
                $days  = $group->pluck('day')
                    ->filter()
                    ->unique()
                    ->sortBy(fn ($day) => array_search($day, $dayOrder, true))
                    ->values()
                    ->all();

                // UPDATED: ALWAYS detect conflicts, including during edit mode
                $hasConflict    = false;
                $conflictReason = '';
                $conflictType   = '';

                // Check if we have real-time conflict data from edit mode
                if ($this->workspaceEditMode && isset($this->workspaceRealTimeConflicts[$editKey])) {
                    $conflictData = $this->workspaceRealTimeConflicts[$editKey];
                    $hasConflict = true;
                    $conflictType = implode(' + ', $conflictData['types'] ?? []);
                    $conflictReason = implode(' | ', $conflictData['messages'] ?? []);
                } elseif ($this->workspaceValidationActive && isset($this->workspaceConflictKeys[$editKey])) {
                    // Validation conflicts after "Done Editing"
                    $hasConflict = true;
                    $conflictReason = 'Workspace edit needs review';
                    $conflictType = 'VALIDATION ERROR';
                } elseif ($first->faculty_id) {
                    // Normal conflict detection outside edit mode
                    foreach ($group as $slot) {
                        $clash = Schedule::activeTerm($this->semester, $this->schoolYear)
                            ->where('faculty_id', $first->faculty_id)
                            ->where('day', $slot->day)
                            ->where('id', '!=', $slot->id)
                            ->where('start_time', '<', $slot->end_time)
                            ->where('end_time', '>', $slot->start_time)
                            ->exists();

                        if ($clash) {
                            $hasConflict = true;
                            $conflictType = 'FACULTY CONFLICT';
                            $conflictReason = 'Faculty has a time conflict on ' . $slot->day;
                            break;
                        }
                    }
                }

                return (object) [
                    'pairing_key'       => $pairingKey,
                    'edit_key'          => $editKey,
                    'ids'               => $group->pluck('id')->all(),
                    'subject'           => $first->subject,
                    'room'              => $first->room,
                    'faculty'           => $first->faculty,
                    'status'            => $first->status,
                    'start_time'        => $first->start_time,
                    'end_time'          => $first->end_time,
                    'days'              => $days,
                    'day_display'       => implode(' / ', $days),
                    'sort_day'          => $days[0] ?? $first->day,
                    'has_conflict'      => $hasConflict,
                    'conflict_reason'   => $conflictReason,
                    'conflict_type'     => $conflictType,
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

        $scheduledSubjectIds = $filteredSchedules->pluck('subject_id')->filter()->unique()->values();
        $unscheduledSubjects = Subject::activeTerm($this->semester, $this->schoolYear)
            ->where('section', $this->selectedSection)
            ->where('year_level', (int) $this->selectedYear)
            ->where(function ($query) {
                $query->where('department', $this->selectedDepartment)
                    ->orWhere('major', $this->selectedDepartment);
            })
            ->when($scheduledSubjectIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $scheduledSubjectIds))
            ->orderBy('subject_code')
            ->get();

        $unscheduledRows = $unscheduledSubjects
            ->map(fn (Subject $subject) => (object) [
                'pairing_key'       => 'not-scheduled-' . $subject->id,
                'edit_key'          => 'not-scheduled-' . $subject->id,
                'ids'               => [],
                'subject'           => $subject,
                'room'              => null,
                'faculty'           => null,
                'status'            => 'not_scheduled',
                'start_time'        => null,
                'end_time'          => null,
                'days'              => [],
                'day_display'       => 'NOT SCHEDULED',
                'sort_day'          => null,
                'has_conflict'      => false,
                'conflict_reason'   => '',
                'conflict_type'     => '',
            ]);

        $scheduleRows = $scheduleRows
            ->concat($unscheduledRows)
            ->sort(function ($a, $b) use ($dayOrder) {
                $aDay = $a->sort_day ? array_search($a->sort_day, $dayOrder, true) : PHP_INT_MAX;
                $bDay = $b->sort_day ? array_search($b->sort_day, $dayOrder, true) : PHP_INT_MAX;
                $aDay = $aDay === false ? PHP_INT_MAX : $aDay;
                $bDay = $bDay === false ? PHP_INT_MAX : $bDay;

                return $aDay <=> $bDay
                    ?: strcmp((string) ($a->start_time ?? '99:99:99'), (string) ($b->start_time ?? '99:99:99'))
                    ?: strcmp((string) ($a->subject?->subject_code ?? ''), (string) ($b->subject?->subject_code ?? ''));
            })
            ->values();

        $subjectIdsForRows = $scheduleRows->pluck('subject.id')->filter()->unique()->values();
        $revisionRequestsBySubject = $subjectIdsForRows->isNotEmpty()
            ? ScheduleRevisionRequest::forWorkspace($this->semester, $this->schoolYear)
                ->whereIn('subject_id', $subjectIdsForRows)
                ->latest()
                ->get()
                ->groupBy('subject_id')
            : collect();

        $scheduleRows = $scheduleRows->map(function ($row) use ($revisionRequestsBySubject) {
            $row->revision_request = $revisionRequestsBySubject
                ->get($row->subject?->id, collect())
                ->first();

            return $row;
        });

        $schedules      = $filteredSchedules->whereIn('day', $dayOrder)->groupBy('day');
        $departmentName = $this->getDepartmentName($this->selectedDepartment);

        $allFinalized    = $scheduleRows->isNotEmpty()
            && $scheduleRows->every(fn ($r) => $r->status === Schedule::STATUS_FINALIZED);

        $unassignedCount = $scheduleRows->filter(fn ($r) => is_null($r->faculty))->count();
        $conflictCount   = $scheduleRows->filter(fn ($r) => $r->has_conflict)->count();

        $modalFaculty = $this->showFacultyModal ? $this->getEligibleFacultyForModal() : collect();
        $modalSubject = $this->modalSubjectId ? Subject::find($this->modalSubjectId) : null;
        $revisionSubject = $this->revisionSubjectId ? Subject::find($this->revisionSubjectId) : null;
        $revisionFacultyOptions = $revisionSubject
            ? Faculty::approved()
                ->orderBy('full_name')
                ->get()
                ->filter(fn (Faculty $faculty) => $faculty->isEligibleForSubject($revisionSubject))
                ->values()
            : collect();
        // ── Per-row filtered options for Edit Workspace ────────────────────────
        // In edit mode we build smart options keyed by edit_key so each row
        // only shows compatible rooms, eligible faculty, valid days, and
        // master-grid time slots.  Outside edit mode these are empty arrays
        // (nothing in the blade renders them) so there is zero cost.
        $workspaceEditOptions = [];
        if ($this->workspaceEditMode) {
            $allRooms     = Room::query()->available()->orderBy('room_name')->get();
            $allFaculty   = Faculty::approved()->orderBy('full_name')->get();
            $activeDays   = Setting::getActiveDays();
            $timeSlots    = Setting::getTimeSlots();
            $currentTerm  = [$this->semester, $this->schoolYear];

            foreach ($this->workspaceEdits as $editKey => $row) {
                $subject = Subject::find($row['subject_id'] ?? null);
                $workspaceEditOptions[$editKey] = $this->buildEditOptionsForRow(
                    $row, $subject, $allRooms, $allFaculty, $activeDays, $timeSlots
                );
            }
        }

        // Fallback flat options still needed for the non-edit-mode faculty
        // assignment modal (unchanged behaviour).
        $roomOptions    = Room::query()->available()->orderBy('room_name')->get(['id', 'room_name']);
        $facultyOptions = Faculty::approved()->orderBy('full_name')->get(['id', 'full_name']);

        $allRegistrars          = $this->canManageRegistrarPermission() ? $this->getAllRegistrars() : collect();
        $registrarWithPermission = $this->getRegistrarWithPermission();

        $recentPermissionLogs = $this->canManageRegistrarPermission()
            ? PermissionLog::with(['performer:id,name', 'targetUser:id,name'])
                ->latest()
                ->take(10)
                ->get()
            : collect();
        $pendingRevisionRequests = $this->canReviewRevisionRequests()
            ? ScheduleRevisionRequest::forWorkspace($this->semester, $this->schoolYear)
                ->pending()
                ->with(['subject:id,subject_code,description', 'requester:id,name', 'currentFaculty:id,full_name', 'requestedFaculty:id,full_name'])
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
            'canEditWorkspace'        => $this->canEditWorkspace(),
            'canRequestRevision'      => $this->canRequestRevision(),
            'canReviewRevision'       => $this->canReviewRevisionRequests(),
            'canManagePermission'     => $this->canManageRegistrarPermission(),
            'allFinalized'            => $allFinalized,
            'unassignedCount'         => $unassignedCount,
            'conflictCount'           => $conflictCount,
            'totalRows'               => $scheduleRows->count(),
            'modalFaculty'            => $modalFaculty,
            'modalSubject'            => $modalSubject,
            'revisionSubject'         => $revisionSubject,
            'revisionFacultyOptions'  => $revisionFacultyOptions,
            'roomOptions'             => $roomOptions,
            'facultyOptions'          => $facultyOptions,
            'workspaceEditOptions'    => $workspaceEditOptions,
            'allRegistrars'           => $allRegistrars,
            'registrarWithPermission' => $registrarWithPermission,
            'recentPermissionLogs'    => $recentPermissionLogs,
            'pendingRevisionRequests' => $pendingRevisionRequests,
        ]);
    }
}