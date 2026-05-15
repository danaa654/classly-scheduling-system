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

    protected $listeners = [
        'refreshGrid' => '$refresh',
    ];

    protected const DEFAULT_MAJOR_FILTERS = ['IT', 'ACT', 'FB', 'LD', 'QD', 'ED', 'HM', 'TM'];

    private function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

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

    private function assignedSubjects(): Collection
    {
        return $this->assignedSchedules()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function getFacultySummary()
    {
        if (! $this->selectedFaculty) {
            return null;
        }

        $subjects = $this->assignedSubjects();
        $totalUnits = $subjects->sum('units') ?? 0;
        $maxUnits = $this->selectedFaculty->max_units ?? 21;
        $utilizationPercent = $maxUnits > 0 ? round(($totalUnits / $maxUnits) * 100) : 0;

        $majorSubjects = $subjects->where('type', 'Major');
        $minorSubjects = $subjects->where('type', 'Minor');
        $majorCount = $majorSubjects->count();
        $minorCount = $minorSubjects->count();
        $majorUnits = $majorSubjects->sum('units') ?? 0;
        $minorUnits = $minorSubjects->sum('units') ?? 0;

        return [
            'totalUnits' => $totalUnits,
            'maxUnits' => $maxUnits,
            'remainingUnits' => max(0, $maxUnits - $totalUnits),
            'overloadUnits' => max(0, $totalUnits - $maxUnits),
            'utilizationPercent' => $utilizationPercent,
            'majorCount' => $majorCount,
            'minorCount' => $minorCount,
            'majorUnits' => $majorUnits,
            'minorUnits' => $minorUnits,
            'averageMajorUnits' => $majorCount > 0 ? round($majorUnits / $majorCount, 2) : 0,
            'averageMinorUnits' => $minorCount > 0 ? round($minorUnits / $minorCount, 2) : 0,
            'scheduleCount' => $this->assignedSchedules()->count(),
        ];
    }

    public function assignSubject($scheduleId)
    {
        if (! $this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');

            return;
        }

        $schedule = Schedule::with(['subject', 'room'])->find($scheduleId);
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
            $this->toast('error', 'Unauthorized assignment.');

            return;
        }

        $warnings = $this->assignmentWarnings($faculty, $schedule);

        if ($warnings !== []) {
            $this->pendingAssignmentScheduleId = $schedule->id;
            $this->pendingAssignmentWarnings = $warnings;
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

        $schedule = Schedule::with(['subject', 'room'])->find($this->pendingAssignmentScheduleId);
        $faculty = Faculty::find($this->selectedFacultyId);

        if (! $schedule || ! $schedule->subject || ! $faculty) {
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
            $schedule->update([
                'faculty_id' => $faculty->id,
                'status' => Schedule::STATUS_PARTIAL,
            ]);

            $this->subjectSearch = '';
            $this->resetPendingAssignment();
            unset($this->selectedFaculty);
            $message = "{$schedule->subject->subject_code} assigned to {$faculty->full_name} successfully.";
            $this->toast('success', $overridden ? "{$message} Override recorded." : $message);
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
        $currentSubjects = $this->assignedSubjects();
        $currentUnits = $currentSubjects->sum('units');
        $subjectAlreadyAssigned = $currentSubjects->contains('id', $schedule->subject_id);
        $newTotal = $currentUnits + ($subjectAlreadyAssigned ? 0 : (int) $schedule->subject->units);
        $maxUnits = (int) ($faculty->max_units ?? 21);

        if ($newTotal > $maxUnits) {
            $warnings[] = [
                'type' => 'OVERLOAD',
                'title' => 'Faculty Load Over Capacity',
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
                'type' => $facultyConflict['conflict_type'] ?? 'FACULTY_CONFLICT',
                'title' => $facultyConflict['title'] ?? 'Faculty Schedule Conflict',
                'message' => $facultyConflict['message'] ?? 'Faculty schedule conflict detected.',
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
                    'type' => $roomConflict['conflict_type'] ?? 'ROOM_CONFLICT',
                    'title' => $roomConflict['title'] ?? 'Room Schedule Conflict',
                    'message' => $roomConflict['message'] ?? 'Room schedule conflict detected.',
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
                'type' => $availability['conflict_type'] ?? 'FACULTY_AVAILABILITY',
                'title' => $availability['title'] ?? 'Faculty Availability Conflict',
                'message' => $availability['message'] ?? 'Faculty is not available during this time.',
            ];
        }

        return $warnings;
    }

    private function resetPendingAssignment(): void
    {
        $this->conflictModalOpen = false;
        $this->pendingAssignmentScheduleId = null;
        $this->pendingAssignmentWarnings = [];
    }

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
                'status' => Schedule::STATUS_PARTIAL,
            ]);

            unset($this->selectedFaculty);
            $this->toast('success', "Subject {$oldCode} faculty assignment removed.");
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not removed.');
        }
    }

    public function submitFacultyLoading(): void
    {
        $user = Auth::user();
        $query = Schedule::query()
            ->assignable()
            ->whereNotNull('faculty_id');

        if (in_array($user->role, ['dean', 'oic']) && $user->department) {
            $query->where(function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->whereHas('subject', function (Builder $majorQuery) use ($user) {
                    $majorQuery->where('type', 'Major')
                        ->where('department', $user->department);
                })->orWhere(function (Builder $minorQuery) use ($user) {
                    $minorQuery->whereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('type', 'Minor'))
                        ->whereHas('faculty', fn (Builder $facultyQuery) => $facultyQuery->where('department', $user->department));
                });
            });
        } elseif ($user->role === 'associate_dean') {
            $query->whereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('type', 'Minor'));
        }

        $updated = $query->update(['status' => 'faculty_assigned']);

        $this->toast(
            $updated > 0 ? 'success' : 'warning',
            $updated > 0
                ? "Submitted {$updated} assigned schedule(s) to Registrar/Admin for approval."
                : 'No assigned schedules are ready to submit.'
        );
    }

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
        $roomName = $conflict->room?->room_name ?? 'Unknown Room';

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

    private function getFacultyQuery()
    {
        $user = Auth::user();
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
                $q->where('full_name', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_id', 'like', '%'.$this->search.'%');
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
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('department', $this->subjectDepartmentFilter));
            });
        }
        if ($this->subjectMajorFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('major', $this->subjectMajorFilter)
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('major', $this->subjectMajorFilter));
            });
        }
        if ($this->subjectYearLevelFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('year_level', (int) $this->subjectYearLevelFilter)
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('year_level', (int) $this->subjectYearLevelFilter));
            });
        }
        if ($this->subjectSectionFilter !== 'all') {
            $query->where(function (Builder $filterQuery) {
                $filterQuery->where('section', $this->subjectSectionFilter)
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('section', $this->subjectSectionFilter));
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
        $subjectDepartments = Subject::query()->select('department')->distinct()->pluck('department');
        $departmentCodes = Department::query()->select('code')->distinct()->pluck('code');

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
        $subjectMajors = Subject::query()->select('major')->distinct()->pluck('major');

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
        $subjectYears = Subject::query()->select('year_level')->distinct()->pluck('year_level');

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
        $subjectSections = Subject::query()->select('section')->distinct()->pluck('section');

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
        $user = Auth::user();
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
                $time = Carbon::parse($schedule->start_time)->format('H:i:s');

                return sprintf('%02d-%s-%08d', $dayIndex, $time, $schedule->id);
            })
            ->values();
    }

    private function getFacultyConflicts(Collection $assignedSchedules): Collection
    {
        $conflicts = collect();
        $schedules = $assignedSchedules->values();
        $conflictService = app(ScheduleConflictService::class);

        foreach ($schedules as $schedule) {
            if (! Setting::dayIsActive((string) $schedule->day)) {
                $conflicts->push([
                    'title' => 'Inactive Schedule Day',
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
                        'title' => $availability['title'] ?? 'Faculty Availability Conflict',
                        'message' => $availability['message'] ?? 'Faculty availability conflict detected.',
                    ]);
                }
            }
        }

        for ($i = 0; $i < $schedules->count(); $i++) {
            for ($j = $i + 1; $j < $schedules->count(); $j++) {
                $first = $schedules[$i];
                $second = $schedules[$j];

                if ($first->day !== $second->day) {
                    continue;
                }

                $overlaps = Carbon::parse($first->start_time)->lt(Carbon::parse($second->end_time))
                    && Carbon::parse($first->end_time)->gt(Carbon::parse($second->start_time));

                if ($overlaps) {
                    $conflicts->push([
                        'title' => 'Overlapping Faculty Schedule',
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
        $otherDays = $assignedSchedules
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

    public function render()
    {
        $user = Auth::user();
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
        $availableSubjects = $this->getAvailableSubjects();
        $currentFaculty = $this->selectedFaculty;
        $assignedSchedules = $this->assignedSchedules();
        $assignedSubjects = $this->assignedSubjects();
        $facultySummary = $this->getFacultySummary();
        $facultyDepartments = $this->getFacultyDepartments();
        $scheduleDepartments = $this->getScheduleDepartments();
        $majors = $this->getAvailableMajors();
        $yearLevels = $this->getAvailableYearLevels();
        $sections = $this->getAvailableSections();
        $subjectTypes = $this->getAvailableSubjectTypes();
        $employmentTypes = ['Full-Time', 'Part-Time'];
        $facultyConflicts = $this->getFacultyConflicts($assignedSchedules);
        $scheduleGroups = $this->getScheduleGroups($assignedSchedules);
        $activeDays = Setting::getActiveDays();

        return view('livewire.faculty-loading', [
            'faculties' => $faculties,
            'availableSubjects' => $availableSubjects,
            'assignedSchedules' => $assignedSchedules,
            'assignedSubjects' => $assignedSubjects,
            'currentFaculty' => $currentFaculty,
            'facultySummary' => $facultySummary,
            'facultyDepartments' => $facultyDepartments,
            'scheduleDepartments' => $scheduleDepartments,
            'majors' => $majors,
            'yearLevels' => $yearLevels,
            'sections' => $sections,
            'employmentTypes' => $employmentTypes,
            'subjectTypes' => $subjectTypes,
            'userRole' => $user->role,
            'activeTab' => $this->activeTab,
            'facultyConflicts' => $facultyConflicts,
            'scheduleGroups' => $scheduleGroups,
            'activeDays' => $activeDays,
            'canOverrideWarnings' => $this->canOverrideAssignmentWarnings($user),
        ])->layout('layouts.app');
    }
}
