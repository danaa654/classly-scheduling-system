<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    public $totalRooms, $totalUsers, $totalFaculty, $activeUsersCount;
    public $todayString, $currentMonth, $daysInMonth, $todayDay;

    public function mount()
    {
        $this->totalRooms = Room::count();
        $this->totalUsers = User::count();
        $this->totalFaculty = Faculty::count();
        
        // Logical check for active users (last 24 hours activity or just existing)
        $this->activeUsersCount = User::count(); 

        // Calendar Data
        $now = Carbon::now();
        $this->todayString = $now->format('F d, Y');
        $this->currentMonth = $now->format('F Y');
        $this->daysInMonth = $now->daysInMonth;
        $this->todayDay = $now->day;
    }

    public function render()
    {
        return view('livewire.admin-dashboard')->layout('layouts.app');
    }
}