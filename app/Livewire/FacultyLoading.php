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

    public array $pendingRawSubjectData = [];

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

    private function resetFilters(): void
    {
        $this->subjectDepartmentFilter = 'all';
        $this->subjectMajorFilter      = 'all';
        $this->subjectYearLevelFilter  = 'all';
        $this->subjectSectionFilter    = 'all';
        $this->subjectTypeFilter       = 'all';
        $this->showUnassignedOnly      = false;
    }

    /**
     * Livewire lifecycle hook — fires whenever $subjectDepartmentFilter changes.
     *
     * Resets the Major dropdown to "all" so a stale value (e.g. 'IT') can never
     * combine with the new department (e.g. 'CTE') to produce an impossible query
     * like  dept=CTE AND major=IT  → 0 rows → empty panel.
     */
    public function updatedSubjectDepartmentFilter(): void
    {
        $this->subjectMajorFilter = 'all';
    }

    #[Computed]
    public function selectedFaculty()
    {
        return $this->selectedFacultyId
            ? Faculty::with(['schedules' => fn ($query) => $query->activeTerm()->with(['subject.preferredRoom', 'room'])])
                ->find($this->selectedFacultyId)
            : null;
    }

    public function toggleTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function openAssignmentPanel(?int $scheduleId = null, ?int $facultyId = null): void
{
    if ($scheduleId) {
        // Load the schedule to get the CURRENT faculty assignment
        $schedule = Schedule::activeTerm()->find($scheduleId);
        
        if ($schedule) {
            // Pre-select the CURRENT faculty, don't pick a random one
            $this->selectedFacultyId = $schedule->faculty_id;
            $this->pendingAssignmentScheduleId = $scheduleId;
            // ... open modal
        }
    } elseif ($facultyId) {
        // Coming from the "Open Assignment Panel" button
        $this->selectedFacultyId = $facultyId;
    }
    
    $this->scheduleModalOpen = true;
}

    public function initializeRawSubjectAssignment(int $subjectId, string $section = 'A', ?int $facultyId = null): void
{
    $subject = Subject::activeTerm()->find($subjectId);
    
    if (!$subject) {
        $this->toast('error', 'Subject no longer exists.');
        return;
    }
    
    // Store raw subject data for later schedule creation
    $this->pendingRawSubjectData = [
        'subject_id' => $subjectId,
        'section' => trim($section),
        'year_level' => $subject->year_level ?? 1,
        'department' => $subject->department,
        'major' => $subject->major,
    ];
    
    // Pre-select faculty if provided
    if ($facultyId !== null) {
        $this->selectedFacultyId = $facultyId;
    }
    
    if (!$this->selectedFacultyId) {
        $this->toast('error', 'Please select a faculty member first.');
        $this->pendingRawSubjectData = [];
        return;
    }
    
    // Validate workload before opening confirmation modal
    $faculty = Faculty::find($this->selectedFacultyId);
    if (!$faculty) {
        $this->toast('error', 'Faculty member not found.');
        return;
    }
    
    // Check if subject is already assigned to this faculty
    $existing = Schedule::activeTerm()
        ->where('subject_id', $subjectId)
        ->where('faculty_id', $faculty->id)
        ->first();
    
    if ($existing) {
        $this->toast('warning', "{$subject->subject_code} is already assigned to {$faculty->full_name}.");
        $this->pendingRawSubjectData = [];
        return;
    }
    
    // Validate workload
    $workloadValidation = $this->validatePreAssignmentWorkload($faculty, $subject);
    
    if (!$workloadValidation['valid']) {
        $this->pendingAssignmentWarnings = [[
            'type' => 'OVERLOAD',
            'title' => 'Faculty Load Limit Exceeded',
            'message' => "{$subject->subject_code} ({$subject->units} units) would exceed {$faculty->full_name}'s maximum load of {$workloadValidation['max_units']} units.",
            'details' => sprintf(
                "Current load: %d units\nSubject units: %d units\nNew total: %d units\nOverage: %d units",
                $workloadValidation['current_units'],
                $workloadValidation['subject_units'],
                $workloadValidation['new_total'],
                $workloadValidation['overload']
            ),
            'overridable' => $this->canOverrideAssignmentWarnings(Auth::user()),
        ]];
        
        $this->conflictModalOpen = true;
        $this->toast(
            $this->canOverrideAssignmentWarnings(Auth::user()) ? 'warning' : 'error',
            'Assignment needs workload review.'
        );
        return;
    }
    
    // No warnings - proceed directly with assignment
    $this->confirmRawSubjectAssignment();
}

/**
 * Validate faculty workload before assignment
 * Returns detailed metrics for UI and validation
 */
private function validatePreAssignmentWorkload(Faculty $faculty, Subject $subject): array
{
    $currentUnits = $faculty->calculateTotalAssignedUnits();
    $subjectUnits = (int) ($subject->units ?? 0);
    $maxUnits = (int) ($faculty->max_units ?? 21);
    $newTotal = $currentUnits + $subjectUnits;
    
    return [
        'valid' => $newTotal <= $maxUnits,
        'current_units' => $currentUnits,
        'subject_units' => $subjectUnits,
        'new_total' => $newTotal,
        'max_units' => $maxUnits,
        'overload' => max(0, $newTotal - $maxUnits),
        'remaining' => max(0, $maxUnits - $newTotal),
    ];
}

    /**
     * Approve raw subject assignment from conflict modal (with override capability)
     * Called when user confirms modal after workload warning
     * 
     * This is the critical BRIDGE between the conflict modal and the execution layer.
     * Without this method, the modal can warn but has no way to approve the assignment.
     */
    public function approveRawSubjectAssignmentOverride(): void
    {
        $user = Auth::user();

        // GUARD 1: Authorization - only override-capable roles can proceed
        if (!$this->canOverrideAssignmentWarnings($user)) {
            $this->toast('error', 'You are not authorized to override assignment warnings.');
            return;
        }

        // GUARD 2: Data validation - must have pending raw subject data
        if (empty($this->pendingRawSubjectData) || !$this->selectedFacultyId) {
            $this->resetPendingAssignment();
            $this->toast('error', 'No pending assignment found.');
            return;
        }

        // GUARD 3: Subject must still exist (in case it was archived)
        $subject = Subject::activeTerm()->find($this->pendingRawSubjectData['subject_id'] ?? null);
        if (!$subject) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Subject no longer exists.');
            return;
        }

        // GUARD 4: Faculty must still exist
        $faculty = Faculty::find($this->selectedFacultyId);
        if (!$faculty) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Faculty member no longer exists.');
            return;
        }

        // GUARD 5: Authorization check - can this user assign to this faculty?
        if (!$this->canAssignSubject($user, $faculty, $subject)) {
            $this->resetPendingAssignment();
            $this->toast('error', 'Unauthorized assignment.');
            return;
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // ALL GUARDS PASSED - Execute assignment with override flag = true
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $this->confirmRawSubjectAssignment(overridden: true);
    }

    /**
 * Confirm and persist raw subject assignment
 * Creates new schedule record with NULL spacetime, status = 'faculty_locked'
 * This bypasses override logic since we're not checking conflicts on unscheduled subjects
 */
public function confirmRawSubjectAssignment(bool $overridden = false): void
{
    // Validate pending data
    if (empty($this->pendingRawSubjectData) || !$this->selectedFacultyId) {
        $this->toast('error', 'Assignment data is missing. Please try again.');
        return;
    }
    
    $faculty = Faculty::find($this->selectedFacultyId);
    $subject = Subject::activeTerm()->find($this->pendingRawSubjectData['subject_id'] ?? null);
    
    if (!$faculty || !$subject) {
        $this->toast('error', 'Faculty or subject no longer exists.');
        $this->resetPendingAssignment();
        return;
    }
    
    try {
        DB::transaction(function () use ($faculty, $subject, $overridden) {
            $period = Setting::getAcademicPeriod();
            
            // Step 1: Try to find existing schedule for this subject-section combo
            $existingSchedule = Schedule::activeTerm()
                ->where('subject_id', $subject->id)
                ->where('section', $this->pendingRawSubjectData['section'])
                ->lockForUpdate()
                ->first();
            
            if ($existingSchedule) {
                // Step 2a: Use existing schedule (shouldn't happen, but handle gracefully)
                if ($existingSchedule->faculty_id !== null && (int) $existingSchedule->faculty_id !== (int) $faculty->id) {
                    throw new \RuntimeException(
                        "{$subject->subject_code} is already assigned to another faculty."
                    );
                }
                
                $existingSchedule->update([
                    'faculty_id' => $faculty->id,
                    'status' => Schedule::STATUS_FACULTY_LOCKED,
                ]);
                
                $scheduleId = $existingSchedule->id;
            } else {
                // Step 2b: Create NEW schedule record with NULL spacetime
                $newSchedule = Schedule::create([
                    'subject_id' => $subject->id,
                    'faculty_id' => $faculty->id,
                    'section' => $this->pendingRawSubjectData['section'],
                    'department' => $this->pendingRawSubjectData['department'],
                    'major' => $this->pendingRawSubjectData['major'],
                    'year_level' => $this->pendingRawSubjectData['year_level'],
                    
                    // ← CRITICAL: Keep these NULL for unscheduled subjects
                    'day' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'room_id' => null,
                    
                    // Status indicates faculty assigned but spacetime pending
                    'status' => Schedule::STATUS_FACULTY_LOCKED,
                    
                    // Academic period (auto-populated by Schedule::booted hook)
                    'semester' => $period['semester'],
                    'school_year' => $period['school_year'],
                    'academic_year' => $period['school_year'],
                    'workspace_key' => Setting::workspaceKey($period['school_year'], $period['semester']),
                    'edp_code' => $subject->edp_code,
                ]);
                
                $scheduleId = $newSchedule->id;
            }
            
            // Step 3: Log the action
            if (method_exists('App\Models\FacultyLog', 'create')) {
                \App\Models\FacultyLog::create([
                    'faculty_id' => $faculty->id,
                    'action' => 'raw_subject_assignment',
                    'subject_code' => $subject->subject_code,
                    'description' => "Faculty pre-assigned (unscheduled) to {$subject->subject_code} (raw assignment)",
                    'performed_by' => Auth::id(),
                ]);
            }
        });
        
        // Step 4: Refresh component state OUTSIDE transaction
        unset($this->selectedFaculty);
        $this->resetPendingAssignment();
        
        $message = "{$subject->subject_code} assigned to {$faculty->full_name} (awaiting auto-generation).";
        $this->toast(
            'success',
            $overridden ? "{$message} Override recorded." : $message
        );
    } catch (\RuntimeException $e) {
        $this->toast('warning', $e->getMessage());
    } catch (\Throwable $e) {
        report($e);
        $this->toast('error', 'Could not create schedule. Subject was not assigned.');
        \Log::error('Raw subject assignment failed', [
            'subject_id' => $this->pendingRawSubjectData['subject_id'] ?? null,
            'faculty_id' => $this->selectedFacultyId,
            'error' => $e->getMessage(),
        ]);
    }
}

    public function closeAssignmentPanel(): void
    {
        $this->scheduleModalOpen = false;
        $this->conflictModalOpen = false;  // ← CHANGE 3: Add this line
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
            ->with(['subject.preferredRoom', 'room'])
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
 
            // ================================================================
            // CRITICAL FIX: Only include schedules with COMPLETE spacetime
            // ================================================================
            $fullyScheduledRecords = $group->filter(fn (Schedule $s) => 
                filled($s->day) && 
                filled($s->start_time) && 
                filled($s->end_time) &&
                filled($s->room_id)
            );
 
            // Check if ANY schedule in this group is a pre-assignment (faculty assigned, no spacetime)
            $preAssignedRecords = $group->filter(fn (Schedule $s) =>
                filled($s->faculty_id) && (
                    blank($s->day) || 
                    blank($s->start_time) || 
                    blank($s->end_time) || 
                    blank($s->room_id)
                )
            );
 
            $isPreAssigned = $preAssignedRecords->isNotEmpty();
            $hasFullyScheduled = $fullyScheduledRecords->isNotEmpty();
 
            // ================================================================
            // Build schedule display string
            // ================================================================
            if ($hasFullyScheduled) {
                // Use only the valid schedules for display
                $scheduleLines = $fullyScheduledRecords
                    ->map(fn (Schedule $s) => trim(
                        ($s->day ?? 'TBA')
                        .' / '
                        .(\Carbon\Carbon::parse($s->start_time)->format('h:i A') ?? '--:-- --')
                        .' - '
                        .(\Carbon\Carbon::parse($s->end_time)->format('h:i A') ?? '--:-- --')
                    ))
                    ->unique()
                    ->values()
                    ->implode('<br>');
            } elseif ($isPreAssigned) {
                // This is a pre-assigned subject awaiting auto-generation
                // Use a marker string that the blade template will recognize
                $scheduleLines = '<span class="text-amber-500 font-medium italic">Unscheduled Placeholder</span>';
            } else {
                // No valid schedules at all
                $scheduleLines = '<span class="text-slate-500 italic">Not Assigned</span>';
            }
 
            $department = Department::normalizeCode($first->department ?? $subject?->department) ?? 'N/A';
            $major = $first->major ?? $subject?->major ?? 'N/A';
            $year = $first->year_level ?? $subject?->year_level ?? '?';
            $section = $first->section ?? $subject?->section ?? 'N/A';
 
            // Build room display for pre-assigned / retrieved-without-time subjects.
            // Priority: (1) room assigned on the schedule record, (2) subject's preferred_room_id.
            // Never fall back to the "No room → TBA" path when the schedule carries a room.
            $displayRoom = $room?->room_name ?? 'No room';
            $preferredRoomName = null;
            if ($isPreAssigned && !$hasFullyScheduled) {
                if ($room) {
                    // Schedule already has a room_id (e.g. retrieved with Subject+Faculty+Room mode).
                    $displayRoom       = $room->room_name;
                    $preferredRoomName = $room->room_name;
                } else {
                    // No room on the schedule — check the subject's preferred_room_id (Manage Rooms pin).
                    $preferredRoom = $first->subject?->preferredRoom;
                    if ($preferredRoom) {
                        $preferredRoomName = $preferredRoom->room_name;
                        $displayRoom       = $preferredRoom->room_name;
                    } else {
                        $displayRoom = 'No room'; // Will be converted to "TBA" in blade
                    }
                }
            }
 
            return [
                'subject_code'      => $subject?->subject_code ?? 'N/A',
                'edp_code'          => $subject?->edp_code ?? 'No EDP',
                'description'       => $subject?->description ?? 'Untitled subject',
                'group'             => "{$department} / {$major} / Y{$year} / {$section}",
                'room'              => $displayRoom,
                'preferred_room_name' => $preferredRoomName,
                'schedule'          => $scheduleLines,
                'units'             => $subject?->units ?? 0,
                'type'              => $subject?->type ?? 'N/A',
                'schedule_ids'      => $group->pluck('id')->all(),
                'first_schedule_id' => $first->id,
                'is_pre_assigned'   => $isPreAssigned,           // Optional: for extra safety
                'is_fully_scheduled'=> $hasFullyScheduled,       // Optional: for extra safety
            ];
        })
        ->values();
}
 
/**
 * OPTIONAL: If you want to add a helper method for easier blade-side checks:
 */
public function isScheduleUnscheduled(string $scheduleHtml): bool
{
    return str_contains($scheduleHtml, 'Unscheduled Placeholder');
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

        // Resolve the active department scope for the filter
        $activeDepartment = null;

        if ($this->departmentFilter !== 'all') {
            $activeDepartment = $this->departmentFilter;
        } elseif (in_array($user->role, ['dean', 'oic'], true) && $user->department) {
            $activeDepartment = Department::normalizeCode($user->department);
        }

        // ════════════════════════════════════════════════════════════════════════════════════
        // FACULTY-FIRST: Query SUBJECTS table with LEFT JOIN to SCHEDULES
        // This counts ALL subjects in the system, not just those with schedule rows
        // ════════════════════════════════════════════════════════════════════════════════════
        
        // Get active period
        $period = Setting::getAcademicPeriod();
        $semester = Setting::normalizeSemester($period['semester']);
        $schoolYear = $period['school_year'];
        $workspaceKey = Setting::workspaceKey($schoolYear, $semester);

        // Base query: All subjects in active term
        $baseQuery = Subject::query()
            ->where('subjects.semester', $semester)
            ->where(function (Builder $q) use ($schoolYear, $workspaceKey) {
                $q->where('subjects.workspace_key', $workspaceKey)
                    ->orWhere('subjects.school_year', $schoolYear)
                    ->orWhere('subjects.academic_year', $schoolYear);
            })
            ->where('subjects.is_archived', 0)
            ->leftJoin('schedules', 'subjects.id', '=', 'schedules.subject_id')
            ->select('subjects.id', 'subjects.*', 'schedules.faculty_id')
            ->distinct();

        // ────────────────────────────────────────────────────────────────────────────────────
        // Apply role-based visibility
        // ────────────────────────────────────────────────────────────────────────────────────
        if ($user->role === 'associate_dean') {
            $baseQuery->where('subjects.type', 'Minor');
        } elseif (in_array($user->role, ['dean', 'oic'], true)) {
            $baseQuery->where(function (Builder $visibility) use ($user) {
                $visibility->where('subjects.type', 'Minor')
                    ->orWhere(function (Builder $majorQuery) use ($user) {
                        $majorQuery->where('subjects.type', 'Major')
                            ->whereIn('subjects.department', $this->departmentAliases($user->department));
                    });
            });
        }

        // ────────────────────────────────────────────────────────────────────────────────────
        // Apply department filter
        // ────────────────────────────────────────────────────────────────────────────────────
        if ($activeDepartment) {
            $aliases = $this->departmentAliases($activeDepartment);
            $baseQuery->where(function (Builder $q) use ($aliases) {
                $q->whereIn('subjects.department', $aliases)
                    ->orWhereIn('schedules.department', $aliases);
            });
        }

        // ────────────────────────────────────────────────────────────────────────────────────
        // Count metrics
        // ────────────────────────────────────────────────────────────────────────────────────
        
        // Total subjects (all)
        $allResults = (clone $baseQuery)->get();
        $totalSubjects = $allResults->pluck('id')->unique()->count();

        // Assigned subjects (has faculty_id in any schedule)
        $assignedSubjects = $allResults
            ->filter(fn ($row) => $row->faculty_id !== null)
            ->pluck('id')
            ->unique()
            ->count();

        // Left (no faculty assigned)
        $subjectsLeft = $totalSubjects - $assignedSubjects;

        // Faculty Processed (unique faculty assigned to these subjects)
        $assignedFacultyIds = $allResults
            ->filter(fn ($row) => $row->faculty_id !== null)
            ->pluck('faculty_id')
            ->filter()
            ->unique()
            ->values();

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

    public function assignUnscheduledSubject(int $subjectId, ?string $section = null): void
    {
        if (!$this->selectedFacultyId) {
            $this->toast('error', 'Please select a faculty member first.');
            return;
        }

        $faculty = Faculty::find($this->selectedFacultyId);
        if (!$faculty) {
            $this->toast('error', 'Faculty not found.');
            return;
        }

        $subject = Subject::query()
            ->where('subjects.semester', Setting::normalizeSemester(Setting::getAcademicPeriod()['semester']))
            ->find($subjectId);

        if (!$subject) {
            $this->toast('error', 'Subject not found.');
            return;
        }

        // Validate faculty eligibility BEFORE attempting to find/create schedule
        $validation = $this->validatePreAssignmentComplete($faculty, $subject);
        
        if (!$validation['eligible']) {
            $this->openAssignmentFailure(null, $faculty, $validation['errors']);
            $this->toast('error', 'Assignment blocked by faculty eligibility rules.');
            return;
        }

        // Find or create a schedule row for this unscheduled subject
        $schedule = $this->findOrCreateScheduleForUnscheduledSubject($subject, $section);

       if (!$schedule) {
    $lastError = \Log::channel('single')->getHandlers()[0]->getLevel();
    $this->toast('error', 'Could not create schedule: Check logs. ' . ($schedule ? '' : 'Schedule null'));
    return;
}

        // Check for feasibility warnings (no time conflicts since spacetime is NULL)
        if (!$validation['feasible']) {
            $this->pendingAssignmentScheduleId = $schedule->id;
            $this->pendingAssignmentWarnings = $validation['warnings'];
            $this->assignmentRecommendations = [];
            $this->conflictModalOpen = true;
            
            $this->toast(
                $this->canOverrideAssignmentWarnings(Auth::user()) ? 'warning' : 'error',
                'Assignment needs feasibility review (load capacity check).'
            );
            return;
        }

        // All good - persist the pre-assignment
        $this->persistPreAssignment($schedule, $faculty);
    }

    private function findOrCreateScheduleForUnscheduledSubject(Subject $subject, ?string $section = null): ?Schedule
{
    $period = Setting::getAcademicPeriod();
    $semester = Setting::normalizeSemester($period['semester']);
    $schoolYear = $period['school_year'];
    $workspaceKey = Setting::workspaceKey($schoolYear, $semester);
    
    // Try to find existing unscheduled row
    $existing = Schedule::query()
        ->where('subject_id', $subject->id)
        ->where('semester', $semester)
        ->where('school_year', $schoolYear)
        ->where('workspace_key', $workspaceKey)
        ->where(function (Builder $q) {
            $q->whereNull('day')
                ->orWhereNull('start_time')
                ->orWhereNull('end_time')
                ->orWhereNull('room_id');
        })
        ->where(function (Builder $q) {
            $q->where('status', Schedule::STATUS_FACULTY_LOCKED)
                ->orWhere('status', Schedule::STATUS_PENDING_GENERATION);
        })
        ->first();

    if ($existing && !$existing->faculty_id) {
        return $existing;
    }

    // Create new one
    $sectionValue = $section ?? $subject->section ?? 'A';

    try {
        $newSchedule = Schedule::create([
            'subject_id'    => $subject->id,
            'faculty_id'    => null,
            'room_id'       => null,
            'day'           => null,
            'start_time'    => null,
            'end_time'      => null,
            'section'       => $sectionValue,
            'department'    => $subject->department,
            'major'         => $subject->major,
            'year_level'    => $subject->year_level,
            'semester'      => $semester,
            'school_year'   => $schoolYear,
            'workspace_key' => $workspaceKey,
            'status'        => Schedule::STATUS_PENDING_GENERATION,
        ]);

        return $newSchedule;
    } catch (\Throwable $e) {
    \Log::error('Schedule creation failed: ' . $e->getMessage());
    \Log::error('Exception: ' . get_class($e));
    \Log::error('Stack: ' . $e->getTraceAsString());
    report($e);
    return null;
}
}

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

        $validation = $this->validatePreAssignmentComplete($faculty, $schedule->subject);

        if (!$validation['eligible']) {
            $this->openAssignmentFailure($schedule, $faculty, $validation['errors']);
            $this->toast('error', 'Assignment blocked by faculty eligibility rules.');
            return;
        }

        // Check if subject has spacetime assigned
        $isUnscheduled = $schedule->day === null 
            || $schedule->start_time === null 
            || $schedule->end_time === null 
            || $schedule->room_id === null;

        // For UNSCHEDULED subjects, use feasibility warnings only
        // For SCHEDULED subjects, check for conflicts
       if ($isUnscheduled) {
    $warnings = $this->assignmentWarnings($faculty, $schedule);
    
    if ($warnings !== []) {
        $this->pendingAssignmentScheduleId = $schedule->id;
        $this->pendingAssignmentWarnings = $warnings;
        $this->assignmentRecommendations = $this->buildAssignmentRecommendations($faculty, $schedule);
        $this->conflictModalOpen = true;
        
        $this->toast(
            $this->canOverrideAssignmentWarnings(Auth::user()) ? 'warning' : 'error',
            'Assignment needs feasibility review.'
        );
        return;
    }
    
    // No warnings for unscheduled - proceed with assignment
    $this->persistAssignment($schedule, $faculty);
    return;
}

        // For SCHEDULED subjects, continue with existing conflict checks
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

    /**
     * Persist pre-assignment state for unscheduled subjects
     * Sets faculty_id and status to 'faculty_locked' or 'pending_generation'
     * Leaves day, start_time, end_time, room_id as NULL for future auto-generation
     */
      

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
            DB::transaction(function () use ($schedule, $faculty, $overridden) {
                // Re-fetch with lock to prevent race conditions
                $fresh = Schedule::lockForUpdate()->find($schedule->id);
                
                if (!$fresh) {
                    throw new \RuntimeException('Schedule was deleted during assignment.');
                }
                
                // Check if already assigned to another faculty
                if ($fresh->faculty_id !== null && (int) $fresh->faculty_id !== (int) $faculty->id) {
                    throw new \RuntimeException(
                        "{$fresh->subject?->subject_code} is already assigned to another faculty."
                    );
                }
                
                $subjectCode = $fresh->subject?->subject_code ?? 'Unknown Subject';
                
                // Determine assignment status
                // If spacetime is NULL (unscheduled), use 'faculty_locked'
                // If spacetime is set (scheduled), use 'partial'
                $isUnscheduled = $fresh->day === null 
                    || $fresh->start_time === null 
                    || $fresh->end_time === null 
                    || $fresh->room_id === null;
                
                $newStatus = $isUnscheduled 
                    ? Schedule::STATUS_FACULTY_LOCKED
                    : Schedule::STATUS_PARTIAL;
                
                // Update the schedule with faculty_id and status
                $fresh->update([
                    'faculty_id' => $faculty->id,
                    'status'     => $newStatus,
                ]);
                
                // Log the assignment action (if FacultyLog model exists)
                if (method_exists('App\Models\FacultyLog', 'create')) {
                    \App\Models\FacultyLog::create([
                        'faculty_id' => $faculty->id,
                        'action' => $isUnscheduled ? 'pre_assignment' : 'assignment',
                        'subject_code' => $subjectCode,
                        'description' => $isUnscheduled
                            ? "Faculty pre-assigned (unscheduled) to {$subjectCode}"
                            : "Faculty assigned to {$subjectCode}",
                        'performed_by' => Auth::id(),
                    ]);
                }
            });

            // Refresh component state OUTSIDE transaction
            unset($this->selectedFaculty);
            $this->resetPendingAssignment();
            
            $message = "{$schedule->subject?->subject_code} assigned to {$faculty->full_name}.";
            $this->toast(
                'success', 
                $overridden ? "{$message} Override recorded." : $message
            );
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
        $overloadFlagged = false;
        $conflictService = app(ScheduleConflictService::class);

        // ════════════════════════════════════════════════════════════════════════════════════
        // OPTIMIZATION: Skip time/room/section conflicts if spacetime is NULL
        // These checks are only meaningful for SCHEDULED subjects
        // ════════════════════════════════════════════════════════════════════════════════════
        $isUnscheduled = $schedule->day === null 
            || $schedule->start_time === null 
            || $schedule->end_time === null 
            || $schedule->room_id === null;

        // ────────────────────────────────────────────────────────────────────────────────────
        // ELIGIBLE CHECK (applies to both scheduled and unscheduled)
        // ────────────────────────────────────────────────────────────────────────────────────
        if (! $schedule->subject) {
            return $warnings;
        }

        if (! $this->canAssignSubject(Auth::user(), $faculty, $schedule->subject)) {
            $warnings[] = $this->eligibilityWarning($faculty, $schedule->subject, $schedule);
        }

        // ────────────────────────────────────────────────────────────────────────────────────
        // SECTION CONFLICT CHECK (only if scheduled)
        // ────────────────────────────────────────────────────────────────────────────────────
        if (! $isUnscheduled) {
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

        // ────────────────────────────────────────────────────────────────────────────────────
        // OVERLOAD & FEASIBILITY CHECKS (applies to both)
        // ────────────────────────────────────────────────────────────────────────────────────
        foreach ($this->assignmentWarningsForFeasibility($faculty, $schedule, $isUnscheduled) as $warning) {
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

        return collect($warnings)->unique(fn (array $warning) => ($warning['type'] ?? '').'|'.($warning['message'] ?? ''))->values()->all();
    }

    /**
     * Internal helper: Extract overload/feasibility warnings separately from time-based warnings
     * @param bool $isUnscheduled If true, skip faculty/room conflict checks (spacetime is NULL)
     */
    private function assignmentWarningsForFeasibility(Faculty $faculty, Schedule $schedule, bool $isUnscheduled): array
    {
        $warnings = [];
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

        // ════════════════════════════════════════════════════════════════════════════════════
        // TIME/ROOM/FACULTY CONFLICTS ONLY IF SCHEDULED
        // ════════════════════════════════════════════════════════════════════════════════════
        if ($isUnscheduled) {
            return $warnings;  // Skip conflict checks; spacetime is still NULL
        }

        // ────────────────────────────────────────────────────────────────────────────────────
        // Time-based conflict checks (only for SCHEDULED subjects)
        // ────────────────────────────────────────────────────────────────────────────────────
        $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i:s');
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
        } elseif ($subject->hasRoomOverride()) {
            $allowed = $subject->eligibleFacultyGroupLabels();
            $message = $allowed === ''
                ? "{$subjectCode} has Room Override enabled but no Eligible Faculty group is checked. Edit the subject and select at least one group."
                : "{$subjectCode} has Room Override enabled — only {$allowed} may be assigned to it.";
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
    $this->pendingAssignmentScheduleId = null;
    $this->pendingAssignmentWarnings = [];
    $this->pendingAssignmentGroupIds = [];
    $this->pendingRawSubjectData = [];
    $this->assignmentRecommendations = [];
    $this->conflictModalOpen = false;
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

        // Room Override: $faculty->isEligibleForSubject() above already resolved
        // eligibility entirely from the subject's Eligible Faculty checkboxes,
        // independent of its Major/Minor type. Re-checking type here would
        // wrongly block a valid checkbox-based assignment (e.g. a Major subject
        // overridden to allow GenEd faculty). All that's left to gate is which
        // SUBJECTS this user is authorized to touch at all — their own department
        // (the same boundary already used by every branch below).
        if ($subject->hasRoomOverride()) {
            return $this->departmentsMatch($subject->department, $user->department);
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

    private function validatePreAssignmentFeasibility(Faculty $faculty, Subject $subject): array
    {
        $warnings = [];
        
        // STEP 1: Calculate current unit load
        $currentSchedules = Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->activeTerm()
            ->with('subject')
            ->get();
        
        $assignedSubjects = $currentSchedules
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();
        
        $currentUnits = (int) $assignedSubjects->sum('units');
        $subjectUnits = (int) ($subject->units ?? 0);
        $maxUnits = (int) ($faculty->max_units ?? 21);
        $newTotal = $currentUnits + $subjectUnits;
        
        // STEP 2: Check weekly unit load (max_units constraint)
        $metrics = [
            'current_units' => $currentUnits,
            'subject_units' => $subjectUnits,
            'new_total' => $newTotal,
            'max_units' => $maxUnits,
            'overload_units' => max(0, $newTotal - $maxUnits),
            'remaining_units' => max(0, $maxUnits - $newTotal),
        ];
        
        if ($newTotal > $maxUnits) {
            $overloadBy = $newTotal - $maxUnits;
            $warnings[] = [
                'type' => 'OVERLOAD',
                'title' => 'Unit Load Exceeded',
                'message' => "Assigning {$subject->subject_code} ({$subjectUnits} units) to {$faculty->full_name} "
                    . "would exceed their max load of {$maxUnits} units by {$overloadBy} unit(s). "
                    . "Current: {$currentUnits} units, New total: {$newTotal} units.",
                'overridable' => true,
            ];
        }
        
        // STEP 3: Check daily distribution feasibility
        $settings = Setting::getScheduleSettings();
        $activeDays = $settings['active_days'] ?? [];
        $maxDailyUnits = (int) ceil($maxUnits / max(1, count($activeDays)));
        
        $dailyDistribution = [];
        foreach ($activeDays as $day) {
            $dayUnits = $currentSchedules
                ->filter(fn (Schedule $s) => $s->day === $day)
                ->pluck('subject')
                ->filter()
                ->unique('id')
                ->sum('units');
            
            $dailyDistribution[$day] = [
                'units' => (int) $dayUnits,
                'available' => max(0, $maxDailyUnits - (int) $dayUnits),
            ];
        }
        
        $hasOptimalDay = collect($dailyDistribution)
            ->contains(fn ($day) => $day['available'] >= $subjectUnits);
        
        if (!$hasOptimalDay && count($activeDays) > 0) {
            $optimalDays = collect($dailyDistribution)
                ->filter(fn ($day) => $day['available'] > 0)
                ->keys()
                ->implode(', ');
            
            if ($optimalDays) {
                $warnings[] = [
                    'type' => 'SUBOPTIMAL_DISTRIBUTION',
                    'title' => 'Daily Distribution Warning',
                    'message' => "For optimal daily load balance, consider scheduling {$subject->subject_code} "
                        . "on: {$optimalDays}. These days have the most available capacity.",
                    'overridable' => true,
                ];
            }
        }
        
        $canAssign = collect($warnings)
            ->filter(fn ($w) => !($w['overridable'] ?? true))
            ->isEmpty();
        
        return [
            'can_assign' => $canAssign,
            'warnings' => $warnings,
            'metrics' => $metrics,
            'daily_distribution' => $dailyDistribution,
        ];
    }

        private function createInitialScheduleRow(Subject $subject): Schedule
    {
        $period = Setting::getAcademicPeriod();
        
        return Schedule::create([
            'subject_id'  => $subject->id,
            'department'  => $subject->department,
            'major'       => $subject->major,
            'year_level'  => $subject->year_level,
            'section'     => $subject->section,
            'semester'    => $period['semester'],
            'school_year' => $period['school_year'],
            'workspace_key' => $period['workspace_key'],
            // Spacetime fields explicitly NULL for pre-assignment
            'room_id'     => null,
            'faculty_id'  => null,
            'day'         => null,
            'start_time'  => null,
            'end_time'    => null,
            'status'      => Schedule::STATUS_PENDING_GENERATION,
        ]);
    }
        /**
     * Validate subject eligibility BEFORE spacetime assignment
     * (existing validation + pre-assignment specific checks)
     */
    private function validateFacultySubjectEligibility(Faculty $faculty, Subject $subject): array
    {
        $errors = [];
        
        // CHECK 1: Faculty eligibility (department, scope, minor rules)
        if (!$faculty->isEligibleForSubject($subject)) {
            $errors[] = [
                'type' => 'FACULTY_INELIGIBLE',
                'title' => 'Faculty Eligibility Mismatch',
                'message' => $this->eligibilityWarning($faculty, $subject)['message'],
                'overridable' => false,
            ];
        }
        
        // CHECK 2: Duplicate subject assignment
        $alreadyAssigned = $faculty->schedules()
            ->activeTerm()
            ->where('subject_id', $subject->id)
            ->exists();
        
        if ($alreadyAssigned) {
            $errors[] = [
                'type' => 'DUPLICATE_SUBJECT',
                'title' => 'Subject Already Assigned',
                'message' => "{$faculty->full_name} is already assigned to {$subject->subject_code}.",
                'overridable' => false,
            ];
        }
        
        return [
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * Comprehensive pre-assignment validation wrapper
     * Combines eligibility + feasibility checks
     */
     private function validatePreAssignmentComplete(Faculty $faculty, Subject $subject): array
    {
        $eligibilityErrors = $this->validateFacultySubjectEligibility($faculty, $subject);
        
        if (!empty($eligibilityErrors['errors'])) {
            return [
                'eligible' => false,
                'feasible' => false,
                'errors' => $eligibilityErrors['errors'],
                'warnings' => [],
                'metrics' => [],
            ];
        }
        
        $feasibility = $this->validatePreAssignmentFeasibility($faculty, $subject);
        
        return [
            'eligible' => true,
            'feasible' => $feasibility['can_assign'],
            'errors' => [],
            'warnings' => $feasibility['warnings'],
            'metrics' => $feasibility['metrics'] ?? [],
        ];
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
    $user = Auth::user();
    
    // Get the active academic period
    $period = Setting::getAcademicPeriod();
    $semester = Setting::normalizeSemester($period['semester']);
    $schoolYear = $period['school_year'];
    $workspaceKey = Setting::workspaceKey($schoolYear, $semester);
    
    // ════════════════════════════════════════════════════════════════════════════════════
    // FACULTY-FIRST QUERY: Start from SUBJECTS table with LEFT JOIN to SCHEDULES
    // This ensures ALL subjects appear, even without a schedule row (NULL spacetime)
    // Uses same logic as Subject::forWorkspace() scope to capture ALL subjects
    // ════════════════════════════════════════════════════════════════════════════════════
    $query = Subject::query()
        // Apply same conditions as Subject::forWorkspace() scope
        ->where('subjects.semester', $semester)
        ->where(function (Builder $q) use ($schoolYear, $workspaceKey) {
            $q->where('subjects.workspace_key', $workspaceKey)
                ->orWhere('subjects.school_year', $schoolYear)
                ->orWhere('subjects.academic_year', $schoolYear);
        })
        ->where('subjects.is_archived', 0)
        // NOW apply the joins
        ->leftJoin('schedules', function ($join) use ($semester, $schoolYear, $workspaceKey) {
    $join->on('subjects.id', '=', 'schedules.subject_id')
         ->where('schedules.semester', $semester)
         ->where(function ($q) use ($schoolYear, $workspaceKey) {
             $q->where('schedules.workspace_key', $workspaceKey)
               ->orWhere('schedules.school_year', $schoolYear)
               ->orWhere('schedules.academic_year', $schoolYear);
         })
         ->where('schedules.is_archived', false);
})
        ->leftJoin('faculties', 'schedules.faculty_id', '=', 'faculties.id')
        ->leftJoin('rooms', 'schedules.room_id', '=', 'rooms.id')
        ->leftJoin('rooms as preferred_rooms', 'subjects.preferred_room_id', '=', 'preferred_rooms.id')
        ->select(
            'subjects.id as subject_id',
            'subjects.*',
            'schedules.id as schedule_id',
            'schedules.room_id',
            'schedules.faculty_id',
            'schedules.section as schedule_section',
            'schedules.day',
            'schedules.start_time',
            'schedules.end_time',
            'schedules.status as schedule_status',
            'schedules.department as schedule_department',
            'schedules.major as schedule_major',
            'schedules.year_level as schedule_year_level',
            'faculties.full_name as faculty_name',
            'rooms.room_name',
            'rooms.id as room_id',
            // Preferred room from subjects.preferred_room_id (set via Manage Rooms)
            'preferred_rooms.room_name as preferred_room_name',
            'preferred_rooms.id as preferred_room_fk_id'
        )
        ->distinct();

    // ────────────────────────────────────────────────────────────────────────────────────
    // ROLE-BASED VISIBILITY (dean/oic/associate_dean can only see their scope)
    // ────────────────────────────────────────────────────────────────────────────────────
    if ($user->role === 'associate_dean') {
        $query->where('subjects.type', 'Minor');
    } elseif (in_array($user->role, ['dean', 'oic'], true)) {
        $query->where(function (Builder $visibility) use ($user) {
            $visibility->where('subjects.type', 'Minor')
                ->orWhere(function (Builder $majorQuery) use ($user) {
                    $majorQuery->where('subjects.type', 'Major')
                        ->whereIn('subjects.department', $this->departmentAliases($user->department));
                });
        });
    }

    // ────────────────────────────────────────────────────────────────────────────────────
    // UI FILTERS — refactored to ->when() so each clause is only appended when the
    // filter value is explicitly set (not 'all' / empty / falsy).
    // This makes impossible combinations (dept=CTE + major=IT) impossible to reach:
    // updatedSubjectDepartmentFilter() resets major to 'all' first, and the major
    // dropdown now only shows codes that belong to the active department.
    // ────────────────────────────────────────────────────────────────────────────────────

    // Department
    $query->when(
        $this->subjectDepartmentFilter !== 'all' && filled($this->subjectDepartmentFilter),
        function (Builder $q) {
            $aliases = $this->departmentAliases($this->subjectDepartmentFilter);
            $q->where(function (Builder $f) use ($aliases) {
                $f->whereIn('subjects.department', $aliases)
                  ->orWhereIn('schedules.department', $aliases);
            });
        }
    );

    // Major — only applied if ALSO consistent with the dept filter (safety net)
    $query->when(
        $this->subjectMajorFilter !== 'all' && filled($this->subjectMajorFilter),
        function (Builder $q) {
            $major = $this->subjectMajorFilter;
            $q->where(function (Builder $f) use ($major) {
                $f->where('subjects.major', $major)
                  ->orWhere('schedules.major', $major);
            });
        }
    );

    // Year Level
    $query->when(
        $this->subjectYearLevelFilter !== 'all' && filled($this->subjectYearLevelFilter),
        function (Builder $q) {
            $year = (int) $this->subjectYearLevelFilter;
            $q->where(function (Builder $f) use ($year) {
                $f->where('subjects.year_level', $year)
                  ->orWhere('schedules.year_level', $year);
            });
        }
    );

    // Section
    $query->when(
        $this->subjectSectionFilter !== 'all' && filled($this->subjectSectionFilter),
        function (Builder $q) {
            $section = $this->subjectSectionFilter;
            $q->where(function (Builder $f) use ($section) {
                $f->where('subjects.section', $section)
                  ->orWhere('schedules.section', $section);
            });
        }
    );

    // Type (Major / Minor)
    $query->when(
        $this->subjectTypeFilter !== 'all' && filled($this->subjectTypeFilter),
        fn (Builder $q) => $q->where('subjects.type', $this->subjectTypeFilter)
    );

    // ────────────────────────────────────────────────────────────────────────────────────
    // SEARCH: Subject code, description, EDP code, section
    // ────────────────────────────────────────────────────────────────────────────────────
    if (strlen($this->subjectSearch) > 1) {
        $term = $this->subjectSearch;
        $departmentTerm = Department::normalizeCode($term);
        
        $query->where(function (Builder $searchQuery) use ($term, $departmentTerm) {
            $searchQuery->where('subjects.subject_code', 'like', "%{$term}%")
                ->orWhere('subjects.description', 'like', "%{$term}%")
                ->orWhere('subjects.edp_code', 'like', "%{$term}%")
                ->orWhere('subjects.section', 'like', "%{$term}%")
                ->orWhere('subjects.department', 'like', "%{$term}%")
                ->orWhere('subjects.major', 'like', "%{$term}%")
                ->orWhere('schedules.section', 'like', "%{$term}%")
                ->orWhere('schedules.department', 'like', "%{$term}%")
                ->orWhere('schedules.major', 'like', "%{$term}%");

            if ($departmentTerm && $departmentTerm !== strtoupper(trim((string) $term))) {
                $searchQuery->orWhere('subjects.department', 'like', "%{$departmentTerm}%")
                    ->orWhere('schedules.department', 'like', "%{$departmentTerm}%");
            }
        });
    }

    // ────────────────────────────────────────────────────────────────────────────────────
    // OPTIONAL: Show unassigned subjects only (toggle in UI)
    // ────────────────────────────────────────────────────────────────────────────────────
    if ($this->showUnassignedOnly) {
        $query->whereNull('schedules.faculty_id');
    }

    // ────────────────────────────────────────────────────────────────────────────────────
    // EXECUTE QUERY AND FILTER BY FACULTY ELIGIBILITY
    // ────────────────────────────────────────────────────────────────────────────────────
    $results = $query->orderBy('subjects.subject_code')->get();

    if ($this->selectedFacultyId) {
    $faculty = Faculty::find($this->selectedFacultyId);

    if ($faculty) {
        // Pre-load every subject touched by the result set — 1 query instead of N
        $subjectCache = Subject::findMany(
            $results->pluck('subject_id')->unique()->all()
        )->keyBy('id');

        $results = $results->filter(function ($row) use ($user, $faculty, $subjectCache) {
            if ((int) ($row->faculty_id ?? 0) === (int) $faculty->id) {
                return true;
            }
            $subject = $subjectCache->get($row->subject_id);
            return $subject ? $this->canAssignSubject($user, $faculty, $subject) : false;
        })->values();
    }
}

    return $results;
}
    private function getGroupedAvailableSubjects(): Collection
{
    $available = $this->getAvailableSubjects();
    
    // ════════════════════════════════════════════════════════════════════════════════════
    // GROUP BY SUBJECT (not schedule details, since some may be unscheduled)
    // ════════════════════════════════════════════════════════════════════════════════════
    return $available
        ->groupBy(function ($row) {
            // Group by subject_id + section (not by time, since unscheduled have NULL times)
            return implode('::', [
                $row->subject_id,
                $row->section ?? 'none',
            ]);
        })
        ->map(function (Collection $group) {
            $first = $group->first();
            
            // ────────────────────────────────────────────────────────────────────────────
            // RECONSTRUCT SUBJECT OBJECT FROM ROW DATA
            // ────────────────────────────────────────────────────────────────────────────
            $subject = Subject::find($first->subject_id);
            
            // ────────────────────────────────────────────────────────────────────────────
            // COLLECT ALL SCHEDULED INSTANCES (may be empty for unscheduled)
            // ────────────────────────────────────────────────────────────────────────────
            $scheduledRows = $group->filter(fn ($row) => $row->schedule_id !== null);
            
            // ────────────────────────────────────────────────────────────────────────────
            // HANDLE UNSCHEDULED vs SCHEDULED
            // ────────────────────────────────────────────────────────────────────────────
            if ($scheduledRows->isEmpty()) {
                // UNSCHEDULED: No schedule row exists yet
                $days = 'Unscheduled';
                $time = 'No time assigned';
                // Fall back to preferred room set via Manage Rooms
                $room = $first->preferred_room_name ?? 'No room';
                $isUnscheduled = true;
            } else {
                // SCHEDULED: Format day/time from schedule rows
                $days = $scheduledRows
                    ->map(fn ($row) => $row->day ?? 'N/A')
                    ->unique()
                    ->values()
                    ->implode(' / ');

                $startTime = $first->start_time ? Carbon::parse($first->start_time)->format('h:i A') : 'N/A';
                $endTime = $first->end_time ? Carbon::parse($first->end_time)->format('h:i A') : 'N/A';
                $time = "{$startTime} - {$endTime}";

                // Use the scheduled room; fall back to preferred room for faculty_locked rows
                $room = $first->room_name ?? $first->preferred_room_name ?? 'No room';
                $isUnscheduled = false;

                // A schedule row exists but spacetime is still null → treat as "awaiting auto-gen"
                if (blank($first->day) && blank($first->start_time)) {
                    $isUnscheduled = true;
                }
            }

            // Track whether the room shown is a pre-assigned preferred room (not a confirmed slot)
            $preferredRoomName = null;
            if (($room !== 'No room') && blank($first->room_name) && filled($first->preferred_room_name)) {
                $preferredRoomName = $first->preferred_room_name;
            }

            // ────────────────────────────────────────────────────────────────────────────
            // FACULTY ASSIGNMENT (may be NULL for unscheduled)
            // ────────────────────────────────────────────────────────────────────────────
            $assignedFacultyId = $group->first(fn ($row) => $row->faculty_id !== null)?->faculty_id;
            $assignedFacultyName = $assignedFacultyId 
                ? ($group->first(fn ($row) => $row->faculty_id !== null)?->faculty_name ?? null)
                : null;

            // ────────────────────────────────────────────────────────────────────────────
            // EXTRACT METADATA
            // ────────────────────────────────────────────────────────────────────────────
            $department = Department::normalizeCode(
                $first->schedule_department ?? $subject?->department
            ) ?? 'N/A';
            $major = $first->schedule_major ?? $subject?->major ?? 'N/A';
            $year = $first->schedule_year_level ?? $subject?->year_level ?? 'N/A';
            $section = $first->schedule_section ?? $subject?->section ?? 'N/A';
            
            $isFinalized = $scheduledRows->contains(fn ($row) => $row->schedule_status === Schedule::STATUS_FINALIZED);

            // ────────────────────────────────────────────────────────────────────────────
            // RETURN GROUPED SUBJECT CARD
            // ────────────────────────────────────────────────────────────────────────────
            return [
                'schedule_ids'          => $scheduledRows->pluck('schedule_id')->filter()->all(),
                'first_schedule_id'     => $scheduledRows->first()?->schedule_id,
                'subject_code'          => $subject?->subject_code ?? 'N/A',
                'edp_code'              => $subject?->edp_code ?? 'No EDP',
                'description'           => $subject?->description ?? 'Untitled subject',
                'units'                 => $subject?->units ?? 0,
                'type'                  => $subject?->type ?? 'N/A',
                'department'            => $department,
                'major'                 => $major,
                'year'                  => $year,
                'section'               => $section,
                'room'                  => $room,
                'preferred_room_name'   => $preferredRoomName,  // non-null only when room is pre-assigned
                'days'                  => $days,
                'time'                  => $time,
                'faculty_id'            => $assignedFacultyId,
                'faculty_name'          => $assignedFacultyName,
                'is_finalized'          => $isFinalized,
                'is_unscheduled'        => $isUnscheduled,
                'subject_id'            => $first->subject_id,
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
        $timeSlots = Setting::getTimeSlots();
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

        $all = $this->collectDepartmentCodes($scheduleDepartments, $subjectDepartments, $departmentCodes);

        // ─── Smart scope filter ────────────────────────────────────────────────────
        // Departmental faculty → only their own department in the dropdown.
        // GenEd / Cross-Department → all departments (they work across the college).
        // ──────────────────────────────────────────────────────────────────────────
        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);

            if ($faculty && $faculty->isDepartmental() && filled($faculty->department)) {
                $normalized = Department::normalizeCode($faculty->department);
                return array_values(array_filter($all, fn ($d) => $d === $normalized));
            }
        }

        return $all;
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

    private function getAvailableMajors(): array
    {
        $scheduleMajors = $this->activeScheduleQuery()->select('major')->distinct()->pluck('major');
        $subjectMajors  = $this->activeSubjectQuery()->select('major')->distinct()->pluck('major');

        $allMajors = collect(self::DEFAULT_MAJOR_FILTERS)
            ->merge($scheduleMajors)
            ->merge($subjectMajors)
            ->filter()
            ->map(fn ($major) => trim((string) $major))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // ─── Smart scope filter ────────────────────────────────────────────────────
        // When a faculty is selected, restrict the Major dropdown to only the codes
        // they are actually eligible to teach:
        //
        //   • Departmental (e.g. CCS) → only their department's sub-majors (IT, ACT)
        //   • Cross-Department         → all majors (they cross into other departments)
        //   • GenEd / Institution-wide → all majors (they teach minors across all depts)
        //
        // If no faculty is selected the full list is shown so the user can still
        // filter the subject list before picking a faculty.
        // ──────────────────────────────────────────────────────────────────────────
        if ($this->selectedFacultyId) {
            $faculty = Faculty::find($this->selectedFacultyId);

            if ($faculty && $faculty->isDepartmental() && filled($faculty->department)) {
                // aliasesFor('CCS') → ['CCS','IT','ACT']
                // Subjects store their major as 'IT' / 'ACT' (not 'CCS'), so
                // comparing against the full alias list is safe and correct.
                $allowedCodes = Department::aliasesFor($faculty->department);

                return collect($allMajors)
                    ->filter(fn (string $major) => in_array($major, $allowedCodes, true))
                    ->values()
                    ->all();
            }

            // GenEd and Cross-Department: fall through to the department-filter below.
        }

        // ─── Scope 2: Department filter ───────────────────────────────────────────
        // When the user has also picked a department in the UI, narrow further so
        // the major options only reflect what actually exists in that department.
        // This prevents cross-department / gened faculty from seeing an 'IT' option
        // while 'CTE' is the active department filter (which would still give 0 rows).
        if ($this->subjectDepartmentFilter !== 'all') {
            $deptAliases = Department::aliasesFor($this->subjectDepartmentFilter);

            $allMajors = collect($allMajors)
                ->filter(fn (string $major) => in_array($major, $deptAliases, true))
                ->values()
                ->all();
        }

        return $allMajors;
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

    private function preAssignmentPlaceholders(Collection $schedules): Collection
    {
        return $schedules->filter(function (Schedule $schedule) {
            // Check if schedule is a pre-assignment placeholder
            // (has faculty assigned but no day/time/room)
            return $schedule->isPreAssignmentPlaceholder();
        });
    }
 
    /**
     * Get count of pre-assignment placeholders for display
     * 
     * @param Collection $schedules Collection of Schedule models
     * @return int Count of pre-assignments
     */
    private function preAssignmentPlaceholderCount(Collection $schedules): int
    {
        return $this->preAssignmentPlaceholders($schedules)->count();
    }
 
    /**
     * Format schedule display data for Blade template
     * 
     * Ensures that pre-assignment placeholders are properly labeled
     * in the UI instead of showing random default times
     */
    private function formatScheduleForDisplay(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'subject_code' => $schedule->subject?->subject_code,
            'faculty_name' => $schedule->getFacultyName(),
            'day' => $schedule->getDayDisplay(),
            'time' => $schedule->getTimeDisplay(),
            'room' => $schedule->getRoomDisplay(),
            'status' => $schedule->getStatusDisplay(),
            'is_placeholder' => $schedule->isPreAssignmentPlaceholder(),
            'is_fully_scheduled' => $schedule->isFullyScheduled(),
            'badge_color' => $this->getScheduleBadgeColor($schedule),
        ];
    }
 
    /**
     * Get badge color for schedule status in UI
     */
    private function getScheduleBadgeColor(Schedule $schedule): string
    {
        if ($schedule->isPreAssignmentPlaceholder()) {
            return 'orange'; // Pre-assignment needs scheduling
        }
 
        return match($schedule->status) {
            Schedule::STATUS_FINALIZED => 'green',
            Schedule::STATUS_PARTIAL => 'blue',
            Schedule::STATUS_FACULTY_LOCKED => 'purple',
            default => 'gray',
        };
    }
 
    /**
     * Get formatted display for assigned schedules with proper placeholders
     */
    private function getFormattedAssignedSchedules(): Collection
    {
        $assignedSchedules = $this->assignedSchedules();
 
        return $assignedSchedules->map(fn (Schedule $schedule) => 
            $this->formatScheduleForDisplay($schedule)
        );
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        $user = Auth::user();
        
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
 
       
        $groupedAvailableSubjects = $this->getGroupedAvailableSubjects();
        $currentFaculty = $this->selectedFaculty;
        $assignedSchedules = $this->assignedSchedules();
        $assignedSubjects = $this->assignedSubjects();
        $groupedAssignedSubjects = $this->groupedAssignedSubjects();
        
        // NEW: Get pre-assignment placeholder count for display
        $preAssignmentCount = $this->preAssignmentPlaceholderCount($assignedSchedules);
        $preAssignmentPlaceholders = $this->preAssignmentPlaceholders($assignedSchedules);
        
        $facultySummary = $this->getFacultySummary();
        $departmentSummary = $this->getDepartmentSummary();
        $facultyDepartments = $this->getFacultyDepartments();
        $scheduleDepartments = $this->getScheduleDepartments();
        $majors = $this->getAvailableMajors();
        $yearLevels = $this->getAvailableYearLevels();
        $sections = $this->getAvailableSections();
        $subjectTypes = $this->getAvailableSubjectTypes();
        $facultyConflicts = $this->getFacultyConflicts($assignedSchedules);
        $scheduleGroups = $this->getScheduleGroups($assignedSchedules);
        $activeDays = Setting::getActiveDays();
        $timeSlots = Setting::getTimeSlots();
 
        return view('livewire.faculty-loading', [
            'faculties' => $faculties,
            
            'groupedAvailableSubjects' => $groupedAvailableSubjects,
            'assignedSchedules' => $assignedSchedules,
            'assignedSubjects' => $assignedSubjects,
            'groupedAssignedSubjects' => $groupedAssignedSubjects,
            'currentFaculty' => $currentFaculty,
            'facultySummary' => $facultySummary,
            'departmentSummary' => $departmentSummary,
            'facultyDepartments' => $facultyDepartments,
            'scheduleDepartments' => $scheduleDepartments,
            'majors' => $majors,
            'yearLevels' => $yearLevels,
            'sections' => $sections,
            'subjectTypes' => $subjectTypes,
            'userRole' => $user->role,
            'activeTab' => $this->activeTab,
            'facultyConflicts' => $facultyConflicts,
            'scheduleGroups' => $scheduleGroups,
            'activeDays' => $activeDays,
            'timeSlots' => $timeSlots,
            'canOverrideWarnings' => $this->canOverrideAssignmentWarnings($user),
            // NEW: Pre-assignment data for display
            'preAssignmentCount' => $preAssignmentCount,
            'preAssignmentPlaceholders' => $preAssignmentPlaceholders,
        ])->layout('layouts.app');
    }
}