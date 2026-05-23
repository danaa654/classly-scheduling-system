<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

    /** Filters the faculty roster by faculty_scope track (departmental / gened / cross_department). */
    public string $selectedScope = 'all';

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

    public array $assignmentRecommendations = [];

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

    private function activeScheduleQuery(): Builder
    {
        return Schedule::activeTerm();
    }

    private function activeSubjectQuery(): Builder
    {
        return Subject::activeTerm();
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
        $this->subjectMajorFilter = 'all';
        $this->subjectYearLevelFilter = 'all';
        $this->subjectSectionFilter = 'all';
        $this->subjectTypeFilter = 'all';
        $this->showUnassignedOnly = false;
    }

    #[Computed]
    public function selectedFaculty()
    {
        return $this->selectedFacultyId
            ? Faculty::with(['schedules' => fn ($query) => $query->activeTerm()->with(['subject', 'room'])])
                ->find($this->selectedFacultyId)
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

        return $this->activeScheduleQuery()
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
     */
    private function groupedAssignedSubjects(): Collection
    {
        return $this->assignedSchedules()
            ->groupBy(fn (Schedule $schedule) => $schedule->subject_id.'::'.($schedule->section ?? 'none')
            )
            ->map(function (Collection $group) {
                /** @var Schedule $first */
                $first = $group->first();
                $subject = $first->subject;
                $room = $first->room;

                $scheduleLines = $group
                    ->map(fn (Schedule $s) => trim(
                        ($s->day ?? 'N/A')
                        .' / '
                        .Carbon::parse($s->start_time)->format('h:i A')
                        .' - '
                        .Carbon::parse($s->end_time)->format('h:i A')
                    )
                    )
                    ->unique()
                    ->values()
                    ->implode('<br>');

                $department = Department::normalizeCode($first->department ?? $subject?->department) ?? 'N/A';
                $major = $first->major ?? $subject?->major ?? 'N/A';
                $year = $first->year_level ?? $subject?->year_level ?? '?';
                $section = $first->section ?? $subject?->section ?? 'N/A';

                return [
                    'subject_code' => $subject?->subject_code ?? 'N/A',
                    'edp_code' => $subject?->edp_code ?? 'No EDP',
                    'description' => $subject?->description ?? 'Untitled subject',
                    'group' => "{$department} / {$major} / Y{$year} / {$section}",
                    'room' => $room?->room_name ?? 'No room',
                    'schedule' => $scheduleLines,
                    'units' => $subject?->units ?? 0,
                    'type' => $subject?->type ?? 'N/A',
                    'schedule_ids' => $group->pluck('id')->all(),
                    'first_schedule_id' => $first->id,
                ];
            })
            ->values();
    }

    // ============================================================
    // DEPARTMENT-LEVEL SUMMARY (State A — no faculty selected)
    // ============================================================

    /**
     * Computes the four department-level overview metrics shown when no faculty is selected.
     *
     * - totalSubjects    : All schedule rows in the active department filter that have a subject.
     * - assignedSubjects : Schedule rows with a faculty_id set.
     * - subjectsLeft     : Unassigned schedule rows.
     * - facultyProcessed : Unique faculty members who hold ≥1 subject inside this department
     *                      (includes departmental staff AND GenEd staff assigned here).
     */
    public function getDepartmentSummary(): array
    {
        $user = Auth::user();

        // Resolve the active department scope for the filter.
        // When the user is a dean/oic we scope to their department automatically.
        $activeDepartment = null;

        if ($this->departmentFilter !== 'all') {
            $activeDepartment = $this->departmentFilter;
        } elseif (in_array($user->role, ['dean', 'oic'], true) && $user->department) {
            $activeDepartment = Department::normalizeCode($user->department);
        }

        // ── Total schedule rows (each unique subject-section-day slot counts as one) ──
        $baseQuery = $this->activeScheduleQuery()
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->whereHas('subject');

        if ($activeDepartment) {
            $aliases = $this->departmentAliases($activeDepartment);
            $baseQuery->where(function (Builder $q) use ($aliases) {
                $q->whereIn('department', $aliases)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->whereIn('department', $aliases));
            });
        } elseif ($user->role === 'associate_dean') {
            $baseQuery->whereHas('subject', fn (Builder $sq) => $sq->where('type', 'Minor'));
        }

        $totalSubjects = (clone $baseQuery)->count();

        // ── Assigned (has a faculty_id) ──
        $assignedSubjects = (clone $baseQuery)->whereNotNull('faculty_id')->count();

        // ── Left (no faculty_id) ──
        $subjectsLeft = $totalSubjects - $assignedSubjects;

        // ── Faculty Processed ──
        // Departmental staff assigned here OR GenEd/Cross-dept staff assigned here.
        $assignedFacultyIds = (clone $baseQuery)
            ->whereNotNull('faculty_id')
            ->pluck('faculty_id')
            ->filter()
            ->unique()
            ->values();

        // Count of distinct faculty in that id set who are either:
        //   (a) from the active department, OR
        //   (b) GenEd / Cross-Department scope (institution-wide)
        $facultyProcessedQuery = Faculty::query()
            ->whereIn('id', $assignedFacultyIds);

        if ($activeDepartment) {
            $aliases = $this->departmentAliases($activeDepartment);
            $facultyProcessedQuery->where(function (Builder $q) use ($aliases) {
                $q->whereIn('department', $aliases)
                    ->orWhereIn('faculty_scope', [Faculty::SCOPE_GENED, Faculty::SCOPE_CROSS_DEPARTMENT]);
            });
        }

        $facultyProcessed = $facultyProcessedQuery->count();

        return [
            'totalSubjects'    => $totalSubjects,
            'assignedSubjects' => $assignedSubjects,
            'subjectsLeft'     => $subjectsLeft,
            'facultyProcessed' => $facultyProcessed,
            'activeDepartment' => $activeDepartment ?? 'All',
        ];
    }

    // ============================================================
    // FACULTY SUMMARY (State B — faculty selected)
    // ============================================================

    public function getFacultySummary()
    {
        if (! $this->selectedFaculty) {
            return null;
        }

        $subjects = $this->assignedSubjects();
        $faculty  = $this->selectedFaculty;

        $totalUnits       = $subjects->sum('units') ?? 0;
        $maxUnits         = $faculty->max_units ?? 21;
        $utilizationPercent = $maxUnits > 0 ? round(($totalUnits / $maxUnits) * 100) : 0;

        // For GenEd faculty: everything they teach is "minor/gened" — treat all as minor load.
        // For departmental / cross-department: split by subject type field.
        if ($faculty->isGenEd()) {
            $majorSubjects = collect();
            $minorSubjects = $subjects;
        } else {
            $majorSubjects = $subjects->filter(fn ($s) => strtolower(trim((string) ($s->type ?? ''))) === 'major');
            $minorSubjects = $subjects->filter(fn ($s) => strtolower(trim((string) ($s->type ?? ''))) !== 'major');
        }

        $majorCount = $majorSubjects->count();
        $minorCount = $minorSubjects->count();
        $majorUnits = (int) $majorSubjects->sum('units');
        $minorUnits = (int) $minorSubjects->sum('units');

        // Progress percentages relative to max_units cap.
        $majorPercent = $maxUnits > 0 ? min(100, round(($majorUnits / $maxUnits) * 100)) : 0;
        $minorPercent = $maxUnits > 0 ? min(100, round(($minorUnits / $maxUnits) * 100)) : 0;

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
            'majorPercent'       => $majorPercent,
            'minorPercent'       => $minorPercent,
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

        $schedule = $this->activeScheduleQuery()
            ->with(['subject', 'room'])
            ->find($scheduleId);
        $faculty = Faculty::find($this->selectedFacultyId);

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
            $this->openAssignmentFailure($schedule, $faculty, [
                $this->eligibilityWarning($faculty, $schedule->subject, $schedule),
            ]);
            $this->toast('error', 'Assignment blocked by faculty eligibility rules.');

            return;
        }

        $warnings = $this->assignmentWarnings($faculty, $schedule);

        if ($warnings !== []) {
            $this->pendingAssignmentScheduleId = $schedule->id;
            $this->pendingAssignmentWarnings = $warnings;
            $this->assignmentRecommendations = $this->buildAssignmentRecommendations($faculty, $schedule);
            $this->conflictModalOpen = true;
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

        $groupIds = $this->pendingAssignmentGroupIds;

        if (! empty($groupIds)) {
            $schedules = $this->activeScheduleQuery()
                ->with(['subject', 'room'])
                ->findMany($groupIds);

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

        $schedule = $this->activeScheduleQuery()
            ->with(['subject', 'room'])
            ->find($this->pendingAssignmentScheduleId);

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

    public function useFacultySuggestion(int $facultyId): void
    {
        [$currentFaculty, $schedules] = $this->pendingAssignmentContext();
        $faculty = Faculty::find($facultyId);

        if (! $faculty || $schedules->isEmpty()) {
            $this->toast('error', 'Suggestion is no longer available.');

            return;
        }

        $this->selectedFacultyId = $faculty->id;
        $warnings = $this->warningsForSchedules($faculty, $schedules);

        if ($warnings !== []) {
            $this->pendingAssignmentWarnings = $warnings;
            $this->assignmentRecommendations = $this->buildAssignmentRecommendations($faculty, $schedules->first());
            $this->toast('error', 'Suggested faculty is no longer available for this schedule.');

            return;
        }

        $schedules->count() > 1
            ? $this->persistGroupAssignment($schedules, $faculty)
            : $this->persistAssignment($schedules->first(), $faculty);
    }

    public function useSlotSuggestion(string $day, string $startTime, string $endTime): void
    {
        [$faculty, $schedules] = $this->pendingAssignmentContext();

        if (! $faculty || $schedules->isEmpty()) {
            $this->toast('error', 'Suggestion is no longer available.');

            return;
        }

        $singleSchedule = $schedules->count() === 1;

        $schedules->each(function (Schedule $schedule) use ($day, $startTime, $endTime, $singleSchedule) {
            if ($singleSchedule) {
                $schedule->day = $day;
            }

            $schedule->start_time = Carbon::parse($startTime)->format('H:i:s');
            $schedule->end_time = Carbon::parse($endTime)->format('H:i:s');
        });

        $warnings = $this->warningsForSchedules($faculty, $schedules, includeSectionChecks: true);

        if ($warnings !== []) {
            $this->pendingAssignmentWarnings = $warnings;
            $this->assignmentRecommendations = $this->buildAssignmentRecommendations($faculty, $schedules->first());
            $this->toast('error', 'Suggested timeslot is no longer available.');

            return;
        }

        try {
            DB::transaction(function () use ($schedules, $faculty, $singleSchedule, $day, $startTime, $endTime) {
                $ids = $schedules->pluck('id')->all();
                $fresh = $this->activeScheduleQuery()
                    ->lockForUpdate()
                    ->findMany($ids);

                foreach ($fresh as $schedule) {
                    $payload = [
                        'start_time' => Carbon::parse($startTime)->format('H:i:s'),
                        'end_time'   => Carbon::parse($endTime)->format('H:i:s'),
                        'faculty_id' => $faculty->id,
                        'status'     => Schedule::STATUS_PARTIAL,
                    ];

                    if ($singleSchedule) {
                        $payload['day'] = $day;
                    }

                    $schedule->update($payload);
                }
            });

            $this->resetPendingAssignment();
            unset($this->selectedFaculty);
            $this->toast('success', 'Suggested timeslot applied and faculty assigned.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Could not apply the suggested timeslot.');
        }
    }

    public function useRoomSuggestion(int $roomId): void
    {
        [$faculty, $schedules] = $this->pendingAssignmentContext();
        $room = Room::find($roomId);

        if (! $faculty || ! $room || $schedules->isEmpty()) {
            $this->toast('error', 'Suggestion is no longer available.');

            return;
        }

        $schedules->each(function (Schedule $schedule) use ($room) {
            $schedule->room_id = $room->id;
            $schedule->setRelation('room', $room);
        });

        $warnings = $this->warningsForSchedules($faculty, $schedules, includeSectionChecks: true);

        if ($warnings !== []) {
            $this->pendingAssignmentWarnings = $warnings;
            $this->assignmentRecommendations = $this->buildAssignmentRecommendations($faculty, $schedules->first());
            $this->toast('error', 'Suggested room is no longer available.');

            return;
        }

        try {
            DB::transaction(function () use ($schedules, $faculty, $room) {
                $fresh = $this->activeScheduleQuery()
                    ->lockForUpdate()
                    ->findMany($schedules->pluck('id')->all());

                foreach ($fresh as $schedule) {
                    $schedule->update([
                        'room_id'    => $room->id,
                        'faculty_id' => $faculty->id,
                        'status'     => Schedule::STATUS_PARTIAL,
                    ]);
                }
            });

            $this->resetPendingAssignment();
            unset($this->selectedFaculty);
            $this->toast('success', 'Suggested room applied and faculty assigned.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Could not apply the suggested room.');
        }
    }

    private function pendingAssignmentContext(): array
    {
        if (! $this->selectedFacultyId || ! $this->pendingAssignmentScheduleId) {
            return [null, collect()];
        }

        $ids = $this->pendingAssignmentGroupIds ?: [$this->pendingAssignmentScheduleId];

        return [
            Faculty::find($this->selectedFacultyId),
            $this->activeScheduleQuery()
                ->with(['subject', 'room'])
                ->findMany($ids),
        ];
    }

    private function warningsForSchedules(Faculty $faculty, Collection $schedules, bool $includeSectionChecks = false): array
    {
        $warnings = [];
        $overloadFlagged = false;
        $conflictService = app(ScheduleConflictService::class);

        foreach ($schedules as $schedule) {
            if (! $schedule->subject) {
                continue;
            }

            if (! $this->canAssignSubject(Auth::user(), $faculty, $schedule->subject)) {
                $warnings[] = $this->eligibilityWarning($faculty, $schedule->subject, $schedule);
            }

            if ($includeSectionChecks) {
                $sectionConflict = $conflictService->checkSectionConflict(
                    $schedule->subject,
                    (string) $schedule->day,
                    Carbon::parse($schedule->start_time)->format('H:i:s'),
                    Carbon::parse($schedule->end_time)->format('H:i:s'),
                    $schedule->id,
                    $schedule->room
                );

                if (($sectionConflict['status'] ?? true) === false) {
                    $warnings[] = $this->warningFromConflict(
                        $sectionConflict,
                        $faculty,
                        $schedule,
                        'SECTION_CONFLICT',
                        'Student Group Conflict',
                        'The student group already has a class during this time.'
                    );
                }
            }

            foreach ($this->assignmentWarnings($faculty, $schedule) as $warning) {
                if (($warning['type'] ?? '') === 'OVERLOAD') {
                    if ($overloadFlagged) {
                        continue;
                    }
                    $overloadFlagged = true;
                }

                if (! collect($warnings)->contains(fn (array $existing) => ($existing['message'] ?? '') === ($warning['message'] ?? ''))) {
                    $warnings[] = $warning;
                }
            }
        }

        return collect($warnings)->unique(fn (array $warning) => ($warning['type'] ?? '').'|'.($warning['message'] ?? ''))->values()->all();
    }

    private function persistAssignment(Schedule $schedule, Faculty $faculty, bool $overridden = false): void
    {
        try {
            $subjectCode = $schedule->subject->subject_code;
            $scheduleId = $schedule->id;

            DB::transaction(function () use ($scheduleId, $faculty) {
                $fresh = $this->activeScheduleQuery()
                    ->lockForUpdate()
                    ->findOrFail($scheduleId);

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
        $warnings = [];
        $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i:s');
        $assignedSchedules = $this->activeScheduleQuery()
            ->where('faculty_id', $faculty->id)
            ->with(['subject', 'room'])
            ->get();
        $currentSubjects = $assignedSchedules
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();
        $currentUnits = $currentSubjects->sum('units');
        $duplicateSubject = $assignedSchedules->first(
            fn (Schedule $assigned) => (int) $assigned->subject_id === (int) $schedule->subject_id
                && ! $this->sameSubjectOffering($assigned, $schedule)
        );
        $subjectAlreadyAssigned = (bool) $duplicateSubject;
        $newTotal = $currentUnits + ($subjectAlreadyAssigned ? 0 : (int) $schedule->subject->units);
        $maxUnits = (int) ($faculty->max_units ?? 21);

        if ($subjectAlreadyAssigned) {
            $warnings[] = [
                'type'       => 'DUPLICATE_SUBJECT',
                'title'      => 'Duplicate Subject Assignment',
                'message'    => "{$faculty->full_name} is already assigned to {$schedule->subject->subject_code}.",
                'details'    => $this->warningDetails(
                    $faculty,
                    $schedule,
                    $duplicateSubject,
                    'This faculty member is already assigned to the same subject.'
                ),
                'overridable' => false,
            ];
        }

        if ($newTotal > $maxUnits) {
            $warnings[] = [
                'type'       => 'OVERLOAD',
                'title'      => 'Faculty Load Limit Exceeded',
                'message'    => "{$schedule->subject->subject_code} would bring {$faculty->full_name} to {$newTotal}/{$maxUnits} units.",
                'details'    => $this->warningDetails(
                    $faculty,
                    $schedule,
                    null,
                    "Maximum load is {$maxUnits} units; this assignment would reach {$newTotal} units."
                ),
                'overridable' => false,
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
            $warnings[] = $this->warningFromConflict(
                $facultyConflict,
                $faculty,
                $schedule,
                'FACULTY_CONFLICT',
                'Faculty Conflict Detected',
                'Faculty schedule conflict detected.'
            );
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
                $warnings[] = $this->warningFromConflict(
                    $roomConflict,
                    $faculty,
                    $schedule,
                    'ROOM_CONFLICT',
                    'Room Conflict Detected',
                    'Room schedule conflict detected.'
                );
            }
        }

        $availability = $conflictService->checkFacultyAvailability(
            $faculty,
            $schedule->day,
            $startTime,
            $endTime
        );

        if (($availability['status'] ?? true) === false) {
            $warnings[] = $this->warningFromConflict(
                $availability,
                $faculty,
                $schedule,
                'FACULTY_AVAILABILITY',
                'Faculty Availability Conflict',
                'Faculty is not available during this time.'
            );
        }

        return $warnings;
    }

    private function sameSubjectOffering(Schedule $first, Schedule $second): bool
    {
        return (int) $first->subject_id === (int) $second->subject_id
            && (string) $first->section === (string) $second->section
            && Carbon::parse($first->start_time)->format('H:i:s') === Carbon::parse($second->start_time)->format('H:i:s')
            && Carbon::parse($first->end_time)->format('H:i:s') === Carbon::parse($second->end_time)->format('H:i:s');
    }

    private function warningFromConflict(
        array $conflict,
        Faculty $faculty,
        Schedule $schedule,
        string $fallbackType,
        string $fallbackTitle,
        string $fallbackMessage
    ): array {
        $type = $conflict['conflict_type'] ?? $fallbackType;
        $title = $conflict['title'] ?? $fallbackTitle;

        if (in_array($type, ['FACULTY_CONFLICT', 'SAME_TIME_CONFLICT', 'OVERLAPPING_TIME_CONFLICT'], true)) {
            $type = $this->facultyConflictType($conflict, $schedule);
            $title = $type === 'SAME_TIME_CONFLICT'
                ? 'Same Time Faculty Conflict'
                : 'Overlapping Faculty Conflict';
        }

        $message = $conflict['message'] ?? $fallbackMessage;

        return [
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'details'    => $this->warningDetailsFromConflict($faculty, $schedule, $conflict, $message),
            'overridable' => false,
        ];
    }

    private function warningDetails(Faculty $faculty, Schedule $schedule, ?Schedule $conflictingSchedule, string $reason): array
    {
        return [
            'faculty_name'             => $faculty->full_name,
            'requested_subject'        => $schedule->subject?->subject_code ?? 'Requested subject',
            'conflicting_subject'      => $conflictingSchedule?->subject?->subject_code ?? $schedule->subject?->subject_code ?? 'Subject',
            'conflicting_subject_name' => $conflictingSchedule?->subject?->description ?? $schedule->subject?->description ?? null,
            'day'                      => $conflictingSchedule?->day ?? $schedule->day,
            'time'                     => $conflictingSchedule ? $this->formatScheduleTime($conflictingSchedule) : $this->formatScheduleTime($schedule),
            'room'                     => $conflictingSchedule?->room?->room_name ?? $schedule->room?->room_name ?? 'Unassigned room',
            'reason'                   => $reason,
        ];
    }

    private function warningDetailsFromConflict(Faculty $faculty, Schedule $schedule, array $conflict, string $reason): array
    {
        $details = $conflict['details'] ?? [];
        $summary = $conflict['conflicting_schedule'] ?? [];
        $conflictingStart = $details['conflicting_start'] ?? null;
        $conflictingEnd   = $details['conflicting_end'] ?? null;
        $conflictingTime  = $summary['time'] ?? ($conflictingStart && $conflictingEnd ? "{$conflictingStart} - {$conflictingEnd}" : null);

        return [
            'faculty_name'             => $faculty->full_name,
            'requested_subject'        => $schedule->subject?->subject_code ?? ($details['requested_subject'] ?? 'Requested subject'),
            'conflicting_subject'      => $summary['subject_code'] ?? $details['conflicting_subject'] ?? $schedule->subject?->subject_code ?? 'Subject',
            'conflicting_subject_name' => $summary['subject_name'] ?? $details['conflicting_subject_name'] ?? null,
            'day'                      => $summary['day'] ?? $details['day'] ?? $schedule->day,
            'time'                     => $conflictingTime ?? $this->formatScheduleTime($schedule),
            'room'                     => $summary['room'] ?? $details['room'] ?? $schedule->room?->room_name ?? 'Unassigned room',
            'reason'                   => $reason,
        ];
    }

    private function facultyConflictType(array $conflict, Schedule $schedule): string
    {
        $conflictingStart = $conflict['details']['conflicting_start'] ?? null;
        $conflictingEnd   = $conflict['details']['conflicting_end'] ?? null;

        if (! $conflictingStart || ! $conflictingEnd) {
            return 'OVERLAPPING_TIME_CONFLICT';
        }

        $scheduleStart = $this->parseTimeOrNull($conflict['details']['requested_start'] ?? null)
            ?? Carbon::parse($schedule->start_time)->format('H:i:s');
        $scheduleEnd   = $this->parseTimeOrNull($conflict['details']['requested_end'] ?? null)
            ?? Carbon::parse($schedule->end_time)->format('H:i:s');

        return ($scheduleStart === $conflictingStart && $scheduleEnd === $conflictingEnd)
            ? 'SAME_TIME_CONFLICT'
            : 'OVERLAPPING_TIME_CONFLICT';
    }

    private function formatScheduleTime(Schedule $schedule): ?string
    {
        try {
            $start = Carbon::parse($schedule->start_time)->format('H:i:s');
            $end   = Carbon::parse($schedule->end_time)->format('H:i:s');

            return "{$start} - {$end}";
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTimeOrNull(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function openAssignmentFailure(Schedule $schedule, Faculty $faculty, array $warnings, array $groupIds = []): void
    {
        $this->pendingAssignmentScheduleId = $schedule->id;
        $this->pendingAssignmentGroupIds   = $groupIds;
        $this->pendingAssignmentWarnings   = $warnings;
        $this->assignmentRecommendations   = $this->buildAssignmentRecommendations($faculty, $schedule);
        $this->conflictModalOpen           = true;
    }

    private function eligibilityWarning(Faculty $faculty, ?Subject $subject, ?Schedule $schedule = null): array
    {
        $subjectCode = $subject?->subject_code ?? 'this subject';

        if (! $subject) {
            $message = 'Subject data is missing.';
        } elseif ($this->subjectIsMinorOrGenEd($subject) && ! $faculty->canTeachMinorSubjects()) {
            $message = "{$subjectCode} is a minor or GenEd subject. Only GenEd faculty or faculty marked Can Teach Minor can be assigned.";
        } elseif ($faculty->isGenEd() && ! $this->subjectIsMinorOrGenEd($subject)) {
            $message = "{$faculty->full_name} is a GenEd faculty member and cannot be assigned to major departmental subjects.";
        } elseif (! $faculty->canTeachDepartment($subject->department)) {
            $message = "{$faculty->full_name} is departmental faculty for {$faculty->displayDepartment()} and is not eligible for {$subject->department}.";
        } else {
            $message = "{$faculty->full_name} is not eligible for {$subjectCode} under the current role and department rules.";
        }

        return [
            'type'    => 'FACULTY_ELIGIBILITY',
            'title'   => 'Invalid Faculty Eligibility',
            'message' => $message,
            'details' => [
                'faculty_name'             => $faculty->full_name,
                'requested_subject'        => $subjectCode,
                'conflicting_subject'      => $subjectCode,
                'conflicting_subject_name' => $subject?->description,
                'day'                      => $schedule?->day,
                'time'                     => $schedule ? $this->formatScheduleTime($schedule) : null,
                'room'                     => $schedule?->room?->room_name,
                'reason'                   => $message,
            ],
            'overridable' => false,
        ];
    }

    private function buildAssignmentRecommendations(Faculty $faculty, Schedule $schedule): array
    {
        return [
            'faculty' => $this->recommendedFaculty($faculty, $schedule),
            'slots'   => $this->recommendedSlots($faculty, $schedule),
            'rooms'   => $this->recommendedRooms($schedule),
        ];
    }

    private function recommendedFaculty(Faculty $currentFaculty, Schedule $schedule): array
    {
        if (! $schedule->subject) {
            return [];
        }

        $conflictService = app(ScheduleConflictService::class);
        $start = Carbon::parse($schedule->start_time)->format('H:i:s');
        $end   = Carbon::parse($schedule->end_time)->format('H:i:s');
        $user  = Auth::user();

        return Faculty::query()
            ->approved()
            ->with(['schedules' => fn ($query) => $query->activeTerm()->with('subject')])
            ->whereKeyNot($currentFaculty->id)
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Faculty $candidate) => $this->canAssignSubject($user, $candidate, $schedule->subject))
            ->filter(fn (Faculty $candidate) => ! $this->facultyWouldExceedLoad($candidate, $schedule))
            ->filter(function (Faculty $candidate) use ($conflictService, $schedule, $start, $end) {
                $conflict = $conflictService->checkFacultyConflict($candidate->id, $schedule->day, $start, $end, $schedule->id);
                if (($conflict['status'] ?? true) === false) {
                    return false;
                }

                $availability = $conflictService->checkFacultyAvailability($candidate, $schedule->day, $start, $end);

                return ($availability['status'] ?? true) !== false;
            })
            ->map(function (Faculty $candidate) {
                $assignedUnits = $candidate->schedules
                    ->pluck('subject')
                    ->filter()
                    ->unique('id')
                    ->sum('units');

                return [
                    'id'              => $candidate->id,
                    'name'            => $candidate->full_name,
                    'scope'           => $candidate->scopeLabel(),
                    'department'      => $candidate->displayDepartment(),
                    'remaining_units' => max(0, (int) ($candidate->max_units ?? 21) - (int) $assignedUnits),
                ];
            })
            ->sortByDesc('remaining_units')
            ->take(5)
            ->values()
            ->all();
    }

    private function facultyWouldExceedLoad(Faculty $faculty, Schedule $schedule): bool
    {
        $schedules = $faculty->relationLoaded('schedules')
            ? $faculty->schedules
            : $faculty->schedules()->activeTerm()->with('subject')->get();
        $currentSubjects = $schedules
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();
        $alreadyHasSubject = $currentSubjects->contains('id', $schedule->subject_id);
        $currentUnits = (int) $currentSubjects->sum('units');
        $newUnits     = $alreadyHasSubject ? 0 : (int) ($schedule->subject?->units ?? 0);

        return ($currentUnits + $newUnits) > (int) ($faculty->max_units ?? 21);
    }

    private function recommendedSlots(Faculty $faculty, Schedule $schedule): array
    {
        if (! $schedule->subject || ! $schedule->room) {
            return [];
        }

        $conflictService  = app(ScheduleConflictService::class);
        $settings         = Setting::getScheduleSettings();
        $durationMinutes  = Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time));
        $boundsStart      = Carbon::parse($settings['start_time']);
        $latestStart      = Carbon::parse($settings['end_time'])->subMinutes($durationMinutes);
        $suggestions      = [];

        if ($latestStart->lt($boundsStart)) {
            return [];
        }

        foreach ($settings['active_days'] as $day) {
            foreach (CarbonPeriod::create($boundsStart->copy(), '30 minutes', $latestStart->copy()) as $slotStart) {
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);
                $start   = $slotStart->format('H:i:s');
                $end     = $slotEnd->format('H:i:s');

                if ($day === $schedule->day && $start === Carbon::parse($schedule->start_time)->format('H:i:s')) {
                    continue;
                }

                if ($conflictService->overlapsLunchBreak($start, $end)) {
                    continue;
                }

                $checks = [
                    $conflictService->checkRoomConflict($schedule->room_id, $day, $start, $end, $schedule->id),
                    $conflictService->checkSectionConflict($schedule->subject, $day, $start, $end, $schedule->id, $schedule->room),
                    $conflictService->checkFacultyConflict($faculty->id, $day, $start, $end, $schedule->id),
                    $conflictService->checkFacultyAvailability($faculty, $day, $start, $end),
                ];

                if (collect($checks)->contains(fn (array $check) => ($check['status'] ?? true) === false)) {
                    continue;
                }

                $suggestions[] = [
                    'label'      => "{$day} ".Carbon::parse($start)->format('h:i A').'-'.Carbon::parse($end)->format('h:i A'),
                    'day'        => $day,
                    'start_time' => $start,
                    'end_time'   => $end,
                    'time'       => Carbon::parse($start)->format('h:i A').' - '.Carbon::parse($end)->format('h:i A'),
                ];

                if (count($suggestions) >= 4) {
                    return $suggestions;
                }
            }
        }

        return $suggestions;
    }

    private function recommendedRooms(Schedule $schedule): array
    {
        if (! $schedule->subject || ! $schedule->room_id) {
            return [];
        }

        $conflictService = app(ScheduleConflictService::class);
        $start           = Carbon::parse($schedule->start_time)->format('H:i:s');
        $end             = Carbon::parse($schedule->end_time)->format('H:i:s');

        return Room::query()
            ->available()
            ->whereKeyNot($schedule->room_id)
            ->orderBy('room_name')
            ->get()
            ->filter(function (Room $room) use ($schedule, $conflictService, $start, $end) {
                try {
                    if (! $room->isCompatibleWithSubject($schedule->subject)) {
                        return false;
                    }
                } catch (\Throwable) {
                    return false;
                }

                $conflict = $conflictService->checkRoomConflict($room->id, $schedule->day, $start, $end, $schedule->id, $schedule->subject, $room);

                return ($conflict['status'] ?? true) !== false;
            })
            ->take(4)
            ->map(fn (Room $room) => [
                'id'   => $room->id,
                'name' => $room->room_name,
                'type' => $room->type,
            ])
            ->values()
            ->all();
    }

    private function resetPendingAssignment(): void
    {
        $this->conflictModalOpen          = false;
        $this->pendingAssignmentScheduleId = null;
        $this->pendingAssignmentWarnings  = [];
        $this->pendingAssignmentGroupIds  = [];
        $this->assignmentRecommendations  = [];
    }

    // ============================================================
    // REMOVE SUBJECT
    // ============================================================

    public function removeSubject($scheduleId)
    {
        $schedule = $this->activeScheduleQuery()
            ->with('subject')
            ->find($scheduleId);

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

    public function removeSubjectGroup(array $scheduleIds): void
    {
        if (empty($scheduleIds)) {
            $this->toast('error', 'No schedules provided.');

            return;
        }

        $schedules = $this->activeScheduleQuery()
            ->with('subject')
            ->findMany($scheduleIds);

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
        $query = $this->activeScheduleQuery()
            ->assignable()
            ->whereNotNull('faculty_id');

        if (in_array($user->role, ['dean', 'oic']) && $user->department) {
            $query->where(function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->whereHas('subject', function (Builder $majorQuery) use ($user) {
                    $majorQuery->where('type', 'Major')
                        ->whereIn('department', Department::aliasesFor($user->department));
                })->orWhere(function (Builder $minorQuery) use ($user) {
                    $minorQuery
                        ->whereHas('subject', fn (Builder $sq) => $sq->where('type', 'Minor'))
                        ->whereHas('faculty', fn (Builder $fq) => $fq->whereIn('department', Department::aliasesFor($user->department)));
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
        $conflict = $this->activeScheduleQuery()
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
        $roomName    = $conflict->room?->room_name ?? 'Unknown Room';

        return "Professor {$faculty->full_name} is already teaching {$subjectCode} in Room {$roomName} during this time.";
    }

    private function canAssignSubject($user, $faculty, $subject)
    {
        if (! $user || ! $faculty || ! $subject || ! $faculty->isEligibleForSubject($subject)) {
            return false;
        }

        if (in_array($user->role, ['admin', 'registrar'])) {
            return true;
        }

        if ($user->role === 'associate_dean') {
            return $this->subjectIsMinorOrGenEd($subject);
        }

        if (in_array($user->role, ['dean', 'oic'])) {
            if ($faculty->isGenEd()) {
                return $this->subjectIsMinorOrGenEd($subject);
            }

            if ($this->subjectIsMinorOrGenEd($subject)) {
                return $faculty->canTeachMinorSubjects();
            }

            return $this->departmentsMatch($subject->department, $user->department)
                && $this->departmentsMatch($faculty->department, $subject->department);
        }

        return $this->subjectIsMinorOrGenEd($subject)
            || $this->departmentsMatch($subject->department, $user->department);
    }

    private function subjectIsMinorOrGenEd(?Subject $subject): bool
    {
        if (! $subject) {
            return false;
        }

        $type        = strtolower(trim((string) $subject->type));
        $subjectType = strtolower(trim((string) ($subject->subject_type ?? '')));
        $department  = $this->normalizeDepartment($subject->department);
        $major       = $this->normalizeDepartment($subject->major);
        $code        = strtoupper((string) $subject->subject_code);

        return $type === 'minor'
            || $subjectType === 'minor'
            || $department === 'GENED'
            || $major === 'GENED'
            || str_contains($code, 'NSTP')
            || str_contains($code, 'PATHFIT');
    }

    private function normalizeDepartment(?string $department): string
    {
        return Department::normalizeCode($department) ?? '';
    }

    private function departmentsMatch(?string $first, ?string $second): bool
    {
        return Department::codesMatch($first, $second);
    }

    private function departmentAliases(?string $department): array
    {
        return Department::aliasesFor($department);
    }

    private function canOverrideAssignmentWarnings($user): bool
    {
        if (! $user || ! in_array($user->role, ['admin', 'registrar'], true)) {
            return false;
        }

        return collect($this->pendingAssignmentWarnings)
            ->every(fn (array $warning) => (bool) ($warning['overridable'] ?? false));
    }

    // ============================================================
    // QUERY BUILDERS
    // ============================================================

    private function getFacultyQuery()
    {
        $user  = Auth::user();
        $query = Faculty::query()
            ->approved()
            ->withCount(['schedules' => fn ($query) => $query->activeTerm()]);

        if (in_array($user->role, ['dean', 'oic'])) {
            $query->where(function (Builder $visibility) use ($user) {
                $visibility->whereIn('department', $this->departmentAliases($user->department))
                    ->orWhere('faculty_scope', Faculty::SCOPE_GENED)
                    ->orWhere('faculty_scope', Faculty::SCOPE_CROSS_DEPARTMENT);
            });
        } elseif ($user->role === 'associate_dean') {
            $query->eligibleForMinor();
        }

        if ($this->subjectTypeFilter === 'Minor') {
            $query->eligibleForMinor();
        }

        if ($this->subjectTypeFilter === 'Major') {
            $query->where('faculty_scope', '!=', Faculty::SCOPE_GENED);

            if ($this->subjectDepartmentFilter !== 'all') {
                $query->where(function (Builder $majorFaculty) {
                    $majorFaculty->whereIn('department', $this->departmentAliases($this->subjectDepartmentFilter));
                });
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_id', 'like', '%'.$this->search.'%');
            });
        }

        // ── Scope filter ──────────────────────────────────────────────────
        // 'departmental'     → ALL faculty whose home department matches the active
        //                      context, regardless of their faculty_scope value.
        //                      This is the correct rule: membership is determined by
        //                      department, not by the scope tag. A faculty member like
        //                      Villacorta (dept=CCS, scope=cross_department) is still a
        //                      core CCS asset and must appear here.
        //
        // 'gened'            → Institution-wide GenEd-scoped faculty (scope = gened).
        //
        // 'cross_department' → GUEST faculty who (a) carry the cross_department scope
        //                      tag AND (b) belong to a DIFFERENT department than the
        //                      active context. Home-department cross_department faculty
        //                      (e.g. Villacorta for CCS) are excluded here because they
        //                      are already surfaced under "Departmental".
        //
        // 'all'              → No additional restriction (default).
        if ($this->selectedScope === Faculty::SCOPE_DEPARTMENTAL) {
            // Rule: show every faculty member whose home department is the active one.
            // Do NOT filter on faculty_scope — a cross_department or gened tag does
            // not remove someone from their own department's roster.
            $dept = $this->departmentFilter !== 'all'
                ? $this->departmentFilter
                : (isset($user->department) ? $user->department : null);

            if ($dept) {
                $query->whereIn('department', $this->departmentAliases($dept));
            }
        } elseif ($this->selectedScope === Faculty::SCOPE_GENED) {
            $query->where('faculty_scope', Faculty::SCOPE_GENED);
        } elseif ($this->selectedScope === Faculty::SCOPE_CROSS_DEPARTMENT) {
            // Rule: show GUEST cross-department faculty from OTHER departments only.
            // Home-department faculty (even if tagged cross_department) belong to the
            // "Departmental" view and must be excluded here to prevent duplication and
            // the "invisible in both views" trap.
            $query->where('faculty_scope', Faculty::SCOPE_CROSS_DEPARTMENT);

            $dept = $this->departmentFilter !== 'all'
                ? $this->departmentFilter
                : (isset($user->department) ? $user->department : null);

            if ($dept) {
                // Exclude faculty whose home department IS the active department.
                $query->whereNotIn('department', $this->departmentAliases($dept));
            }
        }
        // ('all' → no scope restriction; the role-based gate above already applies)

        if ($this->departmentFilter !== 'all') {
            $query->where(function (Builder $departmentQuery) {
                $departmentQuery->whereIn('department', $this->departmentAliases($this->departmentFilter))
                    ->orWhere('faculty_scope', Faculty::SCOPE_GENED)
                    ->orWhere('faculty_scope', Faculty::SCOPE_CROSS_DEPARTMENT);
            });
        }

        return $query;
    }

    private function getAvailableSubjects()
    {
        $query = $this->activeScheduleQuery()
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
                                ->whereIn('department', $this->departmentAliases($user->department));
                        });
                });
            }
        });

        if ($this->subjectDepartmentFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $aliases = $this->departmentAliases($this->subjectDepartmentFilter);

                $filterQuery->whereIn('department', $aliases)
                    ->orWhereHas('subject', fn (Builder $sq) => $sq->whereIn('department', $aliases));
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
            $term           = $this->subjectSearch;
            $departmentTerm = Department::normalizeCode($term);
            $query->where(function (Builder $searchQuery) use ($term, $departmentTerm) {
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

                if ($departmentTerm && $departmentTerm !== strtoupper(trim((string) $term))) {
                    $searchQuery->orWhere('department', 'like', "%{$departmentTerm}%")
                        ->orWhereHas('subject', fn (Builder $q) => $q->where('department', 'like', "%{$departmentTerm}%"));
                }
            });
        }

        $schedules = $query
            ->orderBy('start_time')
            ->get()
            ->pipe(fn (Collection $schedules) => $this->sortSchedules($schedules));

        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);

            if ($faculty) {
                $schedules = $schedules
                    ->filter(fn (Schedule $schedule) => ((int) $schedule->faculty_id === (int) $faculty->id)
                        || $this->canAssignSubject($user, $faculty, $schedule->subject)
                    )
                    ->values();
            }
        }

        return $schedules;
    }

    private function getGroupedAvailableSubjects(): Collection
    {
        return $this->getAvailableSubjects()
            ->groupBy(function (Schedule $schedule) {
                $start = Carbon::parse($schedule->start_time)->format('H:i');
                $end   = Carbon::parse($schedule->end_time)->format('H:i');

                return implode('::', [
                    $schedule->subject_id,
                    $schedule->section ?? 'none',
                    $schedule->department ?? '',
                    $schedule->major ?? '',
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

                $department = Department::normalizeCode($first->department ?? $subject?->department) ?? 'N/A';
                $major      = $first->major ?? $subject?->major ?? 'N/A';
                $year       = $first->year_level ?? $subject?->year_level ?? 'N/A';
                $section    = $first->section ?? $subject?->section ?? 'N/A';

                $assignedFacultyId = $group->first(fn (Schedule $s) => $s->faculty_id !== null)?->faculty_id;
                $assignedFaculty   = $assignedFacultyId
                    ? ($group->first(fn (Schedule $s) => $s->faculty_id !== null)?->faculty?->full_name ?? null)
                    : null;

                $isFinalized = $group->contains(fn (Schedule $s) => $s->status === Schedule::STATUS_FINALIZED);

                return [
                    'schedule_ids'     => $group->pluck('id')->all(),
                    'first_schedule_id' => $first->id,
                    'subject_code'     => $subject?->subject_code ?? 'N/A',
                    'edp_code'         => $subject?->edp_code ?? 'No EDP',
                    'description'      => $subject?->description ?? 'Untitled subject',
                    'units'            => $subject?->units ?? 0,
                    'type'             => $subject?->type ?? 'N/A',
                    'department'       => $department,
                    'major'            => $major,
                    'year'             => $year,
                    'section'          => $section,
                    'room'             => $room?->room_name ?? 'No room',
                    'days'             => $days,
                    'time'             => Carbon::parse($first->start_time)->format('h:i A')
                                         .' - '
                                         .Carbon::parse($first->end_time)->format('h:i A'),
                    'faculty_id'       => $assignedFacultyId,
                    'faculty_name'     => $assignedFaculty,
                    'is_finalized'     => $isFinalized,
                ];
            })
            ->values();
    }

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

        $schedules = $this->activeScheduleQuery()
            ->with(['subject', 'room'])
            ->findMany($scheduleIds);

        if ($schedules->isEmpty()) {
            $this->toast('error', 'Scheduled subjects not found.');

            return;
        }

        $first = $schedules->first();

        if (! $first->subject) {
            $this->toast('error', 'Subject data missing.');

            return;
        }

        if ($schedules->contains(fn (Schedule $s) => $s->status === Schedule::STATUS_FINALIZED)) {
            $this->toast('error', 'Finalized schedules cannot be changed in Faculty Loading.');

            return;
        }

        $assignedElsewhere = $schedules->first(
            fn (Schedule $s) => $s->faculty_id !== null && (int) $s->faculty_id !== (int) $faculty->id
        );

        if ($assignedElsewhere) {
            $this->toast('warning', "{$first->subject->subject_code} is already assigned to another faculty member.");

            return;
        }

        if ($schedules->every(fn (Schedule $s) => (int) $s->faculty_id === (int) $faculty->id)) {
            $this->toast('warning', "{$first->subject->subject_code} is already assigned to {$faculty->full_name}.");

            return;
        }

        if (! $this->canAssignSubject(Auth::user(), $faculty, $first->subject)) {
            $this->openAssignmentFailure($first, $faculty, [
                $this->eligibilityWarning($faculty, $first->subject, $first),
            ], $scheduleIds);
            $this->toast('error', 'Assignment blocked by faculty eligibility rules.');

            return;
        }

        $warnings       = [];
        $overloadFlagged = false;

        foreach ($schedules as $scheduleItem) {
            $dayWarnings = $this->assignmentWarnings($faculty, $scheduleItem);

            foreach ($dayWarnings as $warning) {
                if (($warning['type'] ?? '') === 'OVERLOAD') {
                    if ($overloadFlagged) {
                        continue;
                    }
                    $overloadFlagged = true;
                }

                $isDuplicate = collect($warnings)->contains(
                    fn (array $existing) => ($existing['message'] ?? '') === ($warning['message'] ?? '')
                );

                if (! $isDuplicate) {
                    $warnings[] = $warning;
                }
            }
        }

        if ($warnings !== []) {
            $this->pendingAssignmentScheduleId = $first->id;
            $this->pendingAssignmentGroupIds   = $scheduleIds;
            $this->pendingAssignmentWarnings   = $warnings;
            $this->assignmentRecommendations   = $this->buildAssignmentRecommendations($faculty, $first);
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
                $fresh = $this->activeScheduleQuery()
                    ->lockForUpdate()
                    ->findMany($scheduleIds);

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
                        'title'   => $availability['title'] ?? 'Faculty Availability Conflict',
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
            return [Department::normalizeCode($user->department)];
        }

        return $this->collectDepartmentCodes(
            Faculty::query()->select('department')->distinct()->pluck('department')
        );
    }

    private function getScheduleDepartments(): array
    {
        $scheduleDepartments = $this->activeScheduleQuery()->select('department')->distinct()->pluck('department');
        $subjectDepartments  = $this->activeSubjectQuery()->select('department')->distinct()->pluck('department');
        $departmentCodes     = Department::query()->select('code')->distinct()->pluck('code');

        return $this->collectDepartmentCodes($scheduleDepartments, $subjectDepartments, $departmentCodes);
    }

    private function collectDepartmentCodes(...$collections): array
    {
        $codes = collect($collections)
            ->flatMap(fn ($items) => collect($items))
            ->filter()
            ->map(fn ($code) => Department::normalizeCode($code))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $codes ?: ['CCS', 'CTE', 'COC', 'SHTM'];
    }

    private function getAvailableMajors()
    {
        $scheduleMajors = $this->activeScheduleQuery()->select('major')->distinct()->pluck('major');
        $subjectMajors  = $this->activeSubjectQuery()->select('major')->distinct()->pluck('major');

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
        $scheduleYears = $this->activeScheduleQuery()->select('year_level')->distinct()->pluck('year_level');
        $subjectYears  = $this->activeSubjectQuery()->select('year_level')->distinct()->pluck('year_level');

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
        $scheduleSections = $this->activeScheduleQuery()->select('section')->distinct()->pluck('section');
        $subjectSections  = $this->activeSubjectQuery()->select('section')->distinct()->pluck('section');

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
            if ($faculty->isGenEd()) {
                return ['Minor'];
            }

            return $faculty->canTeachMinorSubjects()
                ? ['Major', 'Minor']
                : ['Major'];
        }

        if ($user->role === 'associate_dean') {
            return ['Minor'];
        }

        if (in_array($user->role, ['dean', 'oic'])) {
            return ['Major', 'Minor'];
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
        $user    = Auth::user();
        $faculties = $this->getFacultyQuery()
            ->with(['schedules' => fn ($query) => $query->activeTerm()->with('subject')])
            ->orderBy('full_name', 'asc')
            ->get()
            ->each(function (Faculty $faculty) {
                $faculty->assigned_units = $faculty->schedules
                    ->pluck('subject')
                    ->filter()
                    ->unique('id')
                    ->sum('units');
            });

        $availableSubjects        = $this->getAvailableSubjects();
        $groupedAvailableSubjects = $this->getGroupedAvailableSubjects();
        $currentFaculty           = $this->selectedFaculty;
        $assignedSchedules        = $this->assignedSchedules();
        $assignedSubjects         = $this->assignedSubjects();
        $groupedAssignedSubjects  = $this->groupedAssignedSubjects();
        $facultySummary           = $this->getFacultySummary();
        $departmentSummary        = $this->getDepartmentSummary();
        $facultyDepartments       = $this->getFacultyDepartments();
        $scheduleDepartments      = $this->getScheduleDepartments();
        $majors                   = $this->getAvailableMajors();
        $yearLevels               = $this->getAvailableYearLevels();
        $sections                 = $this->getAvailableSections();
        $subjectTypes             = $this->getAvailableSubjectTypes();
        $facultyConflicts         = $this->getFacultyConflicts($assignedSchedules);
        $scheduleGroups           = $this->getScheduleGroups($assignedSchedules);
        $activeDays               = Setting::getActiveDays();

        return view('livewire.faculty-loading', [
            'faculties'                => $faculties,
            'availableSubjects'        => $availableSubjects,
            'groupedAvailableSubjects' => $groupedAvailableSubjects,
            'assignedSchedules'        => $assignedSchedules,
            'assignedSubjects'         => $assignedSubjects,
            'groupedAssignedSubjects'  => $groupedAssignedSubjects,
            'currentFaculty'           => $currentFaculty,
            'facultySummary'           => $facultySummary,
            'departmentSummary'        => $departmentSummary,
            'facultyDepartments'       => $facultyDepartments,
            'scheduleDepartments'      => $scheduleDepartments,
            'majors'                   => $majors,
            'yearLevels'               => $yearLevels,
            'sections'                 => $sections,
            'subjectTypes'             => $subjectTypes,
            'userRole'                 => $user->role,
            'activeTab'                => $this->activeTab,
            'facultyConflicts'         => $facultyConflicts,
            'scheduleGroups'           => $scheduleGroups,
            'activeDays'               => $activeDays,
            'canOverrideWarnings'      => $this->canOverrideAssignmentWarnings($user),
        ])->layout('layouts.app');
    }
}
