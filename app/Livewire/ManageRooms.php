<?php

namespace App\Livewire;

use App\Models\Room;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class ManageRooms extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Table & Functional State
    public $search = '';
    public $filterType = '';
    public $importFile;
    
    // Bulk Delete State
    public $selectedRooms = []; 
    public $selectAll = false;
    public $confirmingDeletion = false; 
    
    // Form State
    public $room_id; 
    public $room_name;
    public $type = 'LECTURE';
    public $capacity = 40;
    
    // UI State
    public $showModal = false;
    public $bulkOpen = false; 
    public $isEditMode = false;

    protected function rules() {
        return [
            'room_name' => 'required|unique:rooms,room_name,' . $this->room_id,
            'type'      => 'required|in:LECTURE,LAB,Lecture,Lab', // Flexible validation
            'capacity'  => 'required|integer|min:1',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Logic for Select All checkbox
    public function updatedSelectAll($value)
    {
        if ($value) {
            // Get all IDs from the current query to select them
            $this->selectedRooms = Room::orderBy('room_name', 'asc')->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedRooms = [];
        }
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['room_id', 'room_name', 'isEditMode', 'capacity', 'type']);
        $this->showModal = true;
    }

    public function saveRoom() 
    {
        $this->validate();

        Room::create([
            'room_name' => $this->room_name, 
            'type'      => strtoupper($this->type), // Fix: Always uppercase
            'capacity'  => $this->capacity
        ]);

        $this->showModal = false;
        $this->reset(['room_name', 'capacity', 'type']);
        session()->flash('message', 'Room added successfully.');
    }

    public function editRoom($id) 
    {
        $this->resetValidation();
        $room = Room::findOrFail($id);
        
        $this->room_id   = $room->id;
        $this->room_name = $room->room_name;
        $this->type      = strtoupper($room->type);
        $this->capacity  = $room->capacity;
        
        $this->isEditMode = true;
        $this->showModal  = true;
    }

    public function updateRoom()
    {
        $this->validate();

        $room = Room::findOrFail($this->room_id);
        $room->update([
            'room_name' => $this->room_name,
            'type'      => strtoupper($this->type), // Fix: Always uppercase
            'capacity'  => $this->capacity,
        ]);

        $this->showModal = false;
        $this->isEditMode = false;
        session()->flash('message', 'Room updated successfully!');
    }

    // Single Delete with Confirmation handled by Blade 'confirm()'
    public function deleteRoom($id) 
    {
        Room::findOrFail($id)->delete();
        session()->flash('message', 'Room deleted.');
    }

    // Bulk Delete Action
    public function deleteSelected()
{
    // 1. Check if anything is actually selected
    if (empty($this->selectedRooms)) {
        $this->confirmingDeletion = false;
        return;
    }

    // 2. Perform the deletion
    \App\Models\Room::whereIn('id', $this->selectedRooms)->delete();

    // 3. Clear the state so the UI resets
    $this->selectedRooms = [];
    $this->selectAll = false;
    $this->confirmingDeletion = false;

    // 4. Send feedback to the user
    session()->flash('message', 'Selected rooms have been removed successfully.');
}

    public function importRooms()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt|max:10240' 
        ]);

        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        fgetcsv($file); 

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            if (isset($row[0])) {
                Room::updateOrCreate(
                    ['room_name' => $row[0]], 
                    [
                        'capacity' => $row[1] ?? 40,        
                        'type'     => strtoupper($row[2] ?? 'LECTURE'), // Fix: Always uppercase
                    ]
                );
                $count++;
            }
        }

        fclose($file);
        $this->bulkOpen = false; 
        $this->reset('importFile');
        session()->flash('message', "Successfully imported $count rooms!");
    }

    public function render() 
    {
        $query = Room::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('room_name', 'like', '%' . $this->search . '%')
                  ->orWhere('type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return view('livewire.manage-rooms', [
            'rooms' => $query->orderBy('room_name', 'asc')->paginate(10)
        ]);
    }
}