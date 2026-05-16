<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FacultyLoading extends Component
{
    public $search = '';

    public $subjectSearch = '';

    public $selectedFacultyId = null;

    public $departmentFilter = 'all';

    public $statusFilter = 'all';

    public $subjectDepartmentFilter = 'all';

    public $subjectMajorFilter = 'all';

    public $subjectYearLevelFilter = 'all';

    public $subjectSectionFilter = 'all';

    public $subjectTypeFilter = 'all';

    public bool $showUnassignedOnly = false;

    public $activeTab = 'subjects';

    public $scheduleModalOpen = false;

    public bool $conflictModalOpen = false;

    public ?int $pendingAssignmentScheduleId = null;

    public array $pendingAssignmentWarnings = [];

    public array $pendingAssignmentGroupIds = [];

    protected $listeners = [
        'refreshGrid' => '$refresh',
    ];

    protected const DEFAULT_MAJOR_FILTERS = ['IT', 'ACT', 'FB', 'LD', 'QD', 'ED', 'HM', 'TM'];

    // ============================================================
    // TOAST HELPER
    // ============================================================

    private function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    // ============================================================
    // FACULTY SELECTION & TAB CONTROL
    // ============================================================

    public function selectFaculty($id)
    {
        $this->selectedFacultyId = $id;
        $this->subjectSearch = '';
        $this->resetFilters();
        $this->resetPendingAssignment();
    }

    private function resetFilters()
    {
        $this->subjectDepartmentFilter = 'all';
        $this->subjectMajorFilter     = 'all';
        $this->subjectYearLevelFilter = 'all';
        $this->subjectSectionFilter   = 'all';
        $this->subjectTypeFilter      = 'all';
        $this->showUnassignedOnly     = false;
    }

    #[Computed]
    public function selectedFaculty()
    {
        return $this->selectedFacultyId
            ? Faculty::with(['schedules.subject', 'schedules.room'])->find($this->selectedFacultyId)
            : null;
    }

    public function toggleTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openAssignmentPanel(?int $facultyId = null): void
    {
        if ($facultyId !== null && (int) $this->selectedFacultyId !== $facultyId) {
            $this->selectFaculty($facultyId);
        }

        if (! $this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');
            return;
        }

        $this->scheduleModalOpen = true;
        $this->activeTab = 'subjects';
    }

    public function closeAssignmentPanel(): void
    {
        $this->scheduleModalOpen = false;
        $this->resetPendingAssignment();
    }

    public function showFacultyLoad(int $facultyId): void
    {
        if ((int) $this->selectedFacultyId !== $facultyId) {
            $this->selectFaculty($facultyId);
        }
        $this->activeTab = 'summary';
    }

    public function showFacultySchedule(int $facultyId): void
    {
        if ((int) $this->selectedFacultyId !== $facultyId) {
            $this->selectFaculty($facultyId);
        }
        $this->activeTab = 'schedule';
    }

    public function showFacultyConflicts(int $facultyId): void
    {
        if ((int) $this->selectedFacultyId !== $facultyId) {
            $this->selectFaculty($facultyId);
        }
        $this->activeTab = 'conflicts';
    }

    public function preparePrintLoad(int $facultyId): void
    {
        if ((int) $this->selectedFacultyId !== $facultyId) {
            $this->selectFaculty($facultyId);
        }
        $this->activeTab = 'summary';
    }

    // ============================================================
    // SCHEDULE DATA RETRIEVAL
    // ============================================================

    /**
     * Raw flat collection of all assigned Schedule models for the selected faculty.
     * Used for schedule overview, conflict checking, and summary calculations.
     */
    private function assignedSchedules(): Collection
    {
        if (! $this->selectedFacultyId) {
            return collect();
        }

        return Schedule::query()
            ->where('faculty_id', $this->selectedFacultyId)
            ->with(['subject', 'room'])
            ->orderBy('start_time')
            ->get()
            ->pipe(fn (Collection $schedules) => $this->sortSchedules($schedules));
    }

    /**
     * Unique subjects assigned to the selected faculty.
     * Used for unit/load calculations — not for table display.
     */
    private function assignedSubjects(): Collection
    {
        return $this->assignedSchedules()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Groups multi-day schedule rows into a single display row per subject-section offering.
     *
     * Grouping key: subject_id + section
     * Each resulting array item contains:
     *   - subject_code, edp_code, description  – from the subject
     *   - group                                 – dept / major / year / section label
     *   - room                                  – room name
     *   - schedule                              – all meeting days joined with <br>
     *   - units, type                           – from the subject
     *   - schedule_ids                          – all raw schedule IDs in the group
     *   - first_schedule_id                     – used as the wire:key and remove target
     *
     * This prevents duplicate unit counting and duplicate table rows for the same
     * subject offering that meets on multiple days.
     */
    private function groupedAssignedSubjects(): Collection
    {
        return $this->assignedSchedules()
            ->groupBy(fn (Schedule $schedule) =>
                $schedule->subject_id . '::' . ($schedule->section ?? 'none')
            )
            ->map(function (Collection $group) {
                /** @var Schedule $first */
                $first   = $group->first();
                $subject = $first->subject;
                $room    = $first->room;

                // Build one schedule line per distinct day+time in this group
                $scheduleLines = $group
                    ->map(fn (Schedule $s) =>
                        trim(
                            ($s->day ?? 'N/A')
                            . ' / '
                            . Carbon::parse($s->start_time)->format('h:i A')
                            . ' - '
                            . Carbon::parse($s->end_time)->format('h:i A')
                        )
                    )
                    ->unique()
                    ->values()
                    ->implode('<br>');

                $department = $first->department ?? $subject?->department ?? 'N/A';
                $major      = $first->major      ?? $subject?->major      ?? 'N/A';
                $year       = $first->year_level ?? $subject?->year_level ?? '?';
                $section    = $first->section    ?? $subject?->section    ?? 'N/A';

                return [
                    'subject_code'       => $subject?->subject_code ?? 'N/A',
                    'edp_code'           => $subject?->edp_code     ?? 'No EDP',
                    'description'        => $subject?->description  ?? 'Untitled subject',
                    'group'              => "{$department} / {$major} / Y{$year} / {$section}",
                    'room'               => $room?->room_name ?? 'No room',
                    'schedule'           => $scheduleLines,
                    'units'              => $subject?->units ?? 0,
                    'type'              => $subject?->type  ?? 'N/A',
                    'schedule_ids'       => $group->pluck('id')->all(),
                    'first_schedule_id'  => $first->id,
                ];
            })
            ->values();
    }

    // ============================================================
    // FACULTY SUMMARY
    // ============================================================

    public function getFacultySummary()
    {
        if (! $this->selectedFaculty) {
            return null;
        }

        $subjects           = $this->assignedSubjects();
        $totalUnits         = $subjects->sum('units') ?? 0;
        $maxUnits           = $this->selectedFaculty->max_units ?? 21;
        $utilizationPercent = $maxUnits > 0 ? round(($totalUnits / $maxUnits) * 100) : 0;

        $majorSubjects = $subjects->where('type', 'Major');
        $minorSubjects = $subjects->where('type', 'Minor');
        $majorCount    = $majorSubjects->count();
        $minorCount    = $minorSubjects->count();
        $majorUnits    = $majorSubjects->sum('units') ?? 0;
        $minorUnits    = $minorSubjects->sum('units') ?? 0;

        return [
            'totalUnits'         => $totalUnits,
            'maxUnits'           => $maxUnits,
            'remainingUnits'     => max(0, $maxUnits - $totalUnits),
            'overloadUnits'      => max(0, $totalUnits - $maxUnits),
            'utilizationPercent' => $utilizationPercent,
            'majorCount'         => $majorCount,
            'minorCount'         => $minorCount,
            'majorUnits'         => $majorUnits,
            'minorUnits'         => $minorUnits,
            'averageMajorUnits'  => $majorCount > 0 ? round($majorUnits / $majorCount, 2) : 0,
            'averageMinorUnits'  => $minorCount > 0 ? round($minorUnits / $minorCount, 2) : 0,
            'scheduleCount'      => $this->assignedSchedules()->count(),
        ];
    }

    // ============================================================
    // ASSIGNMENT ACTIONS
    // ============================================================

    public function assignSubject($scheduleId)
    {
        if (! $this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');
            return;
        }

        $schedule = Schedule::with(['subject', 'room'])->find($scheduleId);
        $faculty  = Faculty::find($this->selectedFacultyId);

        if (! $schedule || ! $schedule->subject) {
            $this->toast('error', 'Scheduled subject not found.');
            return;
        }

        if (! $faculty) {
            $this->toast('error', 'Faculty not found.');
            return;
        }

        if ($schedule->status === Schedule::STATUS_FINALIZED) {
            $this->toast('error', 'Finalized schedules cannot be changed in Faculty Loading.');
            return;
        }

        if ((int) $schedule->faculty_id === (int) $faculty->id) {
            $this->toast('warning', "{$schedule->subject->subject_code} is already assigned to {$faculty->full_name}.");
            return;
        }

        if ($schedule->faculty_id !== null && (int) $schedule->faculty_id !== (int) $faculty->id) {
            $this->toast('warning', "{$schedule->subject->subject_code} is already assigned to another faculty member.");
            return;
        }

        if (! $this->canAssignSubject(Auth::user(), $faculty, $schedule->subject)) {
            $this->toast('error', 'Unauthorized assignment.');
            return;
        }

        $warnings = $this->assignmentWarnings($faculty, $schedule);

        if ($warnings !== []) {
            $this->pendingAssignmentScheduleId = $schedule->id;
            $this->pendingAssignmentWarnings   = $warnings;
            $this->conflictModalOpen           = true;
            $this->toast(
                $this->canOverrideAssignmentWarnings(Auth::user()) ? 'warning' : 'error',
                $this->canOverrideAssignmentWarnings(Auth::user())
                    ? 'Assignment needs review before override.'
                    : 'Assignment blocked by load or conflict validation.'
            );
            return;
        }

        $this->persistAssignment($schedule, $faculty);
    }

    public function confirmAssignmentOverride(): void
    {
        $user = Auth::user();

        if (! $this->canOverrideAssignmentWarnings($user)) {
            $this->toast('error', 'You are not authorized to override assignment warnings.');
            return;
        }

        if (! $this->selectedFacultyId || ! $this->pendingAssignmentScheduleId) {
            $this->resetPendingAssignment();
            $this->toast('error', 'No pending assignment found.');
            return;
        }

        $faculty = Faculty::find($this->selectedFacultyId);

        if (! $faculty) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Pending assignment data is no longer available.');
            return;
        }

        // If this was a group assignment (multiple days), handle all of them
        $groupIds = $this->pendingAssignmentGroupIds;

        if (! empty($groupIds)) {
            $schedules = Schedule::with(['subject', 'room'])->findMany($groupIds);

            if ($schedules->isEmpty()) {
                $this->resetPendingAssignment();
                $this->toast('error', 'Pending assignment data is no longer available.');
                return;
            }

            $assignedElsewhere = $schedules->first(
                fn (Schedule $s) => $s->faculty_id !== null && (int) $s->faculty_id !== (int) $faculty->id
            );

            if ($assignedElsewhere) {
                $this->resetPendingAssignment();
                $this->toast('warning', "{$schedules->first()->subject?->subject_code} is already assigned to another faculty member.");
                return;
            }

            if (! $this->canAssignSubject($user, $faculty, $schedules->first()->subject)) {
                $this->resetPendingAssignment();
                $this->toast('error', 'Unauthorized assignment.');
                return;
            }

            $this->persistGroupAssignment($schedules, $faculty, true);
            return;
        }

        // Fallback: single schedule assignment (original behaviour)
        $schedule = Schedule::with(['subject', 'room'])->find($this->pendingAssignmentScheduleId);

        if (! $schedule || ! $schedule->subject) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Pending assignment data is no longer available.');
            return;
        }

        if ($schedule->faculty_id !== null && (int) $schedule->faculty_id !== (int) $faculty->id) {
            $this->resetPendingAssignment();
            $this->toast('warning', "{$schedule->subject->subject_code} is already assigned to another faculty member.");
            return;
        }

        if (! $this->canAssignSubject($user, $faculty, $schedule->subject)) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Unauthorized assignment.');
            return;
        }

        $this->persistAssignment($schedule, $faculty, true);
    }

    public function cancelAssignmentOverride(): void
    {
        $this->resetPendingAssignment();
    }

    private function persistAssignment(Schedule $schedule, Faculty $faculty, bool $overridden = false): void
    {
        try {
            $subjectCode = $schedule->subject->subject_code;
            $scheduleId  = $schedule->id;

            DB::transaction(function () use ($scheduleId, $faculty) {
                $fresh = Schedule::lockForUpdate()->findOrFail($scheduleId);

                if ($fresh->faculty_id !== null && (int) $fresh->faculty_id !== (int) $faculty->id) {
                    throw new \RuntimeException(
                        'This schedule was assigned to another faculty member just now. Please refresh and try again.'
                    );
                }

                $fresh->update([
                    'faculty_id' => $faculty->id,
                    'status'     => Schedule::STATUS_PARTIAL,
                ]);
            });

            $this->subjectSearch = '';
            $this->resetPendingAssignment();
            unset($this->selectedFaculty);

            $message = "{$subjectCode} assigned to {$faculty->full_name} successfully.";
            $this->toast('success', $overridden ? "{$message} Override recorded." : $message);
        } catch (\RuntimeException $exception) {
            $this->toast('warning', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not assigned.');
        }
    }

    private function assignmentWarnings(Faculty $faculty, Schedule $schedule): array
    {
        $warnings              = [];
        $startTime             = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime               = Carbon::parse($schedule->end_time)->format('H:i:s');
        $currentSubjects       = $this->assignedSubjects();
        $currentUnits          = $currentSubjects->sum('units');
        $subjectAlreadyAssigned = $currentSubjects->contains('id', $schedule->subject_id);
        $newTotal              = $currentUnits + ($subjectAlreadyAssigned ? 0 : (int) $schedule->subject->units);
        $maxUnits              = (int) ($faculty->max_units ?? 21);

        if ($newTotal > $maxUnits) {
            $warnings[] = [
                'type'    => 'OVERLOAD',
                'title'   => 'Faculty Load Over Capacity',
                'message' => "{$schedule->subject->subject_code} would bring {$faculty->full_name} to {$newTotal}/{$maxUnits} units.",
            ];
        }

        $conflictService = app(ScheduleConflictService::class);

        $facultyConflict = $conflictService->checkFacultyConflict(
            $faculty->id,
            $schedule->day,
            $startTime,
            $endTime,
            $schedule->id
        );

        if (($facultyConflict['status'] ?? true) === false) {
            $warnings[] = [
                'type'    => $facultyConflict['conflict_type'] ?? 'FACULTY_CONFLICT',
                'title'   => $facultyConflict['title']         ?? 'Faculty Schedule Conflict',
                'message' => $facultyConflict['message']       ?? 'Faculty schedule conflict detected.',
            ];
        }

        if ($schedule->room_id) {
            $roomConflict = $conflictService->checkRoomConflict(
                $schedule->room_id,
                $schedule->day,
                $startTime,
                $endTime,
                $schedule->id
            );

            if (($roomConflict['status'] ?? true) === false) {
                $warnings[] = [
                    'type'    => $roomConflict['conflict_type'] ?? 'ROOM_CONFLICT',
                    'title'   => $roomConflict['title']         ?? 'Room Schedule Conflict',
                    'message' => $roomConflict['message']       ?? 'Room schedule conflict detected.',
                ];
            }
        }

        $availability = $conflictService->checkFacultyAvailability(
            $faculty,
            $schedule->day,
            $startTime,
            $endTime
        );

        if (($availability['status'] ?? true) === false) {
            $warnings[] = [
                'type'    => $availability['conflict_type'] ?? 'FACULTY_AVAILABILITY',
                'title'   => $availability['title']         ?? 'Faculty Availability Conflict',
                'message' => $availability['message']       ?? 'Faculty is not available during this time.',
            ];
        }

        return $warnings;
    }

    private function resetPendingAssignment(): void
    {
        $this->conflictModalOpen           = false;
        $this->pendingAssignmentScheduleId = null;
        $this->pendingAssignmentWarnings   = [];
        $this->pendingAssignmentGroupIds   = [];
    }

    // ============================================================
    // REMOVE SUBJECT
    // ============================================================

    public function removeSubject($scheduleId)
    {
        $schedule = Schedule::with('subject')->find($scheduleId);

        if (! $schedule || ! $schedule->subject) {
            $this->toast('error', 'Scheduled subject not found.');
            return;
        }

        if ($schedule->status === 'finalized') {
            $this->toast('error', 'Finalized schedules cannot be changed in Faculty Loading.');
            return;
        }

        $oldCode = $schedule->subject->subject_code;

        try {
            $schedule->update([
                'faculty_id' => null,
                'status'     => Schedule::STATUS_PARTIAL,
            ]);

            unset($this->selectedFaculty);
            $this->toast('success', "Subject {$oldCode} faculty assignment removed.");
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not removed.');
        }
    }

    /**
     * Removes the faculty assignment from ALL schedules in a group at once.
     * This is the counterpart to assignSubjectGroup().
     */
    public function removeSubjectGroup(array $scheduleIds): void
    {
        if (empty($scheduleIds)) {
            $this->toast('error', 'No schedules provided.');
            return;
        }

        $schedules = Schedule::with('subject')->findMany($scheduleIds);

        if ($schedules->isEmpty()) {
            $this->toast('error', 'Scheduled subjects not found.');
            return;
        }

        if ($schedules->contains(fn (Schedule $s) => $s->status === 'finalized')) {
            $this->toast('error', 'Finalized schedules cannot be changed in Faculty Loading.');
            return;
        }

        $subjectCode = $schedules->first()?->subject?->subject_code ?? 'Subject';

        try {
            foreach ($schedules as $schedule) {
                $schedule->update([
                    'faculty_id' => null,
                    'status'     => Schedule::STATUS_PARTIAL,
                ]);
            }

            unset($this->selectedFaculty);
            $this->toast('success', "Subject {$subjectCode} ({$schedules->count()} schedule(s)) faculty assignment removed.");
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not removed.');
        }
    }

    // ============================================================
    // SUBMIT FACULTY LOADING
    // ============================================================

    public function submitFacultyLoading(): void
    {
        $user  = Auth::user();
        $query = Schedule::query()
            ->assignable()
            ->whereNotNull('faculty_id');

        if (in_array($user->role, ['dean', 'oic']) && $user->department) {
            $query->where(function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->whereHas('subject', function (Builder $majorQuery) use ($user) {
                    $majorQuery->where('type', 'Major')
                        ->where('department', $user->department);
                })->orWhere(function (Builder $minorQuery) use ($user) {
                    $minorQuery
                        ->whereHas('subject', fn (Builder $sq) => $sq->where('type', 'Minor'))
                        ->whereHas('faculty',  fn (Builder $fq) => $fq->where('department', $user->department));
                });
            });
        } elseif ($user->role === 'associate_dean') {
            $query->whereHas('subject', fn (Builder $sq) => $sq->where('type', 'Minor'));
        }

        $updated = $query->update(['status' => 'faculty_assigned']);

        $this->toast(
            $updated > 0 ? 'success' : 'warning',
            $updated > 0
                ? "Submitted {$updated} assigned schedule(s) to Registrar/Admin for approval."
                : 'No assigned schedules are ready to submit.'
        );
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    private function facultyConflict(Faculty $faculty, Schedule $schedule): ?string
    {
        $conflict = Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->whereKeyNot($schedule->id)
            ->where('day', $schedule->day)
            ->where(function (Builder $query) use ($schedule) {
                $query->where('start_time', '<', Carbon::parse($schedule->end_time)->format('H:i:s'))
                    ->where('end_time', '>', Carbon::parse($schedule->start_time)->format('H:i:s'));
            })
            ->with(['subject:id,subject_code', 'room:id,room_name'])
            ->first();

        if (! $conflict) {
            return null;
        }

        $subjectCode = $conflict->subject?->subject_code ?? 'another subject';
        $roomName    = $conflict->room?->room_name       ?? 'Unknown Room';

        return "Professor {$faculty->full_name} is already teaching {$subjectCode} in Room {$roomName} during this time.";
    }

    private function canAssignSubject($user, $faculty, $subject)
    {
        $specialization = $faculty->teaching_specialization ?? 'Both';

        if ($specialization !== 'Both' && $specialization !== $subject->type) {
            return false;
        }

        if (in_array($user->role, ['admin', 'registrar'])) {
            return true;
        }

        if ($user->role === 'associate_dean') {
            return $subject->type === 'Minor';
        }

        if (in_array($user->role, ['dean', 'oic'])) {
            if ($faculty->department !== $user->department) {
                return false;
            }

            return $subject->type === 'Minor'
                || ($subject->type === 'Major' && $user->department === $subject->department);
        }

        if ($subject->type === 'Minor') {
            return true;
        }

        return $subject->type === 'Major' && $user->department === $subject->department;
    }

    private function canOverrideAssignmentWarnings($user): bool
    {
        return $user && in_array($user->role, ['admin', 'registrar'], true);
    }

    // ============================================================
    // QUERY BUILDERS
    // ============================================================

    private function getFacultyQuery()
    {
        $user  = Auth::user();
        $query = Faculty::query()
            ->approved()
            ->withCount('schedules');

        if (in_array($user->role, ['dean', 'oic'])) {
            $query->where('department', $user->department);
        } elseif ($user->role === 'associate_dean') {
            $query->whereIn('teaching_specialization', ['Minor', 'Both']);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                    ->orWhere('employee_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('employment_type', $this->statusFilter);
        }

        if ($this->departmentFilter !== 'all') {
            $query->where('department', $this->departmentFilter);
        }

        return $query;
    }

    private function getAvailableSubjects()
    {
        $query = Schedule::query()
            ->with(['subject', 'room', 'faculty'])
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->whereHas('subject');

        if ($this->showUnassignedOnly) {
            $query->whereNull('faculty_id');
        }

        $user = Auth::user();

        $query->whereHas('subject', function (Builder $subjectQuery) use ($user) {
            if ($user->role === 'associate_dean') {
                $subjectQuery->where('type', 'Minor');
                return;
            }

            if (in_array($user->role, ['dean', 'oic'], true)) {
                $subjectQuery->where(function (Builder $visibility) use ($user) {
                    $visibility->where('type', 'Minor')
                        ->orWhere(function (Builder $majorQuery) use ($user) {
                            $majorQuery->where('type', 'Major')
                                ->where('department', $user->department);
                        });
                });
            }
        });

        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);

            if ($faculty) {
                $query->whereHas('subject', function (Builder $subjectQuery) use ($faculty) {
                    if ($faculty->teaching_specialization === 'Minor') {
                        $subjectQuery->where('type', 'Minor');
                    } elseif ($faculty->teaching_specialization === 'Major') {
                        $subjectQuery->where('type', 'Major')
                            ->where('department', $faculty->department);
                    } else {
                        $subjectQuery->where(function ($sub) use ($faculty) {
                            $sub->where('type', 'Minor')
                                ->orWhere(function ($inner) use ($faculty) {
                                    $inner->where('type', 'Major')
                                        ->where('department', $faculty->department);
                                });
                        });
                    }
                });
            }
        }

        if ($this->subjectDepartmentFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('department', $this->subjectDepartmentFilter)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->where('department', $this->subjectDepartmentFilter));
            });
        }

        if ($this->subjectMajorFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('major', $this->subjectMajorFilter)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->where('major', $this->subjectMajorFilter));
            });
        }

        if ($this->subjectYearLevelFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('year_level', (int) $this->subjectYearLevelFilter)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->where('year_level', (int) $this->subjectYearLevelFilter));
            });
        }

        if ($this->subjectSectionFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('section', $this->subjectSectionFilter)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->where('section', $this->subjectSectionFilter));
            });
        }

        if ($this->subjectTypeFilter !== 'all') {
            $query->whereHas('subject', fn (Builder $q) => $q->where('type', $this->subjectTypeFilter));
        }

        if (strlen($this->subjectSearch) > 1) {
            $term = $this->subjectSearch;
            $query->where(function (Builder $searchQuery) use ($term) {
                $searchQuery->where('section', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%")
                    ->orWhere('major', 'like', "%{$term}%")
                    ->orWhereHas('subject', function (Builder $q) use ($term) {
                        $q->where('subject_code', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('edp_code', 'like', "%{$term}%")
                            ->orWhere('section', 'like', "%{$term}%")
                            ->orWhere('department', 'like', "%{$term}%")
                            ->orWhere('major', 'like', "%{$term}%");
                    });
            });
        }

        return $query
            ->orderBy('start_time')
            ->get()
            ->pipe(fn (Collection $schedules) => $this->sortSchedules($schedules));
    }

    /**
     * Groups the flat availableSubjects collection into one card per subject-section offering.
     *
     * Grouping key: subject_id + section + start_time + end_time
     * (Same subject, same section, same time slot but different days → one group.)
     *
     * Each resulting array contains:
     *   - schedule_ids        – all schedule IDs in this group (used for bulk assign)
     *   - first_schedule_id   – wire:key and fallback single-assign reference
     *   - subject_code, edp_code, description, units, type
     *   - department, major, year, section, room
     *   - days                – comma-joined day list  e.g. "Monday / Tuesday"
     *   - time                – formatted time range
     *   - faculty_id          – null if unassigned, int if assigned
     *   - faculty_name        – assigned faculty name or null
     *   - status              – schedule status of the first record
     */
    private function getGroupedAvailableSubjects(): Collection
    {
        return $this->getAvailableSubjects()
            ->groupBy(function (Schedule $schedule) {
                // Group by: subject + section + time window
                // Two schedules with the same subject/section/time on different days
                // belong to the same "offering" and must share one faculty assignment.
                $start = Carbon::parse($schedule->start_time)->format('H:i');
                $end   = Carbon::parse($schedule->end_time)->format('H:i');

                return implode('::', [
                    $schedule->subject_id,
                    $schedule->section   ?? 'none',
                    $schedule->department ?? '',
                    $schedule->major      ?? '',
                    $schedule->year_level ?? '',
                    $start,
                    $end,
                ]);
            })
            ->map(function (Collection $group) {
                /** @var Schedule $first */
                $first   = $group->first();
                $subject = $first->subject;
                $room    = $first->room;

                $days = $group
                    ->map(fn (Schedule $s) => $s->day ?? 'N/A')
                    ->unique()
                    ->values()
                    ->implode(' / ');

                $department = $first->department ?? $subject?->department ?? 'N/A';
                $major      = $first->major      ?? $subject?->major      ?? 'N/A';
                $year       = $first->year_level ?? $subject?->year_level ?? 'N/A';
                $section    = $first->section    ?? $subject?->section    ?? 'N/A';

                // Determine unified assignment state for the group:
                // If ANY schedule in the group has a faculty_id, treat the whole group as assigned.
                $assignedFacultyId = $group->first(fn (Schedule $s) => $s->faculty_id !== null)?->faculty_id;
                $assignedFaculty   = $assignedFacultyId
                    ? ($group->first(fn (Schedule $s) => $s->faculty_id !== null)?->faculty?->full_name ?? null)
                    : null;

                $isFinalized = $group->contains(fn (Schedule $s) => $s->status === Schedule::STATUS_FINALIZED);

                return [
                    'schedule_ids'       => $group->pluck('id')->all(),
                    'first_schedule_id'  => $first->id,
                    'subject_code'       => $subject?->subject_code ?? 'N/A',
                    'edp_code'           => $subject?->edp_code     ?? 'No EDP',
                    'description'        => $subject?->description  ?? 'Untitled subject',
                    'units'              => $subject?->units         ?? 0,
                    'type'               => $subject?->type         ?? 'N/A',
                    'department'         => $department,
                    'major'              => $major,
                    'year'               => $year,
                    'section'            => $section,
                    'room'               => $room?->room_name ?? 'No room',
                    'days'               => $days,
                    'time'               => Carbon::parse($first->start_time)->format('h:i A')
                                           . ' - '
                                           . Carbon::parse($first->end_time)->format('h:i A'),
                    'faculty_id'         => $assignedFacultyId,
                    'faculty_name'       => $assignedFaculty,
                    'is_finalized'       => $isFinalized,
                ];
            })
            ->values();
    }

    /**
     * Assigns ALL schedule rows in a group to the selected faculty in one action.
     * This prevents a split where Mon is assigned to Faculty A and Tue to Faculty B
     * for the same subject offering.
     */
    public function assignSubjectGroup(array $scheduleIds): void
    {
        if (! $this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');
            return;
        }

        if (empty($scheduleIds)) {
            $this->toast('error', 'No schedules provided.');
            return;
        }

        $faculty = Faculty::find($this->selectedFacultyId);

        if (! $faculty) {
            $this->toast('error', 'Faculty not found.');
            return;
        }

        $schedules = Schedule::with(['subject', 'room'])->findMany($scheduleIds);

        if ($schedules->isEmpty()) {
            $this->toast('error', 'Scheduled subjects not found.');
            return;
        }

        // Use the first schedule for subject/validation checks (all share the same subject)
        $first = $schedules->first();

        if (! $first->subject) {
            $this->toast('error', 'Subject data missing.');
            return;
        }

        // Check none are finalized
        if ($schedules->contains(fn (Schedule $s) => $s->status === Schedule::STATUS_FINALIZED)) {
            $this->toast('error', 'Finalized schedules cannot be changed in Faculty Loading.');
            return;
        }

        // Check none are already assigned to a DIFFERENT faculty
        $assignedElsewhere = $schedules->first(
            fn (Schedule $s) => $s->faculty_id !== null && (int) $s->faculty_id !== (int) $faculty->id
        );

        if ($assignedElsewhere) {
            $this->toast('warning', "{$first->subject->subject_code} is already assigned to another faculty member.");
            return;
        }

        // Check already all assigned to this faculty
        if ($schedules->every(fn (Schedule $s) => (int) $s->faculty_id === (int) $faculty->id)) {
            $this->toast('warning', "{$first->subject->subject_code} is already assigned to {$faculty->full_name}.");
            return;
        }

        if (! $this->canAssignSubject(Auth::user(), $faculty, $first->subject)) {
            $this->toast('error', 'Unauthorized assignment.');
            return;
        }

        // Run conflict/load checks across EVERY day in the group so that a
        // time conflict on day 2 (e.g. Tuesday) is caught even when day 1
        // (Monday) is free.  Unit-overload only needs to be counted once, so
        // we skip it on subsequent schedules once it has already been flagged.
        $warnings           = [];
        $overloadFlagged    = false;

        foreach ($schedules as $scheduleItem) {
            $dayWarnings = $this->assignmentWarnings($faculty, $scheduleItem);

            foreach ($dayWarnings as $warning) {
                // De-duplicate overload warning — same unit total for every day
                if (($warning['type'] ?? '') === 'OVERLOAD') {
                    if ($overloadFlagged) {
                        continue;
                    }
                    $overloadFlagged = true;
                }

                // De-duplicate any other warning by its message text
                $isDuplicate = collect($warnings)->contains(
                    fn (array $existing) => ($existing['message'] ?? '') === ($warning['message'] ?? '')
                );

                if (! $isDuplicate) {
                    $warnings[] = $warning;
                }
            }
        }

        if ($warnings !== []) {
            // Store only the first schedule ID for the conflict modal flow;
            // on override we will assign all IDs in the group.
            $this->pendingAssignmentScheduleId = $first->id;
            $this->pendingAssignmentGroupIds   = $scheduleIds;
            $this->pendingAssignmentWarnings   = $warnings;
            $this->conflictModalOpen           = true;
            $this->toast(
                $this->canOverrideAssignmentWarnings(Auth::user()) ? 'warning' : 'error',
                $this->canOverrideAssignmentWarnings(Auth::user())
                    ? 'Assignment needs review before override.'
                    : 'Assignment blocked by load or conflict validation.'
            );
            return;
        }

        $this->persistGroupAssignment($schedules, $faculty);
    }

    private function persistGroupAssignment(Collection $schedules, Faculty $faculty, bool $overridden = false): void
    {
        try {
            $subjectCode = $schedules->first()?->subject?->subject_code ?? 'Subject';
            $scheduleIds = $schedules->pluck('id')->all();

            DB::transaction(function () use ($scheduleIds, $faculty) {
                // Re-read with a pessimistic lock so two simultaneous Assign
                // clicks cannot both pass the already-assigned-elsewhere check
                // and write conflicting faculty_ids.
                $fresh = Schedule::lockForUpdate()->findMany($scheduleIds);

                $conflict = $fresh->first(
                    fn (Schedule $s) => $s->faculty_id !== null
                        && (int) $s->faculty_id !== (int) $faculty->id
                );

                if ($conflict) {
                    throw new \RuntimeException(
                        'This schedule was assigned to another faculty member just now. Please refresh and try again.'
                    );
                }

                foreach ($fresh as $schedule) {
                    $schedule->update([
                        'faculty_id' => $faculty->id,
                        'status'     => Schedule::STATUS_PARTIAL,
                    ]);
                }
            });

            $this->subjectSearch = '';
            $this->resetPendingAssignment();
            unset($this->selectedFaculty);

            $message = "{$subjectCode} ({$schedules->count()} schedule(s)) assigned to {$faculty->full_name} successfully.";
            $this->toast('success', $overridden ? "{$message} Override recorded." : $message);
        } catch (\RuntimeException $exception) {
            $this->toast('warning', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not assigned.');
        }
    }

    // ============================================================
    // CONFLICT & SCHEDULE GROUPING
    // ============================================================

    private function getFacultyConflicts(Collection $assignedSchedules): Collection
    {
        $conflicts       = collect();
        $schedules       = $assignedSchedules->values();
        $conflictService = app(ScheduleConflictService::class);

        foreach ($schedules as $schedule) {
            if (! Setting::dayIsActive((string) $schedule->day)) {
                $conflicts->push([
                    'title'   => 'Inactive Schedule Day',
                    'message' => "{$schedule->subject?->subject_code} is scheduled on {$schedule->day}, which is disabled in global settings.",
                ]);
            }

            if ($this->selectedFaculty) {
                $availability = $conflictService->checkFacultyAvailability(
                    $this->selectedFaculty,
                    (string) $schedule->day,
                    Carbon::parse($schedule->start_time)->format('H:i:s'),
                    Carbon::parse($schedule->end_time)->format('H:i:s')
                );

                if (($availability['status'] ?? true) === false) {
                    $conflicts->push([
                        'title'   => $availability['title']   ?? 'Faculty Availability Conflict',
                        'message' => $availability['message'] ?? 'Faculty availability conflict detected.',
                    ]);
                }
            }
        }

        for ($i = 0; $i < $schedules->count(); $i++) {
            for ($j = $i + 1; $j < $schedules->count(); $j++) {
                $first  = $schedules[$i];
                $second = $schedules[$j];

                if ($first->day !== $second->day) {
                    continue;
                }

                $overlaps = Carbon::parse($first->start_time)->lt(Carbon::parse($second->end_time))
                    && Carbon::parse($first->end_time)->gt(Carbon::parse($second->start_time));

                if ($overlaps) {
                    $conflicts->push([
                        'title'   => 'Overlapping Faculty Schedule',
                        'message' => "{$first->subject?->subject_code} overlaps with {$second->subject?->subject_code} on {$first->day}.",
                    ]);
                }
            }
        }

        return $conflicts->unique('message')->values();
    }

    private function getScheduleGroups(Collection $assignedSchedules): Collection
    {
        $activeDays = Setting::getActiveDays();
        $otherDays  = $assignedSchedules
            ->pluck('day')
            ->filter()
            ->unique()
            ->diff($activeDays)
            ->values()
            ->all();

        return collect($activeDays)
            ->merge($otherDays)
            ->mapWithKeys(fn (string $day) => [
                $day => $assignedSchedules->where('day', $day)->values(),
            ])
            ->filter(fn (Collection $daySchedules) => $daySchedules->isNotEmpty());
    }

    // ============================================================
    // FILTER OPTION BUILDERS
    // ============================================================

    private function getFacultyDepartments(): array
    {
        $user = Auth::user();

        if (in_array($user->role, ['dean', 'oic'])) {
            return [$user->department];
        }

        return $this->collectDepartmentCodes(
            Faculty::query()->select('department')->distinct()->pluck('department')
        );
    }

    private function getScheduleDepartments(): array
    {
        $scheduleDepartments = Schedule::query()->select('department')->distinct()->pluck('department');
        $subjectDepartments  = Subject::query()->select('department')->distinct()->pluck('department');
        $departmentCodes     = Department::query()->select('code')->distinct()->pluck('code');

        return $this->collectDepartmentCodes($scheduleDepartments, $subjectDepartments, $departmentCodes);
    }

    private function collectDepartmentCodes(...$collections): array
    {
        $codes = collect($collections)
            ->flatMap(fn ($items) => collect($items))
            ->filter()
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $codes ?: ['CCS', 'CTE', 'COC', 'SHTM'];
    }

    private function getAvailableMajors()
    {
        $scheduleMajors = Schedule::query()->select('major')->distinct()->pluck('major');
        $subjectMajors  = Subject::query()->select('major')->distinct()->pluck('major');

        return collect(self::DEFAULT_MAJOR_FILTERS)
            ->merge($scheduleMajors)
            ->merge($subjectMajors)
            ->filter()
            ->map(fn ($major) => trim((string) $major))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function getAvailableYearLevels()
    {
        $scheduleYears = Schedule::query()->select('year_level')->distinct()->pluck('year_level');
        $subjectYears  = Subject::query()->select('year_level')->distinct()->pluck('year_level');

        $years = collect([1, 2, 3, 4])
            ->merge($scheduleYears)
            ->merge($subjectYears)
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $years ?: [1, 2, 3, 4];
    }

    private function getAvailableSections()
    {
        $scheduleSections = Schedule::query()->select('section')->distinct()->pluck('section');
        $subjectSections  = Subject::query()->select('section')->distinct()->pluck('section');

        return $scheduleSections
            ->merge($subjectSections)
            ->filter()
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAvailableSubjectTypes()
    {
        $user    = Auth::user();
        $faculty = $this->selectedFaculty;

        if ($faculty) {
            if ($faculty->teaching_specialization === 'Minor') {
                return ['Minor'];
            }

            if ($faculty->teaching_specialization === 'Major') {
                return ['Major'];
            }

            return ['Major', 'Minor'];
        }

        if ($user->role === 'associate_dean') {
            return ['Minor'];
        }

        if (in_array($user->role, ['dean', 'oic'])) {
            return ['Major'];
        }

        return ['Major', 'Minor'];
    }

    private function sortSchedules(Collection $schedules): Collection
    {
        $dayOrder = array_flip(Setting::getActiveDays());

        return $schedules
            ->sortBy(function (Schedule $schedule) use ($dayOrder) {
                $dayIndex = $dayOrder[$schedule->day] ?? 99;
                $time     = Carbon::parse($schedule->start_time)->format('H:i:s');

                return sprintf('%02d-%s-%08d', $dayIndex, $time, $schedule->id);
            })
            ->values();
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        $user      = Auth::user();
        $faculties = $this->getFacultyQuery()
            ->with('schedules.subject')
            ->orderBy('full_name', 'asc')
            ->get()
            ->each(function (Faculty $faculty) {
                $faculty->assigned_units = $faculty->schedules
                    ->pluck('subject')
                    ->filter()
                    ->unique('id')
                    ->sum('units');
            });

        $availableSubjects           = $this->getAvailableSubjects();
        $groupedAvailableSubjects    = $this->getGroupedAvailableSubjects();
        $currentFaculty         = $this->selectedFaculty;
        $assignedSchedules      = $this->assignedSchedules();
        $assignedSubjects       = $this->assignedSubjects();
        $groupedAssignedSubjects = $this->groupedAssignedSubjects();
        $facultySummary         = $this->getFacultySummary();
        $facultyDepartments     = $this->getFacultyDepartments();
        $scheduleDepartments    = $this->getScheduleDepartments();
        $majors                 = $this->getAvailableMajors();
        $yearLevels             = $this->getAvailableYearLevels();
        $sections               = $this->getAvailableSections();
        $subjectTypes           = $this->getAvailableSubjectTypes();
        $employmentTypes        = ['Full-Time', 'Part-Time'];
        $facultyConflicts       = $this->getFacultyConflicts($assignedSchedules);
        $scheduleGroups         = $this->getScheduleGroups($assignedSchedules);
        $activeDays             = Setting::getActiveDays();

        return view('livewire.faculty-loading', [
            'faculties'               => $faculties,
            'availableSubjects'          => $availableSubjects,
            'groupedAvailableSubjects'   => $groupedAvailableSubjects,
            'assignedSchedules'       => $assignedSchedules,
            'assignedSubjects'        => $assignedSubjects,
            'groupedAssignedSubjects' => $groupedAssignedSubjects,
            'currentFaculty'          => $currentFaculty,
            'facultySummary'          => $facultySummary,
            'facultyDepartments'      => $facultyDepartments,
            'scheduleDepartments'     => $scheduleDepartments,
            'majors'                  => $majors,
            'yearLevels'              => $yearLevels,
            'sections'                => $sections,
            'employmentTypes'         => $employmentTypes,
            'subjectTypes'            => $subjectTypes,
            'userRole'                => $user->role,
            'activeTab'               => $this->activeTab,
            'facultyConflicts'        => $facultyConflicts,
            'scheduleGroups'          => $scheduleGroups,
            'activeDays'              => $activeDays,
            'canOverrideWarnings'     => $this->canOverrideAssignmentWarnings($user),
        ])->layout('layouts.app');
    }
}