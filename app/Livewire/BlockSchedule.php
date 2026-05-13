<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Setting;
use Carbon\Carbon;

class BlockSchedule extends Component
{
    public $selectedDepartment = 'IT';
    public $selectedYear = '1';
    public $selectedSection = 'A';
    public $schoolYear = '2026-2027';
    public $semester = '1st';
    public $semesterName = 'First Semester 2026-2027';

    protected $listeners = [
        'settings-updated' => 'loadSettings',
        'refreshBlockSchedule' => '$refresh',
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->schoolYear = Setting::where('key', 'school_year')->first()?->value ?? '2026-2027';
        $this->semester = Setting::where('key', 'semester')->first()?->value ?? '1st';
        $this->semesterName = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
    }

    public function getDepartmentName($code)
    {
        $departments = [
            'IT' => 'Information Technology',
            'ACT' => 'Associate in Computer Technology',
            'ED' => 'Education',
            'HM' => 'Hospitality Management',
            'TM' => 'Tourism Management',
            'FB' => 'Forensic Biology',
            'LD' => 'Lie Detection',
            'QD' => 'Questioned Document',
        ];
        return $departments[$code] ?? $code;
    }

    public function render()
    {
        // =====================================================
        // Get all schedules for selected section
        // =====================================================
        $allSchedules = Schedule::query()
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->where('section', $this->selectedSection)
            ->with(['subject', 'room', 'faculty'])
            ->get();

        // =====================================================
        // Filter by department AND year_level
        // =====================================================
        $filteredSchedules = $allSchedules->filter(function ($schedule) {
            // Skip if no subject relationship exists
            if (!$schedule->subject) {
                return false;
            }

            // Match both department and year level
            return (
                $schedule->subject->department === $this->selectedDepartment
                || $schedule->subject->major === $this->selectedDepartment
            ) && (int)$schedule->subject->year_level === (int)$this->selectedYear;
        })->values();

        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $scheduleRows = $filteredSchedules
            ->groupBy(function ($schedule) {
                return $schedule->pairing_key ?: implode('|', [
                    $schedule->subject_id,
                    $schedule->room_id,
                    $schedule->start_time,
                    $schedule->end_time,
                ]);
            })
            ->map(function ($group) use ($dayOrder) {
                $first = $group->first();
                $days = $group->pluck('day')
                    ->filter()
                    ->unique()
                    ->sortBy(fn (string $day) => array_search($day, $dayOrder, true))
                    ->values()
                    ->all();

                return (object) [
                    'subject' => $first->subject,
                    'room' => $first->room,
                    'faculty' => $first->faculty,
                    'start_time' => $first->start_time,
                    'end_time' => $first->end_time,
                    'days' => $days,
                    'day_display' => implode(' / ', $days),
                    'sort_day' => $days[0] ?? $first->day,
                ];
            })
            ->sort(function ($a, $b) use ($dayOrder) {
                $dayCompare = array_search($a->sort_day, $dayOrder, true) <=> array_search($b->sort_day, $dayOrder, true);

                return $dayCompare !== 0
                    ? $dayCompare
                    : strcmp((string) $a->start_time, (string) $b->start_time);
            })
            ->values();

        $schedules = $filteredSchedules->groupBy('day');

        $departmentName = $this->getDepartmentName($this->selectedDepartment);

        return view('livewire.block-schedule', [
            'schedules' => $schedules,
            'scheduleRows' => $scheduleRows,
            'departmentName' => $departmentName,
        ]);
    }
}
