<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Faculty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrarDashboard extends Component
{
    public $schedulingStats     = [];
    public $conflicts           = [];
    public $roomUtilization     = [];
    public $facultyLoad         = [];
    public $recentActivities    = [];
    public $unscheduledSubjects = [];

    public function mount(): void
    {
        $this->loadSchedulingStats();
        $this->loadConflicts();
        $this->loadRoomUtilization();
        $this->loadFacultyLoad();
        $this->loadRecentActivities();
        $this->loadUnscheduledSubjects();
    }

    private function loadSchedulingStats(): void
    {
        $total     = Subject::activeTerm()->count();
        $scheduled = Subject::activeTerm()->whereHas('schedules', fn ($query) => $query->activeTerm())->count();
        $finalized = Schedule::activeTerm()->where('status', 'finalized')->count();
        $draft     = Schedule::activeTerm()->where('status', 'draft')->count();
        $partial   = Schedule::activeTerm()->where('status', 'partial')->count();

        $this->schedulingStats = [
            'total_subjects'       => $total,
            'scheduled_subjects'   => $scheduled,
            'unscheduled_subjects' => max(0, $total - $scheduled),
            'finalized_schedules'  => $finalized,
            'draft_schedules'      => $draft,
            'partial_schedules'    => $partial,
            'total_schedules'      => Schedule::activeTerm()->count(),
            'completion_pct'       => $total > 0 ? round(($scheduled / $total) * 100, 1) : 0,
            'rooms_total'          => Room::count(),
            'faculty_total'        => Faculty::where('status', 'approved')->count(),
        ];
    }

    private function loadConflicts(): void
    {
        $period = Setting::getAcademicPeriod();

        $this->conflicts = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })
            ->join('rooms', 's1.room_id', '=', 'rooms.id')
            ->join('subjects as sub1', 's1.subject_id', '=', 'sub1.id')
            ->join('subjects as sub2', 's2.subject_id', '=', 'sub2.id')
            ->where('s1.is_archived', false)
            ->where('s2.is_archived', false)
            ->where('s1.semester', $period['semester'])
            ->where('s2.semester', $period['semester'])
            ->where('s1.academic_year', $period['school_year'])
            ->where('s2.academic_year', $period['school_year'])
            ->select(
                's1.id as schedule_id',
                's2.id as conflict_id',
                'rooms.room_name',
                's1.day',
                's1.start_time',
                's1.end_time',
                'sub1.subject_code as subject_a',
                'sub2.subject_code as subject_b'
            )
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'schedule_id' => $c->schedule_id,
                'conflict_id' => $c->conflict_id,
                'room'        => $c->room_name,
                'day'         => $c->day,
                'time'        => Carbon::parse($c->start_time)->format('h:i A') . ' – ' . Carbon::parse($c->end_time)->format('h:i A'),
                'subject_a'   => $c->subject_a,
                'subject_b'   => $c->subject_b,
                'severity'    => 'critical',
            ])->toArray();
    }

    private function loadRoomUtilization(): void
    {
        $this->roomUtilization = Room::withCount(['schedules' => fn ($query) => $query->activeTerm()])
            ->orderByDesc('schedules_count')
            ->limit(6)
            ->get()
            ->map(fn ($r) => [
                'name'       => $r->room_name,
                'type'       => $r->type,
                'floor'      => $r->floor,
                'schedules'  => $r->schedules_count,
                'pct'        => min(100, round(($r->schedules_count / max(1, 30)) * 100)),
            ])->toArray();
    }

    private function loadFacultyLoad(): void
    {
        $this->facultyLoad = Faculty::withCount(['schedules' => fn ($query) => $query->activeTerm()])
            ->where('status', 'approved')
            ->orderByDesc('schedules_count')
            ->limit(8)
            ->get()
            ->map(fn ($f) => [
                'id'         => $f->id,
                'name'       => $f->full_name,
                'department' => $f->department,
                'load'       => $f->schedules_count,
                'max_units'  => $f->max_units ?? 6,
                'status'     => $f->schedules_count > ($f->max_units ?? 6) ? 'overloaded'
                              : ($f->schedules_count === 0 ? 'unassigned' : 'normal'),
            ])->toArray();
    }

    private function loadRecentActivities(): void
    {
        $this->recentActivities = DB::table('activities')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->select('activities.*', 'users.name as user_name')
            ->orderByDesc('activities.created_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'user'        => $a->user_name,
                'action'      => $a->action,
                'module'      => $a->module,
                'description' => $a->description,
                'time'        => Carbon::parse($a->created_at)->diffForHumans(),
            ])->toArray();
    }

    private function loadUnscheduledSubjects(): void
    {
        $this->unscheduledSubjects = Subject::activeTerm()
            ->whereDoesntHave('schedules', fn ($query) => $query->activeTerm())
            ->select('id', 'subject_code', 'description', 'department', 'year_level', 'section', 'type')
            ->limit(8)
            ->get()
            ->toArray();
    }

    public function resolveConflict(int $scheduleId): void
    {
        Schedule::activeTerm()->where('id', $scheduleId)->delete();

        DB::table('activities')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'resolved',
            'module'      => 'Schedule',
            'description' => "Resolved schedule conflict for schedule #{$scheduleId}",
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->loadConflicts();
        $this->loadSchedulingStats();
    }

    public function render()
    {
        return view('livewire.registrar-dashboard')->layout('layouts.app');
    }
}
