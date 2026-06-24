<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Component
{
    // -------------------------------------------------------------------------
    // Dashboard data
    // -------------------------------------------------------------------------
    public array $stats                = [];
    public array $systemStatus         = [];
    public array $recentActivities     = [];
    public array $pendingVerifications = [];
    public array $conflictAlerts       = [];
    public array $schedulingAnalytics  = [];
    public array $workflowCounts       = [];
    public array $departmentProgress   = [];
    public array $approvalQueue        = [];

    // -------------------------------------------------------------------------
    // System-readiness state
    // -------------------------------------------------------------------------

    /** Whether the system has been marked ready for the current semester. */
    public bool $systemReady = false;

    /** Structured checklist items – each has key/label/description/done. */
    public array $setupChecklist = [];

    /** True only when every checklist item passes. */
    public bool $setupComplete = false;

    /** Metadata: who marked ready and when. */
    public array $systemReadyMeta = [];

    /** Confirmation modal for "Mark as Ready". */
    public bool $confirmingMarkReady = false;

    /** Confirmation modal for "Revert to Not Ready". */
    public bool $confirmingMarkNotReady = false;

    // -------------------------------------------------------------------------
    // Livewire listeners
    // -------------------------------------------------------------------------
    protected $listeners = [
        'refreshDashboard'    => '$refresh',
        'systemReadyChanged'  => 'refreshSystemReadiness',
    ];

    // =========================================================================
    // LIFECYCLE
    // =========================================================================

    public function mount(): void
    {
        $this->loadSystemReadiness();
        $this->loadStats();
        $this->loadSystemStatus();
        $this->loadRecentActivities();
        $this->loadPendingVerifications();
        $this->loadConflictAlerts();
        $this->loadSchedulingAnalytics();
        $this->loadWorkflowCounts();
        $this->loadDepartmentProgress();
        $this->loadApprovalQueue();
    }

    // =========================================================================
    // SYSTEM READINESS ACTIONS
    // =========================================================================

    /**
     * Load / refresh all system-readiness properties.
     * Also called via the 'systemReadyChanged' Livewire event.
     */
    public function refreshSystemReadiness(): void
    {
        $this->loadSystemReadiness();
    }

    /**
     * Open the "Mark as Ready" confirmation modal.
     * Guarded: setup must be complete before this is callable.
     */
    public function openMarkReadyModal(): void
    {
        $this->loadSystemReadiness();          // fresh check before showing modal

        if (! $this->setupComplete) {
            $this->dispatch('notify', [
                'type'    => 'warning',
                'message' => 'Please complete all setup steps before marking the system as ready.',
            ]);

            return;
        }

        $this->confirmingMarkReady = true;
    }

    /** Cancel the "Mark as Ready" confirmation. */
    public function cancelMarkReady(): void
    {
        $this->confirmingMarkReady = false;
    }

    /**
     * Confirm and execute "Mark as Ready".
     * Dispatches a broadcast event so Dean/OIC/AssistantDean dashboards
     * update automatically via their own listener.
     */
    public function confirmMarkReady(): void
    {
        $this->confirmingMarkReady = false;

        if (! $this->setupComplete) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'Setup checklist is not complete. Cannot mark the system as ready.',
            ]);

            return;
        }

        Setting::markSystemReady(auth()->id());

        // Audit trail
        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'system_ready',
            'module'      => 'Settings',
            'description' => 'Marked the system as ready for ' . (Setting::getAcademicPeriod()['semester_name'] ?? 'current semester'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadSystemReadiness();
        $this->loadRecentActivities();

        // Notify other open Livewire components (Dean dashboards, etc.)
        $this->dispatch('systemReadyChanged');

        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => 'System marked as ready! Deans and other roles will now see the semester is live.',
        ]);
    }

    /** Open the "Revert to Not Ready" confirmation modal. */
    public function openMarkNotReadyModal(): void
    {
        $this->confirmingMarkNotReady = true;
    }

    /** Cancel the "Revert to Not Ready" confirmation. */
    public function cancelMarkNotReady(): void
    {
        $this->confirmingMarkNotReady = false;
    }

    /**
     * Confirm and execute "Revert to Not Ready".
     * This hides the MasterGrid from Dean-level roles again.
     */
    public function confirmMarkNotReady(): void
    {
        $this->confirmingMarkNotReady = false;

        Setting::markSystemNotReady(auth()->id());

        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'system_not_ready',
            'module'      => 'Settings',
            'description' => 'Reverted system to not-ready state for ' . (Setting::getAcademicPeriod()['semester_name'] ?? 'current semester'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadSystemReadiness();
        $this->loadRecentActivities();

        $this->dispatch('systemReadyChanged');

        $this->dispatch('notify', [
            'type'    => 'warning',
            'message' => 'System reverted to not-ready. Dean-level roles will see the holding screen again.',
        ]);
    }

    // =========================================================================
    // EXISTING ACTIONS
    // =========================================================================

    public function approveFaculty(int $id): void
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->update(['status' => 'approved']);

        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'approved',
            'module'      => 'Faculty',
            'description' => "Approved faculty: {$faculty->full_name}",
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadPendingVerifications();
        $this->loadStats();
        $this->loadWorkflowCounts();
    }

    public function rejectFaculty(int $id): void
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->update(['status' => 'rejected']);

        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'rejected',
            'module'      => 'Faculty',
            'description' => "Rejected faculty: {$faculty->full_name}",
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadPendingVerifications();
        $this->loadStats();
    }

    public function finalizeSchedule(int $id): void
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update(['status' => Schedule::STATUS_FINALIZED]);

        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'finalized',
            'module'      => 'Schedule',
            'description' => "Admin finalized schedule ID #{$id}",
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadApprovalQueue();
        $this->loadStats();
        $this->loadWorkflowCounts();
        $this->loadSchedulingAnalytics();
        $this->loadDepartmentProgress();
    }

    // =========================================================================
    // PRIVATE LOADERS
    // =========================================================================

    private function loadSystemReadiness(): void
    {
        $this->systemReady    = Setting::isSystemReady();
        $this->setupChecklist = Setting::getSetupChecklist();
        $this->setupComplete  = Setting::isSetupComplete();
        $this->systemReadyMeta = Setting::getSystemReadyMeta();
    }

    private function loadStats(): void
    {
        $this->stats = [
            'total_users'         => User::count(),
            'total_faculty'       => Faculty::count(),
            'total_rooms'         => Room::count(),
            'total_subjects'      => Subject::activeTerm()->count(),
            'total_schedules'     => Schedule::activeTerm()->count(),
            'pending_faculty'     => Faculty::where('status', 'pending')->count(),
            'finalized_schedules' => Schedule::activeTerm()->where('status', 'finalized')->count(),
            'draft_schedules'     => Schedule::activeTerm()->where('status', 'draft')->count(),
        ];
    }

    private function loadSystemStatus(): void
    {
        $period = Setting::getAcademicPeriod();

        $conflictCount = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })
            ->where('s1.is_archived', false)
            ->where('s2.is_archived', false)
            ->where('s1.semester', $period['semester'])
            ->where('s2.semester', $period['semester'])
            ->where('s1.academic_year', $period['school_year'])
            ->where('s2.academic_year', $period['school_year'])
            ->count();

        $this->systemStatus = [
            'scheduler'        => $conflictCount === 0 ? 'healthy' : 'warning',
            'conflict_count'   => $conflictCount,
            'db_tables'        => DB::select('SHOW TABLES') ? 'healthy' : 'critical',
            'rooms_available'  => Room::count() > 0 ? 'healthy' : 'warning',
            'faculty_assigned' => Faculty::where('status', 'approved')->count(),
            'current_semester' => $period['semester_name'],
            'school_year'      => $period['school_year'],
            'semester'         => $period['semester'],
            'server_time'      => Carbon::now()->format('H:i:s'),
        ];
    }

    private function loadRecentActivities(): void
    {
        $this->recentActivities = DB::table('activities')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->select('activities.*', 'users.name as user_name', 'users.role as user_role')
            ->orderByDesc('activities.created_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'user'        => $a->user_name,
                'role'        => $a->user_role ?? 'user',
                'action'      => $a->action,
                'module'      => $a->module,
                'description' => $a->description,
                'time'        => Carbon::parse($a->created_at)->diffForHumans(),
                'created_at'  => Carbon::parse($a->created_at)->format('M d, h:i A'),
                'severity'    => $this->deriveSeverity($a->action),
            ])->toArray();
    }

    private function loadPendingVerifications(): void
    {
        $this->pendingVerifications = Faculty::with('user')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'id'         => $f->id,
                'name'       => $f->full_name,
                'department' => $f->department,
                'email'      => $f->email,
                'time'       => $f->created_at->diffForHumans(),
            ])->toArray();
    }

    private function loadConflictAlerts(): void
    {
        $period = Setting::getAcademicPeriod();

        $conflicts = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })
            ->join('rooms', 's1.room_id', '=', 'rooms.id')
            ->where('s1.is_archived', false)
            ->where('s2.is_archived', false)
            ->where('s1.semester', $period['semester'])
            ->where('s2.semester', $period['semester'])
            ->where('s1.academic_year', $period['school_year'])
            ->where('s2.academic_year', $period['school_year'])
            ->select('s1.id', 'rooms.room_name', 's1.day', 's1.start_time', 's1.end_time', 's2.id as conflict_id')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'type'        => 'room_conflict',
                'room'        => $c->room_name,
                'day'         => $c->day,
                'time'        => Carbon::parse($c->start_time)->format('h:i A').' – '.Carbon::parse($c->end_time)->format('h:i A'),
                'severity'    => 'critical',
                'schedule_id' => $c->id,
            ])->toArray();

        $overloaded = Faculty::withCount(['schedules' => fn ($q) => $q->activeTerm()])
            ->having('schedules_count', '>', 6)
            ->limit(3)
            ->get()
            ->map(fn ($f) => [
                'type'     => 'overload',
                'name'     => $f->full_name,
                'dept'     => $f->department,
                'load'     => $f->schedules_count,
                'severity' => 'warning',
            ])->toArray();

        $this->conflictAlerts = array_merge($conflicts, $overloaded);
    }

    private function loadSchedulingAnalytics(): void
    {
        $totalSchedules     = Schedule::activeTerm()->count();
        $finalizedSchedules = Schedule::activeTerm()->where('status', 'finalized')->count();

        $this->schedulingAnalytics = [
            'total_scheduled'      => $totalSchedules,
            'finalized'            => $finalizedSchedules,
            'draft'                => Schedule::activeTerm()->where('status', 'draft')->count(),
            'partial'              => Schedule::activeTerm()->where('status', 'partial')->count(),
            'faculty_assigned'     => Schedule::activeTerm()->where('status', 'faculty_assigned')->count(),
            'rooms_in_use'         => Schedule::activeTerm()->distinct('room_id')->count('room_id'),
            'faculty_with_load'    => Schedule::activeTerm()->whereNotNull('faculty_id')->distinct('faculty_id')->count('faculty_id'),
            'completion_rate'      => $totalSchedules > 0
                ? round(($finalizedSchedules / $totalSchedules) * 100, 1)
                : 0,
            'subjects_unscheduled' => Subject::activeTerm()
                ->whereDoesntHave('schedules', fn ($q) => $q->activeTerm())
                ->count(),
        ];
    }

    private function loadWorkflowCounts(): void
    {
        $this->workflowCounts = [
            'draft'            => Schedule::activeTerm()->where('status', Schedule::STATUS_DRAFT)->count(),
            'partial'          => Schedule::activeTerm()->where('status', Schedule::STATUS_PARTIAL)->count(),
            'faculty_assigned' => Schedule::activeTerm()->where('status', Schedule::STATUS_FACULTY_ASSIGNED)->count(),
            'finalized'        => Schedule::activeTerm()->where('status', Schedule::STATUS_FINALIZED)->count(),
            'total'            => Schedule::activeTerm()->count(),
            'conflict_count'   => $this->systemStatus['conflict_count'] ?? 0,
        ];
    }

    private function loadDepartmentProgress(): void
    {
        $departments = [
            ['code' => 'CCS',  'label' => 'College of Computer Studies',       'majors' => ['IT', 'ACT'],       'color' => 'yellow'],
            ['code' => 'CTE',  'label' => 'College of Teacher Education',       'majors' => ['ED'],              'color' => 'blue'],
            ['code' => 'COC',  'label' => 'College of Criminology',             'majors' => ['FB', 'LD', 'QD'], 'color' => 'violet'],
            ['code' => 'SHTM', 'label' => 'School of Hospitality & Tourism',   'majors' => ['HM', 'TM'],        'color' => 'orange'],
        ];

        $this->departmentProgress = collect($departments)->map(function ($dept) {
            $majors = $dept['majors'];

            $totalSubjects = Subject::activeTerm()
                ->whereIn('major', $majors)
                ->count();

            $scheduledSubjects = Subject::activeTerm()
                ->whereIn('major', $majors)
                ->whereHas('schedules', fn ($q) => $q->activeTerm())
                ->count();

            $finalizedSubjects = Subject::activeTerm()
                ->whereIn('major', $majors)
                ->whereHas('schedules', fn ($q) => $q->activeTerm()->where('status', 'finalized'))
                ->count();

            $rate = $totalSubjects > 0
                ? round(($finalizedSubjects / $totalSubjects) * 100)
                : 0;

            return [
                'code'      => $dept['code'],
                'label'     => $dept['label'],
                'majors'    => implode(', ', $majors),
                'color'     => $dept['color'],
                'total'     => $totalSubjects,
                'scheduled' => $scheduledSubjects,
                'finalized' => $finalizedSubjects,
                'pending'   => max(0, $totalSubjects - $scheduledSubjects),
                'rate'      => $rate,
            ];
        })->toArray();
    }

    private function loadApprovalQueue(): void
    {
        $this->approvalQueue = Schedule::activeTerm()
            ->where('status', Schedule::STATUS_FACULTY_ASSIGNED)
            ->with(['subject', 'room', 'faculty'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'department' => $s->subject?->department ?? $s->department ?? '—',
                'major'      => $s->subject?->major     ?? $s->major      ?? '—',
                'year_level' => $s->year_level          ?? $s->subject?->year_level ?? '—',
                'section'    => $s->section             ?? '—',
                'subject'    => $s->subject?->description ?? $s->subject?->subject_code ?? '—',
                'room'       => $s->room?->room_name    ?? '—',
                'faculty'    => $s->faculty?->full_name ?? 'Unassigned',
                'day'        => $s->day,
                'time'       => Carbon::parse($s->start_time)->format('h:i A').'–'.Carbon::parse($s->end_time)->format('h:i A'),
                'status'     => $s->status,
            ])->toArray();
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    private function deriveSeverity(string $action): string
    {
        if (str_contains($action, 'conflict') || str_contains($action, 'error') || str_contains($action, 'rejected')) {
            return 'critical';
        }

        if (str_contains($action, 'warn') || str_contains($action, 'retry') || str_contains($action, 'returned')) {
            return 'warning';
        }

        if (str_contains($action, 'approved') || str_contains($action, 'finalized') || str_contains($action, 'ready')) {
            return 'success';
        }

        return 'info';
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function render()
    {
        return view('livewire.admin-dashboard', [
            // System-readiness — always passed explicitly so the blade
            // can render the setup banner / checklist without needing
            // to call Setting:: helpers directly.
            'systemReady'     => $this->systemReady,
            'setupChecklist'  => $this->setupChecklist,
            'setupComplete'   => $this->setupComplete,
            'systemReadyMeta' => $this->systemReadyMeta,

            // Existing dashboard data
            'stats'                => $this->stats,
            'systemStatus'         => $this->systemStatus,
            'recentActivities'     => $this->recentActivities,
            'pendingVerifications' => $this->pendingVerifications,
            'conflictAlerts'       => $this->conflictAlerts,
            'schedulingAnalytics'  => $this->schedulingAnalytics,
            'workflowCounts'       => $this->workflowCounts,
            'departmentProgress'   => $this->departmentProgress,
            'approvalQueue'        => $this->approvalQueue,
        ])->layout('layouts.app');
    }
}