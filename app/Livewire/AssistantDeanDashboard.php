<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssistantDeanDashboard extends Component
{
    public $facultyCoordination  = [];
    public $scheduleReview       = [];
    public $curriculumValidation = [];
    public $subjectDistribution  = [];
    public $aiRecommendations    = [];
    public $globalStats          = [];
    public array $workflowCounts = [];
    public array $schedulingStats = [];
    public bool $systemReady = false;
    public array $currentPeriod = [];

    protected $listeners = [
        'systemReadyChanged' => 'refreshSystemReadiness',
    ];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function refreshSystemReadiness(): void
    {
        $wasReady = $this->systemReady;

        $this->loadDashboard();

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
        $this->currentPeriod = Setting::getAcademicPeriod();
    }

    private function loadDashboard(): void
    {
        $this->loadSystemReadiness();
        $this->loadGlobalStats();
        $this->loadSchedulingWorkflow();
        $this->loadFacultyCoordination();
        $this->loadSubjectDistribution();

        if (! $this->systemReady) {
            $this->loadSetupModeDashboard();

            return;
        }

        $this->loadScheduleReview();
        $this->loadCurriculumValidation();
        $this->loadAiRecommendations();
    }

    private function loadSetupModeDashboard(): void
    {
        $this->scheduleReview = [];
        $this->curriculumValidation = [];
        $this->aiRecommendations = [
            [
                'type'   => 'info',
                'icon'   => '',
                'title'  => 'Semester Setup In Progress',
                'detail' => 'Registrar or admin is finalizing the academic period, active days, and schedule bounds.',
                'action' => 'Prepare faculty loading and preferred rooms',
            ],
        ];

        $this->globalStats['total_schedules'] = 0;
        $this->globalStats['finalized_schedules'] = 0;
        $this->globalStats['completion_pct'] = 0;
    }

    private function loadGlobalStats(): void
    {
        $totalSubjects    = Subject::activeTerm()->count();
        $scheduledSubjects = Subject::activeTerm()->whereHas('schedules', fn ($query) => $query->activeTerm())->count();
        $totalSchedules   = Schedule::activeTerm()->count();
        $finalizedCount   = Schedule::activeTerm()->where('status', 'finalized')->count();

        $this->globalStats = [
            'total_faculty'       => Faculty::where('status', 'approved')->count(),
            'total_subjects'      => $totalSubjects,
            'minor_subjects'      => Subject::activeTerm()->where('type', 'Minor')->count(),
            'major_subjects'      => Subject::activeTerm()->where('type', 'Major')->count(),
            'total_rooms'         => Room::count(),
            'total_users'         => User::count(),
            'total_schedules'     => $totalSchedules,
            'finalized_schedules' => $finalizedCount,
            'completion_pct'      => $totalSubjects > 0
                ? round(($scheduledSubjects / $totalSubjects) * 100, 1)
                : 0,
            'departments'         => ['CCS', 'CTE', 'COC', 'SHTM'],
        ];
    }

    private function loadSchedulingWorkflow(): void
    {
        $totalSubjects = Subject::activeTerm()->count();
        $scheduledSubjects = Subject::activeTerm()
            ->whereHas('schedules', fn ($query) => $query->activeTerm())
            ->count();
        $finalizedSubjects = Subject::activeTerm()
            ->whereHas('schedules', fn ($query) => $query->activeTerm()->where('status', Schedule::STATUS_FINALIZED))
            ->count();

        $draft = Schedule::activeTerm()->where('status', Schedule::STATUS_DRAFT)->count();
        $partial = Schedule::activeTerm()->where('status', Schedule::STATUS_PARTIAL)->count();
        $facultyAssigned = Schedule::activeTerm()
            ->whereIn('status', [Schedule::STATUS_FACULTY_ASSIGNED, Schedule::STATUS_FACULTY_LOCKED])
            ->count();
        $finalized = Schedule::activeTerm()->where('status', Schedule::STATUS_FINALIZED)->count();
        $conflicts = $this->countActiveRoomConflicts();

        $this->workflowCounts = [
            'draft'            => $draft,
            'partial'          => $partial,
            'faculty_assigned' => $facultyAssigned,
            'finalized'        => $finalized,
            'conflict_count'   => $conflicts,
        ];

        $this->schedulingStats = [
            'total_subjects'       => $totalSubjects,
            'scheduled_subjects'   => $scheduledSubjects,
            'unscheduled_subjects' => max(0, $totalSubjects - $scheduledSubjects),
            'draft_schedules'      => $draft,
            'partial_schedules'    => $partial,
            'finalized_schedules'  => $finalized,
            'finalized_subjects'   => $finalizedSubjects,
            'completion_pct'       => $totalSubjects > 0 ? round(($finalizedSubjects / $totalSubjects) * 100, 1) : 0,
        ];
    }

    private function countActiveRoomConflicts(): int
    {
        $period = Setting::getAcademicPeriod();

        return DB::table('schedules as s1')
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
    }

    private function loadFacultyCoordination(): void
    {
        $faculty = Faculty::where('status', 'approved')
            ->withCount(['schedules' => fn ($query) => $query->activeTerm()])
            ->get();

        $overloaded  = $faculty->filter(fn ($f) => $f->schedules_count > ($f->max_units ?? 6))->count();
        $underloaded = $faculty->filter(fn ($f) => $f->schedules_count === 0)->count();
        $normal      = $faculty->count() - $overloaded - $underloaded;

        $deptBreakdown = Faculty::where('status', 'approved')
            ->select('department', DB::raw('count(*) as total'))
            ->groupBy('department')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->department => $item->total])
            ->toArray();

        $this->facultyCoordination = [
            'total'          => $faculty->count(),
            'overloaded'     => $overloaded,
            'underloaded'    => $underloaded,
            'normal'         => $normal,
            'dept_breakdown' => $deptBreakdown,
            'top_loaded'     => Faculty::where('status', 'approved')
                ->withCount(['schedules' => fn ($query) => $query->activeTerm()])
                ->orderByDesc('schedules_count')
                ->limit(5)
                ->get()
                ->map(fn ($f) => [
                    'name'       => $f->full_name,
                    'department' => $f->department,
                    'load'       => $f->schedules_count,
                    'max'        => $f->max_units ?? 6,
                    'status'     => $f->schedules_count > ($f->max_units ?? 6) ? 'overloaded'
                                  : ($f->schedules_count === 0 ? 'unassigned' : 'normal'),
                ])->toArray(),
        ];
    }

    private function loadScheduleReview(): void
    {
        // Schedules that are still in draft/partial (need review)
        $this->scheduleReview = Schedule::activeTerm()->whereIn('status', ['draft', 'partial'])
            ->with(['subject', 'room', 'faculty'])
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'subject'     => optional($s->subject)->subject_code ?? '—',
                'department'  => optional($s->subject)->department ?? '—',
                'room'        => optional($s->room)->room_name ?? '—',
                'faculty'     => optional($s->faculty)->full_name ?? 'Unassigned',
                'day'         => $s->day,
                'time'        => Carbon::parse($s->start_time)->format('h:i A') . ' – ' . Carbon::parse($s->end_time)->format('h:i A'),
                'status'      => $s->status,
                'flag'        => is_null($s->faculty_id) ? 'No Faculty' : 'Incomplete',
            ])->toArray();
    }

    private function loadCurriculumValidation(): void
    {
        // Subjects with no schedule at all
        $missing = Subject::activeTerm()
            ->whereDoesntHave('schedules', fn ($query) => $query->activeTerm())
            ->select('id', 'subject_code', 'description', 'department', 'year_level', 'type')
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'subject_code' => $s->subject_code,
                'description'  => $s->description,
                'department'   => $s->department,
                'year_level'   => $s->year_level,
                'type'         => $s->type,
                'issue'        => 'No schedule assigned',
            ])->toArray();

        // Schedules with no faculty assigned
        $noFaculty = Schedule::activeTerm()->whereNull('faculty_id')
            ->with('subject')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'subject_code' => optional($s->subject)->subject_code ?? '—',
                'department'   => optional($s->subject)->department ?? '—',
                'year_level'   => optional($s->subject)->year_level ?? '—',
                'type'         => optional($s->subject)->type ?? '—',
                'issue'        => 'No faculty assigned',
            ])->toArray();

        $this->curriculumValidation = array_merge($missing, $noFaculty);
    }

    private function loadSubjectDistribution(): void
    {
        $deptData = Subject::activeTerm()->select('department', 'type', DB::raw('count(*) as total'))
            ->groupBy('department', 'type')
            ->get();

        $departments = $deptData->pluck('department')->unique()->values()->toArray();
        $breakdown   = [];

        foreach ($departments as $dept) {
            $major = $deptData->where('department', $dept)->where('type', 'Major')->first();
            $minor = $deptData->where('department', $dept)->where('type', 'Minor')->first();

            $breakdown[$dept] = [
                'major' => $major ? $major->total : 0,
                'minor' => $minor ? $minor->total : 0,
                'total' => ($major ? $major->total : 0) + ($minor ? $minor->total : 0),
            ];
        }

        $this->subjectDistribution = [
            'by_department' => $breakdown,
            'total_major'   => Subject::activeTerm()->where('type', 'Major')->count(),
            'total_minor'   => Subject::activeTerm()->where('type', 'Minor')->count(),
        ];
    }

    private function loadAiRecommendations(): void
    {
        $recommendations = [];

        // Check for overloaded faculty
        $overloaded = Faculty::where('status', 'approved')
            ->withCount(['schedules' => fn ($query) => $query->activeTerm()])
            ->having('schedules_count', '>', 6)
            ->count();

        if ($overloaded > 0) {
            $recommendations[] = [
                'type'    => 'warning',
                'icon'    => '⚠️',
                'title'   => 'Faculty Overload Detected',
                'detail'  => "{$overloaded} faculty member(s) exceed max teaching load.",
                'action'  => 'Review Faculty Loading',
            ];
        }

        // Unscheduled subjects
        $unscheduled = Subject::activeTerm()
            ->whereDoesntHave('schedules', fn ($query) => $query->activeTerm())
            ->count();
        if ($unscheduled > 0) {
            $recommendations[] = [
                'type'    => 'info',
                'icon'    => '📋',
                'title'   => 'Unscheduled Subjects',
                'detail'  => "{$unscheduled} subject(s) have no schedule assigned.",
                'action'  => 'Run Auto-Scheduler',
            ];
        }

        // Room conflicts
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

        if ($conflictCount > 0) {
            $recommendations[] = [
                'type'    => 'critical',
                'icon'    => '🔴',
                'title'   => 'Room Scheduling Conflicts',
                'detail'  => "{$conflictCount} overlap(s) found across rooms.",
                'action'  => 'Resolve Conflicts',
            ];
        }

        // Rooms running empty
        $emptyRooms = Room::whereDoesntHave('schedules', fn ($query) => $query->activeTerm())->count();
        if ($emptyRooms > 0) {
            $recommendations[] = [
                'type'    => 'info',
                'icon'    => '🏫',
                'title'   => 'Idle Rooms',
                'detail'  => "{$emptyRooms} room(s) have no scheduled classes.",
                'action'  => 'Optimize Room Allocation',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type'   => 'success',
                'icon'   => '✅',
                'title'  => 'System Optimal',
                'detail' => 'No scheduling issues detected.',
                'action' => null,
            ];
        }

        $this->aiRecommendations = $recommendations;
    }

    public function render()
    {
        return view('livewire.assistant-dean-dashboard')->layout('layouts.app');
    }
}
