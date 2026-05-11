<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Department & Major Hierarchy
     */
    protected const DEPARTMENT_STRUCTURE = [
        'CCS' => ['IT', 'ACT'],
        'CTE' => ['ED'],
        'COC' => ['FB', 'LD', 'QD'],
        'SHTM' => ['HM', 'TM'],
    ];

    /**
     * Select a faculty member from the registry
     */
    public function selectFaculty($id)
    {
        $this->selectedFacultyId = $id;
        $this->subjectSearch = '';
        // Reset filters when selecting a new faculty
        $this->resetFilters();
    }

    /**
     * Reset all filter states
     */
    private function resetFilters()
    {
        $this->subjectDepartmentFilter = 'all';
        $this->subjectMajorFilter = 'all';
        $this->subjectYearLevelFilter = 'all';
        $this->subjectSectionFilter = 'all';
        $this->subjectTypeFilter = 'all';
    }

    /**
     * Get the selected faculty with their current load (Computed Property)
     */
    #[\Livewire\Attributes\Computed]
    public function selectedFaculty()
    {
        return $this->selectedFacultyId
            ? Faculty::with('subjects')->find($this->selectedFacultyId)
            : null;
    }

    /**
     * Toggle between tabs
     */
    public function toggleTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Toggle schedule modal
     */
    public function toggleScheduleModal()
    {
        $this->scheduleModalOpen = !$this->scheduleModalOpen;
    }

    /**
     * Get faculty summary data for Load Summary tab
     */
    public function getFacultySummary()
    {
        if (!$this->selectedFaculty) {
            return null;
        }

        $faculty = $this->selectedFaculty;
        $subjects = $faculty->subjects;

        $totalUnits = $subjects->sum('units') ?? 0;
        $maxUnits = $faculty->max_units ?? 21;
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

    /**
     * Assign a subject to the selected faculty
     * Includes RBAC checks and capacity validation
     */
    public function assignSubject($subjectId)
    {
        if (!$this->selectedFacultyId) {
            session()->flash('error', 'Please select a faculty member first.');
            return;
        }

        $subject = Subject::find($subjectId);
        $faculty = Faculty::withSum('subjects', 'units')->find($this->selectedFacultyId);

        if (!$subject || !$faculty) {
            session()->flash('error', 'Data error: Could not find subject or faculty.');
            return;
        }

        if ($subject->faculty_id !== null) {
            session()->flash('warning', "{$subject->subject_code} is already assigned to a faculty member.");
            return;
        }

        // RBAC & Specialization Check
        if (!$this->canAssignSubject(Auth::user(), $faculty, $subject)) {
            session()->flash('error', 'You cannot assign this type of subject to this faculty.');
            return;
        }

        // Capacity Check
        $currentUnits = (int) ($faculty->subjects_sum_units ?? 0);
        $subjectUnits = (int) $subject->units;
        $maxUnits = (int) ($faculty->max_units ?? 21);
        $newTotal = $currentUnits + $subjectUnits;

        if ($newTotal > $maxUnits) {
            session()->flash('warning', "Overload prevented: assigning {$subject->subject_code} would bring {$faculty->full_name} to {$newTotal}/{$maxUnits} units.");
            return;
        }

        // Update faculty_id so it leaves the catalog
        $subject->update(['faculty_id' => $this->selectedFacultyId]);
        
        // Clear search and notify user
        $this->subjectSearch = '';
        unset($this->selectedFaculty);
        session()->flash('success', "Assigned {$subject->subject_code} to {$faculty->full_name} successfully.");
    }

    /**
     * Remove a subject from faculty assignment
     */
    public function removeSubject($subjectId)
    {
        $subject = Subject::find($subjectId);
        if ($subject) {
            $oldCode = $subject->subject_code;
            $oldEDP = $subject->edp_code;
            $oldUnits = $subject->units;
            
            $subject->update(['faculty_id' => null]);
            unset($this->selectedFaculty);
            
            if ($this->selectedFaculty) {
                // Refresh the selected faculty
                $faculty = Faculty::with('subjects')->find($this->selectedFacultyId);
                $remainingUnits = $faculty->subjects->sum('units') ?? 0;
                session()->flash('success', "Subject {$oldCode} (EDP: {$oldEDP}, {$oldUnits}u) removed. Remaining load: {$remainingUnits}/{$faculty->max_units} units.");
            }
        }
    }

    /**
     * RBAC Permission Check for Subject Assignment
     * 
     * Rules:
     * 1. Super Users (Admin/Registrar): Always allow
     * 2. Minor Subjects: Allow any Dean/OIC (cross-department OK)
     * 3. Major Subjects: Only allow if user's department matches subject's department
     * 4. Faculty Specialization: Must match subject type or have 'Both'
     */
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

        if ($subject->type === 'Major' && $user->department === $subject->department) {
            return true;
        }

        // Otherwise, deny
        return false;
    }

    /**
     * Get faculty query with filters applied
     */
    private function getFacultyQuery()
    {
        $user = Auth::user();
        $query = Faculty::query()
            ->approved()
            ->withCount('subjects')
            ->withSum('subjects', 'units');

        // Role-based department filtering
        if (in_array($user->role, ['dean', 'oic'])) {
            // Deans/OICs can only see their own department
            $query->where('department', $user->department);
        } elseif ($user->role === 'associate_dean') {
            // Associate Deans can see all departments but only Minor specialists
            $query->whereIn('teaching_specialization', ['Minor', 'Both']);
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_id', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('employment_type', $this->statusFilter);
        }

        // Apply department filter
        if ($this->departmentFilter !== 'all') {
            $query->where('department', $this->departmentFilter);
        }

        return $query;
    }

    /**
     * Get available subjects based on faculty specialization and user RBAC
     * 
     * Filtering Logic:
     * - If faculty type is 'Minor': Show all unassigned Minor subjects (any department)
     * - If faculty type is 'Major': Show unassigned Major subjects from faculty's department
     * - If faculty type is 'Both': Show all Minor subjects + Major subjects from faculty's department
     */
    private function getAvailableSubjects()
    {
        $user = Auth::user();
        $query = Subject::query()->whereNull('faculty_id');

        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);

            if ($faculty) {
                if ($faculty->teaching_specialization === 'Minor') {
                    // All minor subjects, any department
                    $query->where('type', 'Minor');
                } elseif ($faculty->teaching_specialization === 'Major') {
                    // Only major subjects within the faculty's department
                    $query->where('type', 'Major')
                          ->where('department', $faculty->department);
                } else {
                    // "Both" — all Minors + department's Majors
                    $query->where(function ($sub) use ($faculty) {
                        $sub->where('type', 'Minor')
                            ->orWhere(function ($inner) use ($faculty) {
                                $inner->where('type', 'Major')
                                      ->where('department', $faculty->department);
                            });
                    });
                }
            }
        }

        // Advanced filters
        if ($this->subjectDepartmentFilter !== 'all') {
            $query->where('department', $this->subjectDepartmentFilter);
        }
        if ($this->subjectMajorFilter !== 'all') {
            $query->where('major', $this->subjectMajorFilter);
        }
        if ($this->subjectYearLevelFilter !== 'all') {
            $query->where('year_level', (int)$this->subjectYearLevelFilter);
        }
        if ($this->subjectSectionFilter !== 'all') {
            $query->where('section', $this->subjectSectionFilter);
        }
        if ($this->subjectTypeFilter !== 'all') {
            $query->where('type', $this->subjectTypeFilter);
        }

        // 🔍 Maintain search for EDP/subject code
        if (strlen($this->subjectSearch) > 1) {
            $term = $this->subjectSearch;
            $query->where(function ($q) use ($term) {
                $q->where('subject_code', 'like', "%{$term}%")
                  ->orWhere('edp_code', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('subject_code')->get();
    }

    /**
     * Get available departments based on user role
     */
    private function getAvailableDepartments()
    {
        $user = Auth::user();

        if (in_array($user->role, ['dean', 'oic'])) {
            // Deans/OICs see only their department
            return [$user->department];
        }

        // Admin, Registrar, Associate Dean see all
        return array_keys(self::DEPARTMENT_STRUCTURE);
    }

    /**
     * Get available majors from the department hierarchy
     */
    private function getAvailableMajors()
    {
        $majors = [];
        foreach (self::DEPARTMENT_STRUCTURE as $dept => $majorList) {
            $majors = array_merge($majors, $majorList);
        }
        return array_unique($majors);
    }

    /**
     * Get available year levels
     */
    private function getAvailableYearLevels()
    {
        return [1, 2, 3, 4];
    }

    /**
     * Get available sections from current subjects
     */
    private function getAvailableSections()
    {
        return Subject::distinct('section')->pluck('section')->sort()->values()->toArray();
    }

    /**
     * Get available subject types based on user role and selected faculty
     */
    private function getAvailableSubjectTypes()
    {
        $user = Auth::user();
        $faculty = $this->selectedFaculty;

        if ($faculty) {
            // Show types based on selected faculty's specialization
            if ($faculty->teaching_specialization === 'Minor') {
                return ['Minor'];
            } elseif ($faculty->teaching_specialization === 'Major') {
                return ['Major'];
            } else {
                // Both
                return ['Major', 'Minor'];
            }
        }

        // No faculty selected - show based on role
        if ($user->role === 'associate_dean') {
            return ['Minor'];
        }

        if (in_array($user->role, ['dean', 'oic'])) {
            return ['Major'];
        }

        // Admin & Registrar see both
        return ['Major', 'Minor'];
    }

    /**
     * Render the component with all data
     */
    public function render()
    {
        $user = Auth::user();

        // Get faculty list
        $faculties = $this->getFacultyQuery()
            ->orderBy('full_name', 'asc')
            ->get();

        // Get available subjects
        $availableSubjects = $this->getAvailableSubjects();

        // Get current faculty
        $currentFaculty = $this->selectedFaculty;

        // Get faculty summary
        $facultySummary = $this->getFacultySummary();

        // Get filter options
        $departments = $this->getAvailableDepartments();
        $majors = $this->getAvailableMajors();
        $yearLevels = $this->getAvailableYearLevels();
        $sections = $this->getAvailableSections();
        $subjectTypes = $this->getAvailableSubjectTypes();
        $employmentTypes = ['Full-Time', 'Part-Time'];

        return view('livewire.faculty-loading', [
            'faculties' => $faculties,
            'availableSubjects' => $availableSubjects,
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
