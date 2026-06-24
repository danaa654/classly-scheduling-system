<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeanDashboard extends Component
{
    public $department;
    public $academicOverview      = [];
    public $approvalQueue         = [];
    public $curriculumCoverage    = [];
    public $facultySummary        = [];
    public $escalatedConflicts    = [];
    public $requestTracking       = [];
    public $facultyRequestHistory = [];
    public array $workflowCounts = [];
    public array $schedulingStats = [];
    public bool $systemReady = false;
    public array $systemReadyMeta = [];
    public array $currentPeriod = [];

    protected $listeners = [
        'systemReadyChanged' => 'refreshSystemReadiness',
    ];

    public function mount(): void
    {
        $this->department = Auth::user()->department;

        $this->loadSystemReadiness();
        $this->loadAcademicOverview();
        $this->loadApprovalQueue();
        $this->loadCurriculumCoverage();
        $this->loadFacultySummary();
        $this->loadEscalatedConflicts();
        $this->loadSchedulingWorkflow();
        $this->loadRequestTracking();
        $this->loadFacultyRequestHistory();
    }

    public function refreshSystemReadiness(): void
    {
        $wasReady = $this->systemReady;

        $this->loadSystemReadiness();

        if (! $wasReady && $this->systemReady) {
            $this->dispatch('notify', [
                'type'    => 'success',
                'message' => 'Semester configuration is ready. MasterGrid and room view are now available.',
            ]);
        }
    }

    private function loadSystemReadiness(): void
    {
        $this->systemReady = Setting::isSystemReady();
        $this->systemReadyMeta = Setting::getSystemReadyMeta();
        $this->currentPeriod = Setting::getAcademicPeriod();
    }

    private function loadAcademicOverview(): void
    {
        $dept = $this->department;

        $totalSubjects    = Subject::activeTerm()->where('department', $dept)->count();
        $majorSubjects    = Subject::activeTerm()->where('department', $dept)->where('type', 'Major')->count();
        $minorSubjects    = Subject::activeTerm()->where('department', $dept)->where('type', 'Minor')->count();
        $scheduledSubjects = Subject::activeTerm()
            ->where('department', $dept)
            ->whereHas('schedules', fn ($query) => $query->activeTerm())
            ->count();
        $totalFaculty     = Faculty::where('department', $dept)->where('status', 'approved')->count();
        $assignedFaculty  = Faculty::where('department', $dept)
            ->where('status', 'approved')
            ->whereHas('schedules', fn ($query) => $query->activeTerm())
            ->count();

        $this->academicOverview = [
            'total_subjects'     => $totalSubjects,
            'major_subjects'     => $majorSubjects,
            'minor_subjects'     => $minorSubjects,
            'scheduled_subjects' => $scheduledSubjects,
            'unscheduled'        => max(0, $totalSubjects - $scheduledSubjects),
            'completion_rate'    => $totalSubjects > 0
                ? round(($scheduledSubjects / $totalSubjects) * 100, 1)
                : 0,
            'total_faculty'      => $totalFaculty,
            'assigned_faculty'   => $assignedFaculty,
            'unassigned_faculty' => max(0, $totalFaculty - $assignedFaculty),
            'total_rooms'        => Room::count(),
        ];
    }

    private function loadApprovalQueue(): void
    {
        // Pending faculty requests for this department
        $pending = Faculty::where('department', $this->department)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'id'          => $f->id,
                'type'        => 'Faculty Registration',
                'description' => "{$f->full_name} — {$f->employment_type}",
                'submitted_by' => optional($f->requestedBy)->name ?? 'System',
                'time'        => $f->created_at->diffForHumans(),
                'module'      => 'faculty',
            ])->toArray();

        $this->approvalQueue = $pending;
    }

    private function loadCurriculumCoverage(): void
    {
        $byYear = [];
        for ($year = 1; $year <= 4; $year++) {
            $total     = Subject::activeTerm()->where('department', $this->department)->where('year_level', $year)->count();
            $scheduled = Subject::activeTerm()->where('department', $this->department)
                ->where('year_level', $year)
                ->whereHas('schedules', fn ($query) => $query->activeTerm())
                ->count();

            $byYear[] = [
                'year'      => $year,
                'total'     => $total,
                'scheduled' => $scheduled,
                'pct'       => $total > 0 ? round(($scheduled / $total) * 100) : 0,
            ];
        }

        $this->curriculumCoverage = $byYear;
    }

    private function loadFacultySummary(): void
    {
        $this->facultySummary = Faculty::where('department', $this->department)
            ->where('status', 'approved')
            ->withCount(['schedules' => fn ($query) => $query->activeTerm()])
            ->orderByDesc('schedules_count')
            ->limit(8)
            ->get()
            ->map(fn ($f) => [
                'id'         => $f->id,
                'name'       => $f->full_name,
                'load'       => $f->schedules_count,
                'max_units'  => $f->max_units ?? 6,
                'type'       => $f->employment_type,
                'status'     => $f->schedules_count > ($f->max_units ?? 6) ? 'overloaded'
                              : ($f->schedules_count === 0 ? 'unassigned' : 'normal'),
            ])->toArray();
    }

    private function loadEscalatedConflicts(): void
    {
        $period = Setting::getAcademicPeriod();

        $this->escalatedConflicts = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })
            ->join('subjects as sub1', 's1.subject_id', '=', 'sub1.id')
            ->join('rooms', 's1.room_id', '=', 'rooms.id')
            ->where('sub1.department', $this->department)
            ->where('s1.is_archived', false)
            ->where('s2.is_archived', false)
            ->where('s1.semester', $period['semester'])
            ->where('s2.semester', $period['semester'])
            ->where('s1.academic_year', $period['school_year'])
            ->where('s2.academic_year', $period['school_year'])
            ->select('s1.id', 'rooms.room_name', 's1.day', 's1.start_time', 's1.end_time')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'room' => $c->room_name,
                'day'  => $c->day,
                'time' => Carbon::parse($c->start_time)->format('h:i A'),
            ])->toArray();
    }

    private function loadSchedulingWorkflow(): void
    {
        $dept = $this->department;

        $scheduleQuery = Schedule::activeTerm()
            ->whereHas('subject', fn ($query) => $query->where('department', $dept));

        $totalSubjects = Subject::activeTerm()
            ->where('department', $dept)
            ->count();

        $scheduledSubjects = Subject::activeTerm()
            ->where('department', $dept)
            ->whereHas('schedules', fn ($query) => $query->activeTerm())
            ->count();

        $finalizedSubjects = Subject::activeTerm()
            ->where('department', $dept)
            ->whereHas('schedules', fn ($query) => $query->activeTerm()->where('status', Schedule::STATUS_FINALIZED))
            ->count();

        $this->workflowCounts = [
            'draft'            => (clone $scheduleQuery)->where('status', Schedule::STATUS_DRAFT)->count(),
            'partial'          => (clone $scheduleQuery)->where('status', Schedule::STATUS_PARTIAL)->count(),
            'faculty_assigned' => (clone $scheduleQuery)->whereIn('status', [
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FACULTY_LOCKED,
            ])->count(),
            'finalized'        => (clone $scheduleQuery)->where('status', Schedule::STATUS_FINALIZED)->count(),
            'conflict_count'   => count($this->escalatedConflicts),
        ];

        $this->schedulingStats = [
            'total_subjects'       => $totalSubjects,
            'scheduled_subjects'   => $scheduledSubjects,
            'unscheduled_subjects' => max(0, $totalSubjects - $scheduledSubjects),
            'finalized_subjects'   => $finalizedSubjects,
            'completion_pct'       => $totalSubjects > 0 ? round(($finalizedSubjects / $totalSubjects) * 100, 1) : 0,
        ];
    }

    private function loadRequestTracking(): void
    {
        $this->requestTracking = DB::table('activities')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->select('activities.*', 'users.name as user_name')
            ->orderByDesc('activities.created_at')
            ->limit(6)
            ->get()
            ->map(fn ($a) => [
                'description' => $a->description,
                'action'      => $a->action,
                'module'      => $a->module,
                'user'        => $a->user_name,
                'time'        => Carbon::parse($a->created_at)->diffForHumans(),
                'status'      => in_array($a->action, ['approved', 'finalized', 'resolved']) ? 'approved'
                              : (in_array($a->action, ['rejected', 'deleted']) ? 'rejected' : 'pending'),
            ])->toArray();
    }

    private function loadFacultyRequestHistory(): void
    {
        // Fetch approved/rejected/pending faculty requested by the dean's department,
        // joined with the activity log to find who acted on each request.
        $this->facultyRequestHistory = Faculty::where('department', $this->department)
            ->whereIn('status', ['approved', 'rejected', 'pending'])
            ->with('requestedBy')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($f) {
                // Try to find who acted on this request from the activity log
                $actedBy = null;
                if (in_array($f->status, ['approved', 'rejected'])) {
                    $action   = $f->status === 'approved' ? 'approved' : 'rejected';
                    $activity = DB::table('activities')
                        ->join('users', 'activities.user_id', '=', 'users.id')
                        ->where('activities.module', 'Faculty')
                        ->where('activities.action', $action)
                        ->where('activities.description', 'like', "%{$f->full_name}%")
                        ->orderByDesc('activities.created_at')
                        ->select('users.name')
                        ->first();
                    $actedBy = optional($activity)->name;
                }

                return [
                    'id'               => $f->id,
                    'name'             => $f->full_name,
                    'employment_type'  => $f->employment_type ?? 'Full-time',
                    'status'           => $f->status,
                    'rejection_reason' => $f->rejection_reason,
                    'submitted_by'     => optional($f->requestedBy)->name ?? 'System',
                    'acted_by'         => $actedBy,
                    'time'             => $f->updated_at->diffForHumans(),
                ];
            })->toArray();
    }

    public function approveItem(int $id, string $module): void
    {
        if ($module === 'faculty') {
            $faculty = Faculty::findOrFail($id);
            $faculty->update(['status' => 'approved']);

            DB::table('activities')->insert([
                'user_id'     => auth()->id(),
                'action'      => 'approved',
                'module'      => 'Faculty',
                'description' => "Dean approved faculty: {$faculty->full_name}",
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->loadApprovalQueue();
        $this->loadFacultySummary();
        $this->loadFacultyRequestHistory();
    }

    public function rejectItem(int $id, string $module): void
    {
        if ($module === 'faculty') {
            $faculty = Faculty::findOrFail($id);
            $faculty->update(['status' => 'rejected']);

            DB::table('activities')->insert([
                'user_id'     => auth()->id(),
                'action'      => 'rejected',
                'module'      => 'Faculty',
                'description' => "Dean rejected faculty: {$faculty->full_name}",
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->loadApprovalQueue();
        $this->loadFacultyRequestHistory();
    }

    public function render()
    {
        return view('livewire.dean-dashboard')->layout('layouts.app');
    }
}
