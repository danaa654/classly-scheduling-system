<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use Illuminate\Support\Facades\Auth;

class FacultyLoading extends Component
{
    public $search = '';

    public function render()
    {
        $user = Auth::user();
        
        // Start the query
        $query = Faculty::query();

        // 1. If the user is a Dean or OIC, restrict to their department
        if ($user->isDepartmentOfficial()) {
            $query->where('department', $user->department);
        }

        // 2. Apply search filter if user is typing
        if (!empty($this->search)) {
            $query->where('full_name', 'like', '%' . $this->search . '%');
        }

        return view('livewire.faculty-loading', [
            'faculties' => $query->orderBy('full_name', 'asc')->get()
        ])->layout('layouts.app');
    }
}