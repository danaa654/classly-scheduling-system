<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SettingChangeLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class GlobalSettings extends Component
{
    // SYSTEM CONFIG - 30-minute brick philosophy
    public const BRICK_DURATION = 0.5; // Hard-coded 30 minutes
    
    // Institutional time settings
    public $day_start, $day_end, $school_year, $semester, $semester_name;
    public array $active_days = [];
    
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

    // Archived History Logs
    // wire:model.live on the dropdown triggers a full Livewire network round-trip
    // on every change, so the #[Computed] properties below are re-evaluated
    // automatically. The unsetComputedProperty() call below ensures Livewire's
    // per-request computed cache is busted so stale results never leak through.
    public ?string $selectedHistoricalSemester = null;

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
     * Livewire lifecycle hook — fired whenever $selectedHistoricalSemester changes.
     * No-op body needed: the updated hook itself causes a re-render, and render()
     * calls archivedHistoryRecords() directly (no computed cache), so the fresh
     * value of $selectedHistoricalSemester is always used automatically.
     */
    public function updatedSelectedHistoricalSemester(): void
    {
        // Intentionally empty — the re-render triggered by this hook is sufficient.
        // archivedHistoryRecords() is a plain method (not #[Computed]), so there
        // is no stale cache to bust.
    }

    /**
     * Load all settings from database with PAP standards defaults.
     */
    private function loadAllSettings()
    {
        $scheduleSettings = Setting::getScheduleSettings();

        $this->active_days = $scheduleSettings['active_days'];
        $this->day_start = $scheduleSettings['start_time'];
        $this->day_end = $scheduleSettings['end_time'];
        $this->school_year = Setting::getValue('school_year', '2026-2027');
        $this->semester = Setting::getValue('semester', '1st');
        $this->semester_name = Setting::getValue('semester_name', 'First Semester 2026-2027');
        
        // Access control
        $this->config_locked = Setting::isConfigLocked();
        
        // Maintenance mode
        $this->maintenance_mode = Setting::getBoolean('maintenance_mode', false);
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
        $activeDays = Setting::normalizeActiveDays($this->active_days);

        // Get all schedules
        $schedules = Schedule::with('subject:id,subject_code')->get();

        foreach ($schedules as $schedule) {
            $scheduleStart = Carbon::parse($schedule->start_time);
            $scheduleEnd = Carbon::parse($schedule->end_time);

            if (!in_array($schedule->day, $activeDays, true)) {
                $conflicts[] = [
                    'schedule_id' => $schedule->id,
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue' => 'is scheduled on a disabled day',
                ];

                continue;
            }

            // Check if schedule starts before new day start
            if ($scheduleStart < $newStart) {
                $conflicts[] = [
                    'schedule_id' => $schedule->id,
                    'subject_code' => $schedule->subject?->subject_code ?? 'Unknown',
                    'day' => $schedule->day,
                    'time' => $scheduleStart->format('H:i') . ' - ' . $scheduleEnd->format('H:i'),
                    'issue' => 'starts before new day start time',
                ];
            }

            // Check if schedule ends after new day end
            if ($scheduleEnd > $newEnd) {
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
            'active_days'   => 'required|array|min:1',
            'active_days.*' => ['required', 'string', Rule::in(Setting::ALL_SCHEDULE_DAYS)],
            'day_start'     => 'required|date_format:H:i',
            'day_end'       => 'required|date_format:H:i|after:day_start',
        ]);

        $this->active_days = Setting::normalizeActiveDays($this->active_days);

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
                    'active_days'   => json_encode($this->active_days),
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
                    'school_year' => $this->school_year,
                    'total_schedules' => $currentSchedules->count(),
                    'schedule_data' => $currentSchedules->toJson(),
                    'archived_by' => auth()->id(),
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
     * Build the dropdown option list for the history log selector.
     *
     * Two sources are unified under one selector using a prefixed value scheme:
     *   "archive:{id}"  → a schedule_archives blob row (created by archiveAndReset())
     *   "soft_archived" → all schedules where is_archived = true (created by resetSemester())
     *
     * Blob archives are listed newest-first. The soft-archived virtual entry is
     * prepended only when at least one soft-archived row exists.
     *
     * Both sources are guarded against missing tables/columns so the page never
     * crashes on a fresh or partially-migrated database.
     */
    #[Computed]
    public function archivedSemesterOptions(): \Illuminate\Support\Collection
    {
        // ── Source A: hard-archive blob rows ──────────────────────────────
        // Guard: skip if the schedule_archives table has not been migrated yet.
        $blobOptions = collect();
        if (Schema::hasTable('schedule_archives')) {
            $blobOptions = DB::table('schedule_archives')
                ->select('id', 'semester_name', 'school_year', 'total_schedules', 'archived_at')
                ->latest('archived_at')
                ->get()
                ->map(fn ($row) => (object) [
                    'value' => "archive:{$row->id}",
                    'label' => "{$row->semester_name} — {$row->school_year}",
                    'badge' => $row->total_schedules . ' schedules',
                    'date'  => $row->archived_at,
                ]);
        }

        // ── Source B: soft-archived live rows (virtual entry) ─────────────
        // Guard: the is_archived column only exists after its migration has been
        // run. Skip this block entirely if the column is not yet present so the
        // settings page never crashes on a fresh or partially-migrated database.
        $softCount = 0;
        if (Schema::hasColumn('schedules', 'is_archived')) {
            $softCount = DB::table('schedules')
                ->where(function ($q) {
                    $q->where('is_archived', true)->orWhere('is_archived', 1);
                })
                ->count();
        }

        if ($softCount > 0) {
            $blobOptions = $blobOptions->prepend((object) [
                'value' => 'soft_archived',
                'label' => 'Current Cycle — Soft-Archived Schedules',
                'badge' => $softCount . ' schedules',
                'date'  => null,
            ]);
        }

        return $blobOptions;
    }

    /**
     * Return the display rows for whichever archive period is selected.
     *
     * This is a plain method (NOT #[Computed]) so that render() always receives
     * a fresh result reflecting the current value of $selectedHistoricalSemester.
     * Using #[Computed] caused a stale-cache bug: the cache was populated with
     * the old value before the updated() lifecycle hook could update the property,
     * resulting in the table always showing "No semester selected" after a pick.
     *
     * Handles both archive strategies:
     *
     * "archive:{id}" path — decodes the schedule_data JSON blob stored by
     *   archiveAndReset(). The blob contains the raw schedule rows at archive time,
     *   including subject_id, faculty_id, and descriptive_title (stored directly on
     *   the row). We batch-load subjects and faculty for subject_code/units only,
     *   avoiding a query for descriptive_title which may not exist on the subjects
     *   table (it varies per deployment — e.g. subject_description, title, etc.).
     *
     * "soft_archived" path — queries the live schedules table with eager loading
     *   on subject and faculty, same defensive column handling applies.
     *
     * Returns a flat Collection of plain objects with a stable, uniform shape.
     */
    public function archivedHistoryRecords(): \Illuminate\Support\Collection
    {
        if (! $this->selectedHistoricalSemester) {
            return collect();
        }

        // ── Path A: decode blob archive ───────────────────────────────────
        if (str_starts_with($this->selectedHistoricalSemester, 'archive:')) {
            $archiveId = (int) str_replace('archive:', '', $this->selectedHistoricalSemester);

            $archive = DB::table('schedule_archives')->find($archiveId);

            if (! $archive || empty($archive->schedule_data)) {
                return collect();
            }

            $rows = collect(json_decode($archive->schedule_data, true) ?? []);

            if ($rows->isEmpty()) {
                return collect();
            }

            // Batch-load subjects for subject_code and units only.
            // Do NOT select descriptive_title here — the column name varies
            // across deployments. Instead, use whatever was stored in the blob row.
            $subjectIds = $rows->pluck('subject_id')->filter()->unique()->values()->all();
            $facultyIds = $rows->pluck('faculty_id')->filter()->unique()->values()->all();

            $subjects = Subject::whereIn('id', $subjectIds)
                ->get(['id', 'subject_code', 'edp_code', 'units'])
                ->keyBy('id');

            $faculty = Faculty::whereIn('id', $facultyIds)
                ->get(['id', 'full_name', 'employee_id'])
                ->keyBy('id');

            return $rows
                ->map(function (array $row) use ($subjects, $faculty) {
                    $subject    = $subjects->get($row['subject_id'] ?? null);
                    $instructor = $faculty->get($row['faculty_id'] ?? null);

                    // descriptive_title: prefer what was frozen in the blob at
                    // archive time (survives subject table renames). Fall back to
                    // common column-name variants, then '—'.
                    $title = $row['descriptive_title']
                          ?? $row['subject_description']
                          ?? $row['title']
                          ?? '—';

                    return (object) [
                        'edp_code'          => $subject?->edp_code
                                                ?? $row['edp_code']
                                                ?? '—',
                        'subject_code'      => $subject?->subject_code
                                                ?? $row['subject_code']
                                                ?? '—',
                        'descriptive_title' => $title,
                        'section'           => $row['section'] ?? '—',
                        'instructor_name'   => $instructor?->full_name ?? 'Unassigned',
                        'units'             => $subject?->units
                                                ?? $row['units']
                                                ?? '—',
                        'day'               => $row['day'] ?? '—',
                        'start_time'        => $row['start_time'] ?? null,
                        'end_time'          => $row['end_time'] ?? null,
                        'status'            => $row['status'] ?? '—',
                        'department'        => $row['department'] ?? '—',
                    ];
                })
                ->sortBy([['section', 'asc'], ['day', 'asc']])
                ->values();
        }

        // ── Path B: soft-archived live rows ──────────────────────────────
        // Guard: return empty if the column has not been migrated yet.
        if ($this->selectedHistoricalSemester === 'soft_archived') {
            if (! Schema::hasColumn('schedules', 'is_archived')) {
                return collect();
            }

            return Schedule::with([
                    // Only select columns guaranteed to exist on subjects.
                    // descriptive_title is intentionally excluded for the same
                    // reason as Path A — use the schedule row's own stored value.
                    'subject:id,subject_code,edp_code,units',
                    'faculty:id,full_name,employee_id',
                ])
                ->where(function ($q) {
                    $q->where('is_archived', true)->orWhere('is_archived', 1);
                })
                ->get()
                ->map(function (Schedule $schedule) {
                    return (object) [
                        'edp_code'          => $schedule->subject?->edp_code ?? '—',
                        'subject_code'      => $schedule->subject?->subject_code ?? '—',
                        // For soft-archived live rows the schedule row itself
                        // does not carry descriptive_title, so show '—'.
                        // If your subjects table has this column under a different
                        // name, add it to the eager-load select above and reference it here.
                        'descriptive_title' => '—',
                        'section'           => $schedule->section ?? '—',
                        'instructor_name'   => $schedule->faculty?->full_name ?? 'Unassigned',
                        'units'             => $schedule->subject?->units ?? '—',
                        'day'               => $schedule->day ?? '—',
                        'start_time'        => $schedule->start_time,
                        'end_time'          => $schedule->end_time,
                        'status'            => $schedule->status ?? '—',
                        'department'        => $schedule->department ?? '—',
                    ];
                })
                ->sortBy([['section', 'asc'], ['day', 'asc']])
                ->values();
        }

        return collect();
    }

    /**
     * Render the component with all necessary data.
     *
     * archivedSemesterOptions is a #[Computed] property (cached per request).
     * archivedHistoryRecords is a plain method (no cache) so it always reflects
     * the current $selectedHistoricalSemester at the moment render() executes.
     * Both are passed explicitly so Blade can reference them as plain $variables.
     */
    public function render()
    {
        return view('livewire.global-settings', [
            // Guard: schedule_archives may not exist on a fresh database.
            'archives'                => Schema::hasTable('schedule_archives')
                                            ? DB::table('schedule_archives')->latest()->get()
                                            : collect(),
            'archivedSemesterOptions' => $this->archivedSemesterOptions,
            'archivedHistoryRecords'  => $this->archivedHistoryRecords(),
            'changeHistory'           => $this->changeHistory,
            'brickDuration'           => self::BRICK_DURATION,
            'lunchStart'              => self::LUNCH_START,
            'lunchEnd'                => self::LUNCH_END,
            'availableDays'           => Setting::ALL_SCHEDULE_DAYS,
        ])->layout('layouts.app');
    }
}