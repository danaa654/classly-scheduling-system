<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
    public $activeTab = 'subjects';
    public $scheduleModalOpen = false;

    protected const DEPARTMENT_STRUCTURE = [
        'CCS' => ['IT', 'ACT'],
        'CTE' => ['ED'],
        'COC' => ['FB', 'LD', 'QD'],
        'SHTM' => ['HM', 'TM'],
    ];

    private function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    public function selectFaculty($id)
    {
        $this->selectedFacultyId = $id;
        $this->subjectSearch = '';
        $this->resetFilters();
    }

    private function resetFilters()
    {
        $this->subjectDepartmentFilter = 'all';
        $this->subjectMajorFilter = 'all';
        $this->subjectYearLevelFilter = 'all';
        $this->subjectSectionFilter = 'all';
        $this->subjectTypeFilter = 'all';
    }

    #[\Livewire\Attributes\Computed]
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

    public function toggleScheduleModal()
    {
        $this->scheduleModalOpen = !$this->scheduleModalOpen;
    }

    private function assignedSchedules(): Collection
    {
        if (!$this->selectedFacultyId) {
            return collect();
        }

        return Schedule::query()
            ->where('faculty_id', $this->selectedFacultyId)
            ->with(['subject', 'room'])
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
            ->orderBy('start_time')
            ->get();
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
        if (!$this->selectedFaculty) {
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
            'utilizationPercent' => $utilizationPercent,
            'majorCount' => $majorCount,
            'minorCount' => $minorCount,
            'majorUnits' => $majorUnits,
            'minorUnits' => $minorUnits,
            'averageMajorUnits' => $majorCount > 0 ? round($majorUnits / $majorCount, 2) : 0,
            'averageMinorUnits' => $minorCount > 0 ? round($minorUnits / $minorCount, 2) : 0,
        ];
    }

    public function assignSubject($scheduleId)
    {
        if (!$this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');
            return;
        }

        $schedule = Schedule::with(['subject', 'room'])->find($scheduleId);
        $faculty = Faculty::find($this->selectedFacultyId);

        if (!$schedule || !$schedule->subject) {
            $this->toast('error', 'Scheduled subject not found.');
            return;
        }

        if (!$faculty) {
            $this->toast('error', 'Faculty not found.');
            return;
        }

        if ($schedule->faculty_id !== null && (int) $schedule->faculty_id !== (int) $faculty->id) {
            $this->toast('warning', "{$schedule->subject->subject_code} is already assigned to another faculty member.");
            return;
        }

        if (!$this->canAssignSubject(Auth::user(), $faculty, $schedule->subject)) {
            $this->toast('error', 'Unauthorized assignment.');
            return;
        }

        $currentUnits = $this->assignedSubjects()->sum('units');
        $subjectAlreadyAssigned = $this->assignedSubjects()->contains('id', $schedule->subject_id);
        $newTotal = $currentUnits + ($subjectAlreadyAssigned ? 0 : (int) $schedule->subject->units);
        $maxUnits = (int) ($faculty->max_units ?? 21);

        if ($newTotal > $maxUnits) {
            $this->toast('warning', "Faculty overload detected. {$schedule->subject->subject_code} would bring {$faculty->full_name} to {$newTotal}/{$maxUnits} units.");
            return;
        }

        $conflict = $this->facultyConflict($faculty, $schedule);
        if ($conflict) {
            $this->toast('error', $conflict);
            return;
        }

        try {
            $schedule->update([
                'faculty_id' => $faculty->id,
                'status' => 'partial',
            ]);

            $this->subjectSearch = '';
            unset($this->selectedFaculty);
            $this->toast('success', "{$schedule->subject->subject_code} assigned to {$faculty->full_name} successfully.");
        } catch (\Throwable $exception) {
            report($exception);
            $this->toast('error', 'Database update failure. Subject was not assigned.');
        }
    }

    public function removeSubject($scheduleId)
    {
        $schedule = Schedule::with('subject')->find($scheduleId);
        if (!$schedule || !$schedule->subject) {
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
                'status' => 'partial',
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
            $query->where('department', $user->department);
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

        if (!$conflict) {
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

        if ($subject->type === 'Minor') {
            return true;
        }

        return $subject->type === 'Major' && $user->department === $subject->department;
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
            ->where('status', 'partial')
            ->whereNull('faculty_id')
            ->whereHas('subject');

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
            $query->where('department', $this->subjectDepartmentFilter);
        }
        if ($this->subjectMajorFilter !== 'all') {
            $query->where('major', $this->subjectMajorFilter);
        }
        if ($this->subjectYearLevelFilter !== 'all') {
            $query->where('year_level', (int) $this->subjectYearLevelFilter);
        }
        if ($this->subjectSectionFilter !== 'all') {
            $query->where('section', $this->subjectSectionFilter);
        }
        if ($this->subjectTypeFilter !== 'all') {
            $query->whereHas('subject', fn (Builder $q) => $q->where('type', $this->subjectTypeFilter));
        }

        if (strlen($this->subjectSearch) > 1) {
            $term = $this->subjectSearch;
            $query->whereHas('subject', function (Builder $q) use ($term) {
                $q->where('subject_code', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('edp_code', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();
    }

    private function getAvailableDepartments()
    {
        $user = Auth::user();

        if (in_array($user->role, ['dean', 'oic'])) {
            return [$user->department];
        }

        return array_keys(self::DEPARTMENT_STRUCTURE);
    }

    private function getAvailableMajors()
    {
        $majors = [];
        foreach (self::DEPARTMENT_STRUCTURE as $majorList) {
            $majors = array_merge($majors, $majorList);
        }

        return array_unique($majors);
    }

    private function getAvailableYearLevels()
    {
        return [1, 2, 3, 4];
    }

    private function getAvailableSections()
    {
        return Schedule::distinct('section')->pluck('section')->filter()->sort()->values()->toArray();
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
        $departments = $this->getAvailableDepartments();
        $majors = $this->getAvailableMajors();
        $yearLevels = $this->getAvailableYearLevels();
        $sections = $this->getAvailableSections();
        $subjectTypes = $this->getAvailableSubjectTypes();
        $employmentTypes = ['Full-Time', 'Part-Time'];

        return view('livewire.faculty-loading', [
            'faculties' => $faculties,
            'availableSubjects' => $availableSubjects,
            'assignedSchedules' => $assignedSchedules,
            'assignedSubjects' => $assignedSubjects,
            'currentFaculty' => $currentFaculty,
            'facultySummary' => $facultySummary,
            'departments' => $departments,
            'majors' => $majors,
            'yearLevels' => $yearLevels,
            'sections' => $sections,
            'employmentTypes' => $employmentTypes,
            'subjectTypes' => $subjectTypes,
            'userRole' => $user->role,
            'activeTab' => $this->activeTab,
        ])->layout('layouts.app');
    }
}
