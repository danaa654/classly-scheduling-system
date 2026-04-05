<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class AdminDashboard extends Component
{
    use WithFileUploads;

    public $facultyFile;

    /**
     * Security Gate
     * This runs before anything else loads on the dashboard.
     */
    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }
    }

    public function importFaculty()
    {
        $this->validate([
            'facultyFile' => 'required|mimes:csv,xlsx|max:10240', // 10MB Max
        ]);

        // Logic to process the file will go here later
        session()->flash('message', 'Faculty data imported successfully!');
    }

    public function render()
    {
        // Explicitly ensuring it uses your main app layout
        return view('livewire.admin-dashboard')
            ->layout('layouts.app');
    }
}