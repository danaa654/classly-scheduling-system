<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Component
{
    public $stats = [];
    public $systemStatus = [];
    public $recentActivities = [];
    public $pendingVerifications = [];
    public $conflictAlerts = [];
    public $schedulingAnalytics = [];

    public function mount()
    {
        $this->loadStats();
        $this->loadSystemStatus();
        $this->loadRecentActivities();
        $this->loadPendingVerifications();
        $this->loadConflictAlerts();
        $this->loadSchedulingAnalytics();
    }

    private function loadStats(): void
    {
        $this->stats = [
            'total_users'     => User::count(),
            'total_faculty'   => Faculty::count(),
            'total_rooms'     => Room::count(),
            'total_subjects'  => Subject::count(),
            'total_schedules' => Schedule::count(),
            'pending_faculty' => Faculty::where('status', 'pending')->count(),
            'finalized_schedules' => Schedule::where('status', 'finalized')->count(),
            'draft_schedules'     => Schedule::where('status', 'draft')->count(),
        ];
    }

    private function loadSystemStatus(): void
    {
        $conflictCount = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })->count();

        $this->systemStatus = [
            'scheduler'        => $conflictCount === 0 ? 'healthy' : 'warning',
            'conflict_count'   => $conflictCount,
            'db_tables'        => DB::select("SHOW TABLES") ? 'healthy' : 'critical',
            'rooms_available'  => Room::count() > 0 ? 'healthy' : 'warning',
            'faculty_assigned' => Faculty::where('status', 'approved')->count(),
            'current_semester' => '1st Semester 2026–2027',
            'server_time'      => Carbon::now()->format('H:i:s'),
        ];
    }

    private function loadRecentActivities(): void
    {
        $this->recentActivities = DB::table('activities')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->select('activities.*', 'users.name as user_name')
            ->orderByDesc('activities.created_at')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'user'        => $a->user_name,
                'action'      => $a->action,
                'module'      => $a->module,
                'description' => $a->description,
                'time'        => Carbon::parse($a->created_at)->diffForHumans(),
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
        $conflicts = DB::table('schedules as s1')
            ->join('schedules as s2', function ($join) {
                $join->on('s1.room_id', '=', 's2.room_id')
                     ->on('s1.day', '=', 's2.day')
                     ->where('s1.id', '<', DB::raw('s2.id'))
                     ->whereRaw('s1.start_time < s2.end_time')
                     ->whereRaw('s1.end_time > s2.start_time');
            })
            ->join('rooms', 's1.room_id', '=', 'rooms.id')
            ->select('s1.id', 'rooms.room_name', 's1.day', 's1.start_time', 's1.end_time', 's2.id as conflict_id')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'room'      => $c->room_name,
                'day'       => $c->day,
                'time'      => Carbon::parse($c->start_time)->format('h:i A') . ' - ' . Carbon::parse($c->end_time)->format('h:i A'),
                'severity'  => 'critical',
                'schedule_id' => $c->id,
            ])->toArray();

        // Overloaded faculty (more than max_units' worth of schedules)
        $overloaded = Faculty::withCount('schedules')
            ->having('schedules_count', '>', 6)
            ->limit(3)
            ->get()
            ->map(fn ($f) => [
                'name'     => $f->full_name,
                'dept'     => $f->department,
                'load'     => $f->schedules_count,
                'severity' => 'warning',
                'type'     => 'overload',
            ])->toArray();

        $this->conflictAlerts = array_merge($conflicts, $overloaded);
    }

    private function loadSchedulingAnalytics(): void
    {
        $today = Carbon::today();

        $this->schedulingAnalytics = [
            'total_scheduled'   => Schedule::count(),
            'finalized'         => Schedule::where('status', 'finalized')->count(),
            'draft'             => Schedule::where('status', 'draft')->count(),
            'partial'           => Schedule::where('status', 'partial')->count(),
            'rooms_in_use'      => Schedule::distinct('room_id')->count('room_id'),
            'faculty_assigned'  => Schedule::whereNotNull('faculty_id')->distinct('faculty_id')->count('faculty_id'),
            'completion_rate'   => Schedule::count() > 0
                ? round((Schedule::where('status', 'finalized')->count() / Schedule::count()) * 100, 1)
                : 0,
            'subjects_unscheduled' => Subject::whereDoesntHave('schedules')->count(),
        ];
    }

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

    private function deriveSeverity(string $action): string
    {
        return match (true) {
            str_contains($action, 'conflict') || str_contains($action, 'error') => 'critical',
            str_contains($action, 'warn') || str_contains($action, 'retry')     => 'warning',
            default => 'info',
        };
    }

    public function render()
    {
        return view('livewire.admin-dashboard')->layout('layouts.app');
    }
}