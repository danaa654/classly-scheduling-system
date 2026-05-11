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
     * Assign a subject to the active faculty
     * Enforces RBAC and subject type restrictions
     */
    public function assignSubject($subjectId)
    {
        if (!$this->selectedFacultyId) {
            session()->flash('error', 'No faculty selected.');
            return;
        }

        $subject = Subject::find($subjectId);
        if (!$subject) {
            session()->flash('error', 'Subject not found.');
            return;
        }

        // Prevent double assignment
        if ($subject->faculty_id) {
            session()->flash('error', 'Subject already assigned.');
            return;
        }

        // Get current user and selected faculty
        $user = Auth::user();
        $faculty = Faculty::find($this->selectedFacultyId);

        if (!$faculty) {
            session()->flash('error', 'Faculty not found.');
            return;
        }

        // RBAC Enforcement: Check if user can assign this subject type to this faculty
        if (!$this->canAssignSubject($user, $faculty, $subject)) {
            session()->flash('error', 'You do not have permission to assign this subject to this faculty.');
            return;
        }

        // Check if faculty would exceed max units
        $currentUnits = $faculty->subjects->sum('units') ?? 0;
        $newTotal = $currentUnits + $subject->units;

        if ($newTotal > $faculty->max_units) {
            session()->flash('warning', "⚠️ Conflict: Assignment of {$subject->units} units would exceed {$faculty->full_name}'s maximum ({$faculty->max_units} units). Current load: {$currentUnits} units. Remaining capacity: " . ($faculty->max_units - $currentUnits) . " units.");
            return;
        }

        // Assignment successful - update subject
        $subject->update(['faculty_id' => $this->selectedFacultyId]);
        
        // Refresh the selected faculty to update total units
        $faculty = Faculty::with('subjects')->find($this->selectedFacultyId);
        $finalTotal = $faculty->subjects->sum('units') ?? 0;
        
        $this->subjectSearch = '';
        $this->resetFilters();
        
        // Flash success with details
        session()->flash('success', "✓ Subject {$subject->subject_code} (EDP: {$subject->edp_code}) assigned to {$faculty->full_name}. Total load: {$finalTotal}/{$faculty->max_units} units.");
    }

    /**
     * Remove a subject from the faculty's load
     */
    public function removeSubject($subjectId)
    {
        $subject = Subject::find($subjectId);
        if ($subject) {
            $oldCode = $subject->subject_code;
            $oldEDP = $subject->edp_code;
            $oldUnits = $subject->units;
            
            $subject->update(['faculty_id' => null]);
            
            if ($this->selectedFaculty) {
                // Refresh the selected faculty
                $faculty = Faculty::with('subjects')->find($this->selectedFacultyId);
                $remainingUnits = $faculty->subjects->sum('units') ?? 0;
                session()->flash('success', "✓ Subject {$oldCode} (EDP: {$oldEDP}, {$oldUnits}u) removed. Remaining load: {$remainingUnits}/{$faculty->max_units} units.");
            }
        }
    }

    /**
     * Check if the current user can assign a subject to a faculty member
     * Implements RBAC logic based on role and subject type
     */
    private function canAssignSubject($user, Faculty $faculty, Subject $subject): bool
    {
        // Admin & Registrar: full access
        if (in_array($user->role, ['admin', 'registrar'])) {
            return true;
        }

        // Associate Dean: can only manage Minor subjects
        if ($user->role === 'associate_dean') {
            // Check if faculty can teach Minor
            if (!in_array($faculty->teaching_specialization, ['Minor', 'Both'])) {
                return false;
            }
            // Check if subject is Minor
            return $subject->type === 'Minor';
        }

        // Dean & OIC: restricted to their department and Major subjects
        if (in_array($user->role, ['dean', 'oic'])) {
            // Check department restriction
            if ($faculty->department !== $user->department) {
                return false;
            }
            // Check if faculty can teach Major
            if (!in_array($faculty->teaching_specialization, ['Major', 'Both'])) {
                return false;
            }
            // Check if subject is Major
            return $subject->type === 'Major';
        }

        return false;
    }

    /**
     * Get filtered faculty list based on user role
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
     * Get available subjects based on:
     * 1. User role permissions
     * 2. Selected faculty's teaching specialization (CROSS-DEPARTMENT LOGIC)
     * 3. Advanced filters (Department, Major, Year Level, Section, Type)
     * 
     * INTELLIGENT CROSS-DEPARTMENT LOGIC:
     * - Minor specialists can see Minor subjects from ANY department
     * - Major specialists see only Major subjects from their home department
     * - Both specialists see all Minor + Major from home department
     */
    private function getAvailableSubjects()
    {
        $user = Auth::user();
        $query = Subject::query()->whereNull('faculty_id');

        // ============================================================
        // CROSS-DEPARTMENT ASSIGNMENT LOGIC
        // ============================================================
        
        if ($this->selectedFacultyId) {
            // Faculty is selected - filter based on their qualifications
            $faculty = Faculty::find($this->selectedFacultyId);
            
            if ($faculty) {
                if ($faculty->teaching_specialization === 'Minor') {
                    // Minor specialist: Can teach ANY Minor subjects regardless of department
                    $query->where('type', 'Minor');
                } elseif ($faculty->teaching_specialization === 'Major') {
                    // Major specialist: Only Major subjects from their home department
                    $query->where('type', 'Major')
                          ->where('department', $faculty->department);
                } elseif ($faculty->teaching_specialization === 'Both') {
                    // Can teach both: All Minor subjects + Major from their department
                    $query->where(function ($q) use ($faculty) {
                        $q->where('type', 'Minor')
                          ->orWhere(function ($subQ) use ($faculty) {
                              $subQ->where('type', 'Major')
                                   ->where('department', $faculty->department);
                          });
                    });
                }
            }
        } else {
            // No faculty selected - show based on user role
            if ($user->role === 'associate_dean') {
                // Associate Deans can only see Minor subjects
                $query->where('type', 'Minor');
            } elseif (in_array($user->role, ['dean', 'oic'])) {
                // Deans/OICs can only see Major subjects in their department
                $query->where('type', 'Major')
                      ->where('department', $user->department);
            }
            // Admin/Registrar see all (unless filtered below)
        }

        // ============================================================
        // ADVANCED FILTERING (Department, Major, Year Level, Section, Type)
        // ============================================================
        
        // Department filter (only for admin/registrar when no faculty selected)
        if (!$this->selectedFacultyId && ($user->role === 'admin' || $user->role === 'registrar')) {
            if ($this->subjectDepartmentFilter !== 'all') {
                $query->where('department', $this->subjectDepartmentFilter);
            }
        }

        // Major filter (only for admin/registrar when no faculty selected)
        if (!$this->selectedFacultyId && ($user->role === 'admin' || $user->role === 'registrar')) {
            if ($this->subjectMajorFilter !== 'all') {
                $query->where('major', $this->subjectMajorFilter);
            }
        }

        // Year Level filter (applies to all)
        if ($this->subjectYearLevelFilter !== 'all') {
            $query->where('year_level', (int)$this->subjectYearLevelFilter);
        }

        // Section filter (applies to all)
        if ($this->subjectSectionFilter !== 'all') {
            $query->where('section', $this->subjectSectionFilter);
        }

        // Subject Type filter (only for admin/registrar when no faculty selected)
        if (!$this->selectedFacultyId && ($user->role === 'admin' || $user->role === 'registrar')) {
            if ($this->subjectTypeFilter !== 'all') {
                $query->where('type', $this->subjectTypeFilter);
            }
        }

        // Apply search filters (EDP Code, Subject Code, Description)
        if (strlen($this->subjectSearch) > 1) {
            $query->where(function ($q) {
                $q->where('subject_code', 'like', '%' . $this->subjectSearch . '%')
                  ->orWhere('description', 'like', '%' . $this->subjectSearch . '%')
                  ->orWhere('edp_code', 'like', '%' . $this->subjectSearch . '%');
            });
        }

        return $query->orderBy('subject_code', 'asc')->get();
    }

    /**
     * Get available departments for dropdown filters
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
