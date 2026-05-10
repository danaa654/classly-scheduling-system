<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Faculty;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class FacultyLoading extends Component
{
    // Search & Filter Properties
    public $search = '';
    public $subjectSearch = '';
    public $selectedFacultyId = null;
    public $departmentFilter = 'all';
    public $statusFilter = 'all';
    public $subjectDepartmentFilter = 'all';
    public $subjectTypeFilter = 'all';

    // View State Properties
    public $activeTab = 'subjects'; // 'subjects', 'schedule', 'summary'
    public $showScheduleModal = false;
    public $showSummaryModal = false;

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
        $this->activeTab = 'subjects';
        $this->showScheduleModal = false;
        $this->showSummaryModal = false;
    }

    /**
     * Get the selected faculty with their current load (Computed Property)
     */
    #[On('selectFaculty')]
    public function getSelectedFacultyProperty()
    {
        return $this->selectedFacultyId
            ? Faculty::with('subjects')->find($this->selectedFacultyId)
            : null;
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
            session()->flash('error', 'Subject already assigned to another faculty.');
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
            session()->flash('error', 'You do not have permission to assign this subject type to this faculty.');
            return;
        }

        // Check if faculty would exceed max units
        $currentUnits = $faculty->subjects->sum('units') ?? 0;
        $newTotal = $currentUnits + $subject->units;

        if ($newTotal > $faculty->max_units) {
            session()->flash('warning', "Conflict: Assignment of {$subject->units} units would exceed {$faculty->full_name}'s maximum of {$faculty->max_units} units. Current: {$currentUnits}, Total would be: {$newTotal}.");
            return;
        }

        // Assignment successful
        $subject->update(['faculty_id' => $this->selectedFacultyId]);
        $this->subjectSearch = '';
        session()->flash('success', "✓ Subject {$subject->subject_code} ({$subject->edp_code}) assigned to {$faculty->full_name}.");
    }

    /**
     * Remove a subject from the faculty's load
     */
    public function removeSubject($subjectId)
    {
        $subject = Subject::find($subjectId);
        if ($subject) {
            $subjectCode = $subject->subject_code;
            $edpCode = $subject->edp_code;
            $subject->update(['faculty_id' => null]);
            session()->flash('success', "✓ Subject {$subjectCode} ({$edpCode}) removed successfully.");
        }
    }

    /**
     * Toggle Schedule Modal
     */
    public function toggleScheduleModal()
    {
        $this->showScheduleModal = !$this->showScheduleModal;
        $this->activeTab = $this->showScheduleModal ? 'schedule' : 'subjects';
    }

    /**
     * Toggle Summary Modal
     */
    public function toggleSummaryModal()
    {
        $this->showSummaryModal = !$this->showSummaryModal;
        $this->activeTab = $this->showSummaryModal ? 'summary' : 'subjects';
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
     * Get available subjects based on user role and selected faculty
     * Auto-filters based on faculty specialization when faculty is selected
     */
    private function getAvailableSubjects()
    {
        $user = Auth::user();
        $query = Subject::query()
            ->whereNull('faculty_id');

        // If faculty is selected, filter based on their specialization
        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);
            if ($faculty) {
                // Faculty-driven filtering
                $facultyTypes = [];
                if (in_array($faculty->teaching_specialization, ['Major', 'Both'])) {
                    $facultyTypes[] = 'Major';
                }
                if (in_array($faculty->teaching_specialization, ['Minor', 'Both'])) {
                    $facultyTypes[] = 'Minor';
                }

                if (!empty($facultyTypes)) {
                    $query->whereIn('type', $facultyTypes);
                }

                // Restrict to faculty's department
                $query->where('department', $faculty->department);
            }
        } else {
            // No faculty selected: use role-based initial filtering
            if ($user->role === 'associate_dean') {
                // Associate Deans can only see Minor subjects
                $query->where('type', 'Minor');
            } elseif (in_array($user->role, ['dean', 'oic'])) {
                // Deans/OICs can only see Major subjects in their department
                $query->where('type', 'Major');
                $query->where('department', $user->department);
            }
        }

        // Apply additional department filter if admin/registrar and if provided
        if ($user->role === 'admin' || $user->role === 'registrar') {
            if ($this->subjectDepartmentFilter !== 'all' && !$this->selectedFacultyId) {
                $query->where('department', $this->subjectDepartmentFilter);
            }
        }

        // Apply subject type filter only if admin/registrar and no faculty selected
        if (($user->role === 'admin' || $user->role === 'registrar') && !$this->selectedFacultyId) {
            if ($this->subjectTypeFilter !== 'all') {
                $query->where('type', $this->subjectTypeFilter);
            }
        }

        // Apply search filters
        if (strlen($this->subjectSearch) > 1) {
            $query->where(function ($q) {
                $q->where('subject_code', 'like', '%' . $this->subjectSearch . '%')
                  ->orWhere('description', 'like', '%' . $this->subjectSearch . '%')
                  ->orWhere('edp_code', 'like', '%' . $this->subjectSearch . '%');
            });
        }

        // Return all available subjects (no limit for scrollable catalog)
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
     * Get available subject types based on user role
     */
    private function getAvailableSubjectTypes()
    {
        $user = Auth::user();

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
     * Get summary statistics for selected faculty
     */
    private function getFacultySummary()
    {
        if (!$this->selectedFacultyId) {
            return null;
        }

        $faculty = Faculty::with('subjects')->find($this->selectedFacultyId);
        if (!$faculty) {
            return null;
        }

        $subjects = $faculty->subjects;
        $totalUnits = $subjects->sum('units') ?? 0;

        $lectureUnits = $subjects->where('type', 'Major')->sum('units') ?? 0;
        $labUnits = $subjects->where('type', 'Minor')->sum('units') ?? 0;

        $lectureCount = $subjects->where('type', 'Major')->count() ?? 0;
        $labCount = $subjects->where('type', 'Minor')->count() ?? 0;

        return [
            'total_units' => $totalUnits,
            'lecture_units' => $lectureUnits,
            'lab_units' => $labUnits,
            'lecture_count' => $lectureCount,
            'lab_count' => $labCount,
            'max_units' => $faculty->max_units,
            'remaining_units' => max(0, $faculty->max_units - $totalUnits),
            'utilization_percent' => min(($totalUnits / $faculty->max_units) * 100, 100),
        ];
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
        $currentFaculty = $this->getSelectedFacultyProperty();

        // Get summary
        $facultySummary = $this->getFacultySummary();

        // Get filter options
        $departments = $this->getAvailableDepartments();
        $subjectTypes = $this->getAvailableSubjectTypes();
        $employmentTypes = ['Full-Time', 'Part-Time', 'Contractual'];

        return view('livewire.faculty-loading', [
            'faculties' => $faculties,
            'availableSubjects' => $availableSubjects,
            'currentFaculty' => $currentFaculty,
            'facultySummary' => $facultySummary,
            'departments' => $departments,
            'employmentTypes' => $employmentTypes,
            'subjectTypes' => $subjectTypes,
            'userRole' => $user->role,
        ])->layout('layouts.app');
    }
}
