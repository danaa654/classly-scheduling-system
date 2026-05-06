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
    public $day_start, $day_end, $school_year, $semester, $semester_name;
    
    // Access Control & Audit
    public $config_locked = true;
    public $last_updated_by;
    public $last_updated_at;
    
    // Lunch Break (Hard-coded, read-only)
    public const LUNCH_START = '12:00';
    public const LUNCH_END = '13:00';
    
    // Maintenance Mode
    public $maintenance_mode = false;
    
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
     */
    private function loadAllSettings()
    {
        $this->day_start = Setting::where('key', 'day_start')->first()?->value ?? '07:00';
        $this->day_end = Setting::where('key', 'day_end')->first()?->value ?? '21:00';
        $this->school_year = Setting::where('key', 'school_year')->first()?->value ?? '2026-2027';
        $this->semester = Setting::where('key', 'semester')->first()?->value ?? '1st';
        $this->semester_name = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
        
        // Access control
        $this->config_locked = (bool)Setting::where('key', 'config_locked')->first()?->value ?? true;
        
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
        return $this->validateTimeIncrement($this->day_start)
            && $this->validateTimeIncrement($this->day_end);
    }

    /**
     * Check if any schedules exist that would conflict with new day bounds.
     */
    private function hasScheduleConflicts(): array
    {
        $conflicts = [];
        $newStart = Carbon::parse($this->day_start);
        $newEnd = Carbon::parse($this->day_end);
        $oldStart = Carbon::parse(Setting::where('key', 'day_start')->first()?->value ?? '07:00');
        $oldEnd = Carbon::parse(Setting::where('key', 'day_end')->first()?->value ?? '21:00');

        // Get all schedules
        $schedules = Schedule::all();

        foreach ($schedules as $schedule) {
            $scheduleStart = Carbon::parse($schedule->start_time);
            $scheduleEnd = Carbon::parse($schedule->end_time);

            // Check if schedule starts before new day start
            if ($scheduleStart < $newStart && $scheduleStart >= $oldStart) {
                $conflicts[] = [
                    'schedule_id' => $schedule->id,
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue' => 'starts before new day start time',
                ];
            }

            // Check if schedule ends after new day end
            if ($scheduleEnd > $newEnd && $scheduleEnd <= $oldEnd) {
                $conflicts[] = [
                    'schedule_id' => $schedule->id,
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue' => 'ends after new day end time',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Toggle the "Safe Mode" lock.
     */
    public function toggleLock()
    {
        $this->config_locked = !$this->config_locked;
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $this->config_locked 
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
        if ($this->config_locked) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Configuration is locked. Click "Unlock" to modify settings.',
            ]);
            return;
        }

        // Validation
        $this->validate([
            'school_year'   => 'required|string|min:4',
            'semester'      => 'required|in:1st,2nd,Summer',
            'semester_name' => 'required|string|min:3',
            'day_start'     => 'required|date_format:H:i',
            'day_end'       => 'required|date_format:H:i|after:day_start',
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
        $start = Carbon::parse($this->day_start);
        $end = Carbon::parse($this->day_end);
        $totalMinutesAvailable = $start->diffInMinutes($end);
        $brickMinutesRequired = self::BRICK_DURATION * 60; // 30 minutes

        if ($totalMinutesAvailable < $brickMinutesRequired) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'The total school day is too short for a single 30-minute brick slot!',
            ]);
            return;
        }

        // NEW: Check if new bounds conflict with existing schedules
        $conflicts = $this->hasScheduleConflicts();
        if (!empty($conflicts)) {
            $conflictDetails = collect($conflicts)->map(function ($conflict) {
                return "• {$conflict['subject_code']} on {$conflict['day']} ({$conflict['time']}) - {$conflict['issue']}";
            })->join("\n");

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '⚠️ Cannot change day bounds - existing schedules conflict',
                'detail' => "The following schedules would be outside the new bounds:\n\n{$conflictDetails}\n\nPlease remove or reschedule these classes first.",
            ]);
            return;
        }

        // SYNC TO DATABASE with Audit Trail
        try {
            DB::transaction(function () {
                $settingsToUpdate = [
                    'school_year'   => $this->school_year,
                    'semester'      => $this->semester,
                    'semester_name' => $this->semester_name,
                    'day_start'     => $this->day_start,
                    'day_end'       => $this->day_end,
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
                Setting::updateOrCreate(['key' => 'config_locked'], ['value' => '1']);
                $this->config_locked = true;
            });

            // BROADCAST to MasterGrid (if exists)
            $this->dispatch('settings-updated')->to(MasterGrid::class);
            
            $this->loadChangeHistory();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✅ System configuration updated. Configuration re-locked.",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Save failed: ' . $e->getMessage(),
            ]);
        }
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
                Setting::updateOrCreate(['key' => 'config_locked'], ['value' => '1']);
            });

            $this->confirmingReset = false;
            $this->config_locked = true;
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
            'archives' => DB::table('schedule_archives')->latest()->get(),
            'changeHistory' => $this->changeHistory,
            'brickDuration' => self::BRICK_DURATION,
            'lunchStart' => self::LUNCH_START,
            'lunchEnd' => self::LUNCH_END,
        ])->layout('layouts.app');
    }
}
