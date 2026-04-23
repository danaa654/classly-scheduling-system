<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use App\Traits\HandlesNotifications;

class AssistantDeanDashboard extends Component
{
    use HandlesNotifications;
    
    public function render()
    {
        // For the Associate Dean, we want a global overview of the entire institution.
        return view('livewire.assistant-dean-dashboard', [
            
            // Accessing ALL faculty across every department
            'facultyMembers' => Faculty::with('user')->latest()->get(), 
            
            // Global Statistics (Across all Departments)
            'minorSubjectsCount' => Subject::where('is_minor', true)->count(),
            'totalRooms' => Room::count(),
            'totalFacultyCount' => Faculty::count(),
            'totalUsers' => User::count(), // Added to show total system accounts
            
            // Departmental Overview (Used for UI chips or filtering)
            'departments' => ['CCS', 'CTE', 'COC', 'SHTM'],
            
            // Calendar & Date Helpers
            'daysInMonth' => now()->daysInMonth,
            'todayDay' => now()->day,
            'currentMonthName' => now()->format('F Y'),
        ]);
    }
}