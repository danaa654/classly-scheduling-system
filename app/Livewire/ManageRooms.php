<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GeneralNotification;

class ManageRooms extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $filterType = '';
    public $importFile;
    public $importPreview = []; 
    
    public $selectedRooms = []; 
    public $selectAll = false;
    public $confirmingDeletion = false; 
    
    public $room_id; 
    public $room_name;
    public $type = 'LECTURE';
    public $capacity = 40;
    
    public $showModal = false;
    public $bulkOpen = false; 
    public $isEditMode = false;

    protected function messages() {
        return [
            'room_name.unique' => '⚠️ This room name is already being used.',
            'importFile.mimes' => '⚠️ The selected file is not a valid room file. Please upload a CSV.',
        ];
    }

    protected function rules() {
        return [
            'room_name' => 'required|unique:rooms,room_name,' . $this->room_id,
            'type'      => 'required|in:LECTURE,LAB',
            'capacity'  => 'required|integer|min:1',
        ];
    }

    // Fix: Clear selection when searching to avoid accidental deletes
    public function updatedSearch() { $this->resetPage(); $this->selectedRooms = []; $this->selectAll = false; }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRooms = Room::where('room_name', 'like', '%' . $this->search . '%')
                ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
                ->pluck('id')
                ->map(fn($id) => (string)$id) // Ensure string IDs for checkbox matching
                ->toArray();
        } else {
            $this->selectedRooms = [];
        }
    }

    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt|max:10240',
        ]);

        $path = $this->importFile->getRealPath();
        $fileContent = file($path);
        $data = array_map('str_getcsv', $fileContent);

        $headers = array_map(function($header) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header)));
        }, $data[0]);

        // CHECK FOR WRONG FILE TYPES (Warning Toast)
        if (in_array('employee_id', $headers) || in_array('subject_code', $headers)) {
            $type = in_array('employee_id', $headers) ? 'FACULTY' : 'SUBJECT';
            $this->reset(['importFile', 'importPreview']);
            
            return $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => "File Mismatch: This is a $type file!",
                'detail'  => "Please upload a Room CSV instead."
            ]);
        }

        // Invalid Headers (Warning Toast)
        $required = ['room_name', 'capacity', 'type'];
        foreach($required as $key) {
            if (!in_array($key, $headers)) {
                $this->reset(['importFile', 'importPreview']);
                return $this->dispatch('toast', [
                    'type'    => 'error',
                    'message' => 'Invalid Format',
                    'detail'  => "Missing '$key' column."
                ]);
            }
        }

        // Preview Generation...
        $this->importPreview = [];
        foreach (array_slice($data, 1) as $row) {
            if (empty($row) || count($row) < 3) continue;
            $this->importPreview[] = [
                'room_name' => trim($row[0]),
                'capacity'  => trim($row[1]),
                'type'      => strtoupper(trim($row[2])),
                'status'    => Room::where('room_name', trim($row[0]))->exists() ? 'DUPLICATE' : 'READY',
            ];
        }
    }

    public function processImport()
    {
        $count = 0;
        foreach ($this->importPreview as $data) {
            if ($data['status'] === 'READY') {
                Room::create([
                    'room_name' => $data['room_name'],
                    'capacity'  => $data['capacity'],
                    'type'      => $data['type'],
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            // Notify others with account info and date (handled by the Notification class)
            $this->notifyManagement("has imported $count new rooms to the registry.", 'room_import');
            
            $this->dispatch('roomImported'); // Refresh components

            // Success Toast for the Registrar
            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Import Successful',
                'detail'  => "Added $count rooms to the system."
            ]);
        }
        $this->reset(['importFile', 'importPreview', 'bulkOpen']);
    }

    public function deleteSelected()
    {
        if (empty($this->selectedRooms)) {
            return $this->dispatch('toast', ['type' => 'info', 'message' => 'No rooms selected.']);
        }

        $count = count($this->selectedRooms);
        Room::whereIn('id', $this->selectedRooms)->delete();
        
        // Notify others
        $this->notifyManagement("deleted $count rooms from the registry.", 'room_delete');
        $this->dispatch('roomImported');

        $this->reset(['selectedRooms', 'selectAll', 'confirmingDeletion']);
        
        // Delete Toast
        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => 'Rooms Deleted',
            'detail'  => "Successfully removed $count records."
        ]);
    }
    private function notifyManagement($message, $type)
{
    $managementRoles = ['dean', 'oic', 'ass.dean', 'registrar', 'admin'];

    $usersToNotify = User::whereIn('role', $managementRoles)
        ->where('id', '!=', auth()->id()) 
        ->get();

    if ($usersToNotify->isNotEmpty()) {
        Notification::send($usersToNotify, new GeneralNotification([
            'title' => 'Room Registry Update',
            'message' => $message,
            'type' => $type,
            'url' => url('/manage-rooms'),
            'sender_name' => auth()->user()->name, 
        ]));
    }
}

    // Modal & CRUD methods...
    public function openModal() { $this->resetValidation(); $this->reset(['room_id', 'room_name', 'isEditMode', 'capacity', 'type']); $this->showModal = true; }

    public function saveRoom() 
{
    $this->validate();
    
    Room::create([
        'room_name' => $this->room_name, 
        'type'      => strtoupper($this->type),
        'capacity'  => $this->capacity
    ]);

    // Notify others
    $this->notifyManagement("added a new room: {$this->room_name}", 'room_add');
    $this->dispatch('roomImported');

    $this->showModal = false;

    // Success Message for the person doing the action
    return $this->dispatch('swal', [
        'title' => 'Room Created!',
        'text' => "Room {$this->room_name} has been added to the system.",
        'icon' => 'success'
    ]);
}
    public function editRoom($id) 
    {
        $this->resetValidation();
        $room = Room::findOrFail($id);
        $this->room_id = $room->id; $this->room_name = $room->room_name; $this->type = $room->type; $this->capacity = $room->capacity; $this->isEditMode = true; $this->showModal = true;
    }

    public function updateRoom()
    {
        $this->validate();
        Room::findOrFail($this->room_id)->update(['room_name' => $this->room_name, 'type' => strtoupper($this->type), 'capacity' => $this->capacity]);
        $this->showModal = false; $this->isEditMode = false;
        $this->dispatch('swal', title: 'Room Updated', icon: 'success');
    }

    public function deleteRoom($id) 
{
    $room = Room::findOrFail($id);
    $name = $room->room_name;
    $room->delete();

    // Notify others
    $this->notifyManagement("deleted room: $name", 'room_delete');
    $this->dispatch('roomImported');

    // Success Message for the person doing the action
    return $this->dispatch('swal', [
        'title' => 'Room Deleted',
        'text' => "The record for $name has been removed.",
        'icon' => 'warning'
    ]);
}

    public function render() 
    {
        $query = Room::query()
            ->when($this->search, fn($q) => $q->where('room_name', 'like', '%' . $this->search . '%'))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType));

        return view('livewire.manage-rooms', [
            'rooms' => $query->orderBy('room_name', 'asc')->paginate(10)
        ]);
    }
}