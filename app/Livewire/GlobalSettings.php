<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SettingChangeLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GlobalSettings extends Component
{
    // SYSTEM CONFIG
    public $start_time, $end_time, $semester_name, $default_duration;
    
    // NEW: Access Control & Audit
    public $is_locked = true;
    public $last_updated_by;
    public $last_updated_at;
    
    // NEW: Room Constraints
    public $turnover_buffer = 10;
    public $default_room_capacity = 40;
    
    // NEW: Lunch Break
    public $lunch_break_start = '12:00';
    public $lunch_break_end = '13:00';
    
    // NEW: Maintenance Mode
    public $maintenance_mode = false;
    
    // DEPARTMENT MANAGEMENT
    public $new_dept_name, $new_dept_code;
    
    // UI State
    public $confirmingReset = false;
    public $changeHistory = [];

    /**
     * Security Gatekeeper: Ensure only Admin and Registrar can enter.
     */
    public function mount()
    {
        $user = auth()->user();
        
        if (!$user || !in_array($user->role, ['admin', 'registrar'])) {
            abort(403, 'Unauthorized access to system configurations.');
        }

        $this->loadAllSettings();
        $this->loadChangeHistory();
    }

    /**
     * Load all settings from database with PAP standards defaults
     */
    private function loadAllSettings()
    {
        $this->start_time = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->end_time = Setting::where('key', 'end_time')->first()?->value ?? '20:00';
        $this->semester_name = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
        $this->default_duration = Setting::where('key', 'default_duration')->first()?->value ?? '1.0';
        
        // NEW SETTINGS
        $this->is_locked = (bool)Setting::where('key', 'is_locked')->first()?->value ?? true;
        $this->turnover_buffer = (int)Setting::where('key', 'turnover_buffer')->first()?->value ?? 10;
        $this->default_room_capacity = (int)Setting::where('key', 'default_room_capacity')->first()?->value ?? 40;
        $this->lunch_break_start = Setting::where('key', 'lunch_break_start')->first()?->value ?? '12:00';
        $this->lunch_break_end = Setting::where('key', 'lunch_break_end')->first()?->value ?? '13:00';
        $this->maintenance_mode = (bool)Setting::where('key', 'maintenance_mode')->first()?->value ?? false;
    }

    /**
     * Load change history for audit trail
     */
    private function loadChangeHistory()
    {
        $this->changeHistory = SettingChangeLog::latest()
            ->take(10)
            ->get()
            ->toArray();
    }

    /**
     * Toggle the "Safe Mode" lock
     */
    public function toggleLock()
    {
        $this->is_locked = !$this->is_locked;
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $this->is_locked ? 'Configuration locked. Changes are disabled.' : '⚠️ Configuration unlocked. Proceed with caution.',
        ]);
    }

    /**
     * Toggle Maintenance Mode (prevents Deans from adding subjects)
     */
    public function toggleMaintenanceMode()
    {
        $this->maintenance_mode = !$this->maintenance_mode;
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => (string)$this->maintenance_mode]);
        
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => $this->maintenance_mode ? 'Maintenance mode ON. Deans cannot modify subjects.' : 'Maintenance mode OFF.',
        ]);
    }

    public function save()
    {
        // GUARD: Check if locked
        if ($this->is_locked) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Configuration is locked. Click "Modify Configuration" to unlock.',
            ]);
            return;
        }

        $this->validate([
            'semester_name'       => 'required|string|min:3',
            'start_time'          => 'required',
            'end_time'            => 'required|after:start_time',
            'default_duration'    => 'required|numeric|min:0.5|max:5',
            'turnover_buffer'     => 'required|integer|min:0|max:30',
            'default_room_capacity' => 'required|integer|min:10|max:200',
            'lunch_break_start'   => 'required',
            'lunch_break_end'     => 'required|after:lunch_break_start',
        ]);

        // LOGIC CHECK: Day is long enough
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $totalMinutesAvailable = $start->diffInMinutes($end);
        $slotMinutesRequired = floatval($this->default_duration) * 60;

        if ($totalMinutesAvailable < $slotMinutesRequired) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'The total school day is too short for the default class duration!',
            ]);
            return;
        }

        // SYNC TO DATABASE with Audit Trail
        try {
            DB::transaction(function () {
                $settingsToUpdate = [
                    'semester_name'       => $this->semester_name,
                    'start_time'          => $this->start_time,
                    'end_time'            => $this->end_time,
                    'default_duration'    => $this->default_duration,
                    'turnover_buffer'     => $this->turnover_buffer,
                    'default_room_capacity' => $this->default_room_capacity,
                    'lunch_break_start'   => $this->lunch_break_start,
                    'lunch_break_end'     => $this->lunch_break_end,
                ];

                foreach ($settingsToUpdate as $key => $value) {
                    $oldValue = Setting::where('key', $key)->first()?->value;
                    
                    Setting::updateOrCreate(['key' => $key], [
                        'value' => (string)$value,
                        'last_updated_by' => auth()->id(),
                        'last_updated_at' => now(),
                    ]);

                    // LOG CHANGE
                    SettingChangeLog::create([
                        'user_id' => auth()->id(),
                        'setting_key' => $key,
                        'old_value' => $oldValue,
                        'new_value' => (string)$value,
                        'action' => 'updated',
                        'changed_at' => now(),
                    ]);
                }

                // RE-LOCK after save for safety
                Setting::updateOrCreate(['key' => 'is_locked'], ['value' => '1']);
                $this->is_locked = true;
            });

            // BROADCAST to MasterGrid
            $this->dispatch('settings-updated')->to(MasterGrid::class);
            
            $this->loadChangeHistory();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✅ System configuration updated for {$this->semester_name}. Configuration re-locked.",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Save failed: ' . $e->getMessage(),
            ]);
        }
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
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'New Department registered.',
        ]);
    }

    public function deleteDepartment($id)
    {
        $dept = Department::findOrFail($id);

        $isLinked = Faculty::where('department_id', $id)->exists() || 
                    Subject::where('department_id', $id)->exists();

        if ($isLinked) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Cannot delete {$dept->code}. Faculty or Subjects are currently assigned.",
            ]);
            return;
        }

        $dept->delete();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Department successfully removed.',
        ]);
    }

    /**
     * End of Semester: Archive current data and wipe the Grid
     */
    public function archiveAndReset()
    {
        $currentSchedules = Schedule::all();

        if ($currentSchedules->isEmpty()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Grid is already empty. Nothing to archive.',
            ]);
            $this->confirmingReset = false;
            return;
        }

        try {
            DB::transaction(function () use ($currentSchedules) {
                // 1. Archive current schedules
                DB::table('schedule_archives')->insert([
                    'semester_name' => $this->semester_name,
                    'schedule_data' => $currentSchedules->toJson(),
                    'archived_at'   => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // 2. Clear schedules
                Schedule::query()->delete();
                
                // 3. Reset to locked state
                Setting::updateOrCreate(['key' => 'is_locked'], ['value' => '1']);
            });

            $this->confirmingReset = false;
            $this->is_locked = true;
            $this->loadChangeHistory();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '✅ Semester archived. Grid cleared. Configuration re-locked.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Archive failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.global-settings', [
            'departments' => Department::latest()->get(),
            'archives' => DB::table('schedule_archives')->latest()->get(),
            'changeHistory' => $this->changeHistory,
        ])->layout('layouts.app');
    }
}