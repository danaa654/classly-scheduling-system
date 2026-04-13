<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Room;

class AssistantDeanDashboard extends Component
{
    public function render()
    {
        return view('livewire.assistant-dean-dashboard', [
            // Pulling ALL faculty members as requested
            'facultyMembers' => Faculty::all(), 
            
            // Stats for the dashboard cards
            'minorSubjectsCount' => Subject::where('is_minor', true)->count(),
            'totalRooms' => Room::count(),
            'totalFacultyCount' => Faculty::count(),
            
            // Layout helpers
            'departments' => ['CCS', 'CTE', 'COC', 'SHTM'],
            'daysInMonth' => now()->daysInMonth,
            'todayDay' => now()->day,
        ]);
    }
}