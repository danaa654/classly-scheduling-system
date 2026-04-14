<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Room;    
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DeanDashboard extends Component
{
    public $totalFaculty, $totalSubjects, $totalRooms;
    public $department, $todayString, $daysInMonth, $todayDay;

    public function mount()
    {
        $user = Auth::user();
        $this->department = $user->department; // e.g., 'CCS'

        // Department-Specific Counts
        $this->totalFaculty = Faculty::where('department', $this->department)->count();
        $this->totalSubjects = Subject::where('department', $this->department)->count();
        
        // Rooms are usually shared, but you can filter if you have department-specific rooms
        $this->totalRooms = Room::count(); 

        // Calendar Data
        $now = Carbon::now();
        $this->todayString = $now->format('F d, Y');
        $this->daysInMonth = $now->daysInMonth;
        $this->todayDay = $now->day;
    }


    public function render()
    {
        return view('livewire.dean-dashboard', [
            
        ]);
    }
}