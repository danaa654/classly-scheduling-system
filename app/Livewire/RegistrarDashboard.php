<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;

// Placeholder model for scheduling core, use your actual Schedule/Section model
// use App\Models\ClassSchedule; 

class RegistrarDashboard extends Component
{
    public function render()
    {
        // Fetch key stats
        $totalRooms = Room::count();
        $totalUsers = User::count();
        
        // Example logic for daily classes. Update this with your actual scheduling table query.
        $scheduledTodayCount = 0; 
        // $scheduledTodayCount = ClassSchedule::whereDate('scheduled_date', Carbon::today())->count(); 

        $todayString = Carbon::today()->format('F j, Y');

        return view('livewire.registrar-dashboard', [
            'totalRooms' => $totalRooms,
            'totalUsers' => $totalUsers,
            'scheduledTodayCount' => $scheduledTodayCount,
            'todayString' => $todayString,
        ]);
    }
}