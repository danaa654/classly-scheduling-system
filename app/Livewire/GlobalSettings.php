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
    // SYSTEM CONFIG - 30-minute brick philosophy
    public const BRICK_DURATION = 0.5; // Hard-coded 30 minutes
    
    // Institutional time settings
    public $start_time, $end_time, $semester_name;
    
    // Access Control & Audit
    public $is_locked = true;
    public $last_updated_by;
    public $last_updated_at;
    
    // Lunch Break Configuration
    public $lunch_break_start = '12:00';
    public $lunch_break_end = '13:00';
    
    // Maintenance Mode
    public $maintenance_mode = false;
    
    // Department Management
    public $new_dept_name, $new_dept_code;
    
    // UI State
    public $confirmingReset = false;
    public $changeHistory = [];

    /**
     * Security Gatekeeper: Ensure only Admin and Registrar can access.
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
     * Load all settings from database with PAP standards defaults.
     * Note: default_duration is now a system constant (0.5 hours = 30 minutes).
     */
    private function loadAllSettings()
    {
        $this->start_time = Setting::where('key', 'start_time')->first()?->value ?? '07:00';
        $this->end_time = Setting::where('key', 'end_time')->first()?->value ?? '20:00';
        $this->semester_name = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
        
        // Lunch break settings
        $this->lunch_break_start = Setting::where('key', 'lunch_break_start')->first()?->value ?? '12:00';
        $this->lunch_break_end = Setting::where('key', 'lunch_break_end')->first()?->value ?? '13:00';
        
        // Access control
        $this->is_locked = (bool)Setting::where('key', 'is_locked')->first()?->value ?? true;
        
        // Maintenance mode
        $this->maintenance_mode = (bool)Setting::where('key', 'maintenance_mode')->first()?->value ?? false;
    }

    /**
     * Load change history for audit trail (latest 10 changes).
     */
    private function loadChangeHistory()
    {
        $this->changeHistory = SettingChangeLog::latest()
            ->take(10)
            ->get()
            ->toArray();
    }

    /**
     * Validate that a time string is snapped to 30-minute increments.
     * Valid: 07:00, 07:30, 08:00, 08:30, etc.
     * Invalid: 07:15, 07:45, etc.
     */
    private function validateTimeIncrement(string $time): bool
    {
        $parts = explode(':', $time);
        if (count($parts) !== 2) {
            return false;
        }
        
        $minutes = (int)$parts[1];
        return in_array($minutes, [0, 30], true);
    }

    /**
     * Validate that all time-based settings are aligned to 30-minute increments.
     */
    private function validateAllTimeIncrements(): bool
    {
        return $this->validateTimeIncrement($this->start_time)
            && $this->validateTimeIncrement($this->end_time)
            && $this->validateTimeIncrement($this->lunch_break_start)
            && $this->validateTimeIncrement($this->lunch_break_end);
    }

    /**
     * Toggle the "Safe Mode" lock.
     */
    public function toggleLock()
    {
        $this->is_locked = !$this->is_locked;
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $this->is_locked 
                ? 'Configuration locked. Changes are disabled.' 
                : '⚠️ Configuration unlocked. Proceed with caution.',
        ]);
    }

    /**
     * Toggle Maintenance Mode (prevents Deans from modifying data).
     */
    public function toggleMaintenanceMode()
    {
        $this->maintenance_mode = !$this->maintenance_mode;
        
        Setting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => (string)$this->maintenance_mode]
        );
        
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => $this->maintenance_mode 
                ? 'Maintenance mode ON. Deans cannot modify subjects.' 
                : 'Maintenance mode OFF.',
        ]);
    }

    /**
     * Save system configuration with comprehensive validation.
     */
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

        // Validation
        $this->validate([
            'semester_name'     => 'required|string|min:3',
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i|after:start_time',
            'lunch_break_start' => 'required|date_format:H:i',
            'lunch_break_end'   => 'required|date_format:H:i|after:lunch_break_start',
        ]);

        // NEW: Validate 30-minute increments
        if (!$this->validateAllTimeIncrements()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '⏰ All times must be aligned to 30-minute increments (:00 or :30).',
            ]);
            return;
        }

        // LOGIC CHECK: Day is long enough for at least one 30-minute brick
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $totalMinutesAvailable = $start->diffInMinutes($end);
        $brickMinutesRequired = self::BRICK_DURATION * 60; // 30 minutes

        if ($totalMinutesAvailable < $brickMinutesRequired) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'The total school day is too short for a single 30-minute brick slot!',
            ]);
            return;
        }

        // Ensure lunch break is within school day
        $lunchStart = Carbon::parse($this->lunch_break_start);
        $lunchEnd = Carbon::parse($this->lunch_break_end);

        if ($lunchStart->lessThan($start) || $lunchEnd->greaterThan($end)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Lunch break must fall within school operating hours.',
            ]);
            return;
        }

        // SYNC TO DATABASE with Audit Trail
        try {
            DB::transaction(function () {
                $settingsToUpdate = [
                    'semester_name'     => $this->semester_name,
                    'start_time'        => $this->start_time,
                    'end_time'          => $this->end_time,
                    'lunch_break_start' => $this->lunch_break_start,
                    'lunch_break_end'   => $this->lunch_break_end,
                ];

                foreach ($settingsToUpdate as $key => $value) {
                    $oldValue = Setting::where('key', $key)->first()?->value;
                    
                    Setting::updateOrCreate(['key' => $key], [
                        'value' => (string)$value,
                        'last_updated_by' => auth()->id(),
                        'last_updated_at' => now(),
                    ]);

                    // LOG CHANGE only if value changed
                    if ($oldValue !== (string)$value) {
                        SettingChangeLog::create([
                            'user_id' => auth()->id(),
                            'setting_key' => $key,
                            'old_value' => $oldValue,
                            'new_value' => (string)$value,
                            'action' => 'updated',
                            'changed_at' => now(),
                        ]);
                    }
                }

                // RE-LOCK after save for safety
                Setting::updateOrCreate(['key' => 'is_locked'], ['value' => '1']);
                $this->is_locked = true;
            });

            // BROADCAST to MasterGrid (if exists)
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

    /**
     * Add a new department.
     */
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

    /**
     * Delete a department (with safety checks).
     */
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
     * End of Semester: Archive current data and wipe the Grid.
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

    /**
     * Render the component with all necessary data.
     */
    public function render()
    {
        return view('livewire.global-settings', [
            'departments' => Department::latest()->get(),
            'archives' => DB::table('schedule_archives')->latest()->get(),
            'changeHistory' => $this->changeHistory,
            'brickDuration' => self::BRICK_DURATION,
        ])->layout('layouts.app');
    }
}