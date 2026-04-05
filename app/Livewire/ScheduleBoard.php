<?php
namespace App\Livewire;

use Livewire\Component;

class ScheduleBoard extends Component
{
    public function render()
    {
        // This links the logic to the UI code I gave you earlier
        return view('livewire.schedule-board')
                ->layout('layouts.app'); // Ensure this matches your main layout file
    }
}