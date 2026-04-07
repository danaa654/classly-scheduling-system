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
    
    // Form State
    public $room_id, $room_name;
    public $type = 'Lecture';
    public $capacity = 40;
    
    // UI State
    public $showModal = false;
    public $bulkOpen = false; // Added to control Bulk Modal visibility
    public $isEditMode = false;

    protected function rules() {
        return [
            'room_name' => 'required|unique:rooms,room_name,' . $this->room_id,
            'type'      => 'required|in:Lecture,Lab',
            'capacity'  => 'required|integer|min:1',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['room_id', 'room_name', 'isEditMode']);
        $this->showModal = true;
    }

    public function saveRoom() 
    {
        $this->validate();

        Room::create([
            'room_name' => $this->room_name, 
            'type'      => $this->type, 
            'capacity'  => $this->capacity
        ]);

        $this->showModal = false;
        $this->reset(['room_name']);
        session()->flash('message', 'Room added successfully.');
    }

    public function editRoom($id) 
    {
        $this->resetValidation();
        $room = Room::findOrFail($id);
        
        $this->room_id   = $room->id;
        $this->room_name = $room->room_name;
        $this->type      = $room->type;
        $this->capacity  = $room->capacity;
        
        $this->isEditMode = true;
        $this->showModal  = true;
    }

    public function updateRoom()
    {
        $this->validate();

        if ($this->room_id) {
            $room = Room::find($this->room_id);
            $room->update([
                'room_name' => $this->room_name,
                'type'      => $this->type,
                'capacity'  => $this->capacity,
            ]);

            $this->showModal = false;
            $this->isEditMode = false;
            $this->reset(['room_name', 'room_id']);
            
            session()->flash('message', 'Room updated successfully!');
        }
    }

    public function deleteRoom($id) 
    {
        Room::findOrFail($id)->delete();
        session()->flash('message', 'Room deleted.');
    }

    /**
     * Functional Bulk Import
     */
    public function importRooms()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt|max:10240' // Limit to 10MB
        ]);

        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');

        // Skip header row
        fgetcsv($file); 

        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            // CSV structure: room_name, type, capacity
            if (isset($row[0])) {
                Room::updateOrCreate(
                    ['room_name' => $row[0]], // Match by room name
                    [
                        'type'     => $row[1] ?? 'Lecture',
                        'capacity' => $row[2] ?? 40,
                    ]
                );
                $count++;
            }
        }

        fclose($file);

        $this->bulkOpen = false; // Close modal
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
            'rooms' => $query->latest()->paginate(10)
        ]);
    }
}