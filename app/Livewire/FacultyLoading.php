<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faculty;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;

class FacultyLoading extends Component
{
    public $search = '';
    public $selectedDepartment = '';
    public $subjectType = 'both';

    public function render()
    {
        $user = Auth::user();
        
        // Start the query
        $query = Faculty::query();

        // 1. If the user is a Dean or OIC, restrict to their department
        if ($user->role === 'dean') {
            // Deans only see their own department (e.g., 'CCS')
            $query->where('department', $user->department);
        } else {
            // Admin, Registrar, and Ass. Dean can filter by department
            if ($this->selectedDepartment) {
                $query->where('department', $this->selectedDepartment);
            }
        }

        // 2. SUBJECT TYPE FILTERING
        if ($this->subjectType !== 'both') {
            $query->where('teaching_type', $this->subjectType);
        }

        // 3. SEARCH
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        return view('livewire.faculty-loading', [
            'faculties' => $query->orderBy('full_name', 'asc')->get(),
            'departments' => Department::all()
        ])->layout('layouts.app');
    }
}