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
        $allSchedules = Schedule::where('section', $this->selectedSection)
            ->with(['subject', 'room'])
            ->get();

        // =====================================================
        // Filter by department AND year_level
        // =====================================================
        $schedules = $allSchedules->filter(function ($schedule) {
            // Skip if no subject relationship exists
            if (!$schedule->subject) {
                return false;
            }

            // Match both department and year level
            return ($schedule->subject->department === $this->selectedDepartment && 
                    (int)$schedule->subject->year_level === (int)$this->selectedYear);
        })
        ->groupBy('day'); // Group by day for display

        $departmentName = $this->getDepartmentName($this->selectedDepartment);

        return view('livewire.block-schedule', [
            'schedules' => $schedules,
            'departmentName' => $departmentName,
        ]);
    }
}
