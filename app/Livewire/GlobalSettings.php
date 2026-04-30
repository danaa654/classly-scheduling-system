<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GlobalSettings extends Component
{
    // System Config Properties
    public $start_time, $end_time, $semester_name, $default_duration;
    
    // Department Properties
    public $new_dept_name, $new_dept_code;
    
    // UI State
    public $confirmingReset = false;

    /**
     * Security Gatekeeper: Ensure only Admin and Registrar can enter.
     */
    public function mount()
    {
        $user = auth()->user();
        
        // Strictly check for authorized roles
        if (!$user || !in_array($user->role, ['admin', 'registrar'])) {
            abort(403, 'Unauthorized access to system configurations.');
        }

        // Load existing settings with defaults for PAP standards
        $this->start_time = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->end_time = Setting::where('key', 'end_time')->first()?->value ?? '20:00';
        $this->semester_name = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
        $this->default_duration = Setting::where('key', 'default_duration')->first()?->value ?? '1.0';
    }

    public function save()
{
    $this->validate([
        'semester_name'    => 'required|string|min:3',
        'start_time'       => 'required',
        'end_time'         => 'required|after:start_time',
        'default_duration' => 'required|numeric|min:0.5|max:5', 
    ]);

    // Logic check: Ensure day is long enough
    $start = Carbon::parse($this->start_time);
    $end = Carbon::parse($this->end_time);
    $totalMinutesAvailable = $start->diffInMinutes($end);
    $slotMinutesRequired = floatval($this->default_duration) * 60;

    if ($totalMinutesAvailable < $slotMinutesRequired) {
        $this->dispatch('notify', [
            'type' => 'error', 
            'message' => 'The total school day is too short for the default class duration!'
        ]);
        return;
    }

    // Sync to Database
    $settings = [
        'semester_name'    => $this->semester_name,
        'start_time'       => $this->start_time,
        'end_time'         => $this->end_time,
        'default_duration' => $this->default_duration,
    ];

    foreach ($settings as $key => $value) {
        Setting::updateOrCreate(['key' => $key], ['value' => (string)$value]);
    }

    // ✨ NEW: Broadcast change to all listeners
    $this->dispatch('settings-updated')->to(MasterGrid::class);

    $this->dispatch('notify', [
        'type' => 'success',
        'message' => "System configurations updated for {$this->semester_name}."
    ]);
}

    public function addDepartment()
    {
        $this->validate([
            'new_dept_name' => 'required|unique:departments,name',
            'new_dept_code' => 'required|unique:departments,code|max:10',
        ]);

        Department::create([
            'name' => $this->new_dept_name,
            'code' => strtoupper($this->new_dept_code),
        ]);

        $this->reset(['new_dept_name', 'new_dept_code']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'New Department registered.']);
    }

    public function deleteDepartment($id)
    {
        $dept = Department::findOrFail($id);

        // Foreign Key Check: Prevent orphaned data
        $isLinked = Faculty::where('department_id', $id)->exists() || 
                    Subject::where('department_id', $id)->exists();

        if ($isLinked) {
            $this->dispatch('notify', [
                'type' => 'error', 
                'message' => "Cannot delete {$dept->code}. Faculty or Subjects are currently assigned to it."
            ]);
            return;
        }

        $dept->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Department successfully removed.']);
    }

    /**
     * End of Semester: Archive current data and wipe the Grid
     */
    public function archiveAndReset()
    {
        $currentSchedules = Schedule::all();

        if ($currentSchedules->isEmpty()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Grid is already empty. Nothing to archive.']);
            $this->confirmingReset = false;
            return;
        }

        try {
            DB::transaction(function () use ($currentSchedules) {
                // 1. Move to Archive
                DB::table('schedule_archives')->insert([
                    'semester_name' => $this->semester_name,
                    'schedule_data' => $currentSchedules->toJson(),
                    'archived_at'   => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // 2. Clear current schedule table
                Schedule::query()->delete();
            });

            $this->confirmingReset = false;
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Semester archived. Grid is now clear for new entries.']);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Archive failed: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.global-settings', [
            'departments' => Department::latest()->get(),
            'archives'    => DB::table('schedule_archives')->latest()->get(),
        ])->layout('layouts.app');
    }
}