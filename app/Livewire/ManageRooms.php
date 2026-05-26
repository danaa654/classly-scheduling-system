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
    public $specialization = '';
    public $floor = '';
    public $department_owner = '';
    public $allowed_departments = [];
    public bool $is_specialized = false;
    
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
            'type'      => 'required|in:LECTURE,LAB,Lecture,Lab',
            'capacity'  => 'required|integer|min:1',
            'specialization' => 'nullable|string',
            'floor' => 'nullable|string',
            'department_owner' => 'nullable|string|max:30',
            'allowed_departments' => 'array',
            'allowed_departments.*' => 'string',
            'is_specialized' => 'boolean',
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

        // Invalid Headers (Warning Toast) - Check for required columns
        $required = ['room_name', 'capacity', 'specialization', 'floor'];
        $roomTypeCol = null;
        
        // Support both 'type' and 'room_type' column names
        if (in_array('type', $headers)) {
            $roomTypeCol = 'type';
        } elseif (in_array('room_type', $headers)) {
            $roomTypeCol = 'room_type';
        }

        if (!$roomTypeCol) {
            $this->reset(['importFile', 'importPreview']);
            return $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Invalid Format',
                'detail'  => "Missing 'type' or 'room_type' column."
            ]);
        }

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

        // Get column indices
        $roomNameIdx = array_search('room_name', $headers);
        $capacityIdx = array_search('capacity', $headers);
        $roomTypeIdx = array_search($roomTypeCol, $headers);
        $specializationIdx = array_search('specialization', $headers);
        $floorIdx = array_search('floor', $headers);

        // Preview Generation...
        $this->importPreview = [];
        foreach (array_slice($data, 1) as $row) {
            if (empty($row) || collect($row)->every(fn ($value) => trim((string) $value) === '')) continue;
            
            $roomName = trim($row[$roomNameIdx] ?? '');
            $rawRoomType = trim($row[$roomTypeIdx] ?? '');
            $roomType = strtoupper($rawRoomType);
            $capacity = trim($row[$capacityIdx] ?? '');
            $specialization = $specializationIdx !== false ? trim($row[$specializationIdx] ?? '') : '';
            $floor = $floorIdx !== false ? trim($row[$floorIdx] ?? '') : '';
            
            // Normalize room type values
            if ($roomType === 'LECTURE' || strtoupper($roomType) === 'LECTURE') {
                $roomType = 'LECTURE';
            } elseif ($roomType === 'LAB' || strtoupper($roomType) === 'LABORATORY') {
                $roomType = 'LAB';
            }

            $validationErrors = [];

            if ($roomName === '') {
                $validationErrors[] = 'Missing room name';
            }

            if ($rawRoomType === '' || !in_array($roomType, ['LECTURE', 'LAB'], true)) {
                $validationErrors[] = 'Invalid room type';
            }

            if ($specialization === '') {
                $validationErrors[] = 'Missing specialization';
            }

            if (!is_numeric($capacity) || (int) $capacity <= 0) {
                $validationErrors[] = 'Invalid capacity';
            }

            $status = Room::where('room_name', $roomName)->exists() ? 'DUPLICATE' : 'READY';

            if ($validationErrors) {
                $status = 'INVALID';
            }

            $this->importPreview[] = [
                'room_name' => $roomName,
                'capacity'  => $capacity,
                'type'      => $roomType,
                'specialization' => $specialization,
                'floor'     => $floor,
                'status'    => $status,
                'errors'    => implode(', ', $validationErrors),
            ];
        }

        if (empty($this->importPreview)) {
            $this->reset(['importFile', 'importPreview']);
            return $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'No valid data found',
                'detail'  => "The CSV file appears to be empty or contains only headers."
            ]);
        }
    }

    public function processImport()
    {
        if (collect($this->importPreview)->contains(fn ($data) => $data['status'] === 'INVALID')) {
            return $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Import blocked',
                'detail'  => 'Fix invalid room rows before importing.'
            ]);
        }

        $count = 0;
        foreach ($this->importPreview as $data) {
            if ($data['status'] === 'READY') {
                Room::create([
                    'room_name' => $data['room_name'],
                    'capacity'  => (int)$data['capacity'],
                    'type'      => $data['type'],
                    'room_type' => $data['type'],
                    'specialization' => $data['specialization'],
                    'floor'     => $data['floor'],
                    'department_owner' => $this->inferDepartmentOwner($data['specialization'], $data['room_name']),
                    'is_specialized' => strtoupper($data['type']) === 'LAB' || $data['specialization'] !== '',
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
        $managementRoles = ['dean', 'oic', 'associate_dean', 'registrar', 'admin'];

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

    private function inferDepartmentOwner(?string $specialization, ?string $roomName): ?string
    {
        $text = strtoupper(trim((string) $specialization.' '.(string) $roomName));

        return match (true) {
            str_contains($text, 'CCS') || str_contains($text, 'IT') || str_contains($text, 'ACT') || str_contains($text, 'ICT') || str_contains($text, 'COMPUTER') => 'CCS',
            str_contains($text, 'CTE') || str_contains($text, 'ED') || str_contains($text, 'EDUCATION') => 'CTE',
            str_contains($text, 'SHTM') || str_contains($text, 'HM') || str_contains($text, 'TM') || str_contains($text, 'HRM') || str_contains($text, 'KITCHEN') => 'SHTM',
            str_contains($text, 'COC') || str_contains($text, 'FB') || str_contains($text, 'LD') || str_contains($text, 'QD') || str_contains($text, 'FORENSIC') => 'COC',
            default => null,
        };
    }

    // Modal & CRUD methods...
    public function openModal() { $this->resetValidation(); $this->reset(['room_id', 'room_name', 'isEditMode', 'capacity', 'type', 'specialization', 'floor', 'department_owner', 'allowed_departments', 'is_specialized']); $this->type = 'LECTURE'; $this->capacity = 40; $this->allowed_departments = []; $this->is_specialized = false; $this->showModal = true; }

    public function saveRoom() 
    {
        $this->validate();
        
        Room::create([
            'room_name' => $this->room_name, 
            'type'      => strtoupper($this->type),
            'room_type' => strtoupper($this->type),
            'capacity'  => $this->capacity,
            'specialization' => $this->specialization,
            'floor'     => $this->floor,
            'department_owner' => $this->department_owner ?: null,
            'allowed_departments' => array_values($this->allowed_departments ?? []),
            'is_specialized' => (bool) $this->is_specialized,
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
        $this->room_id = $room->id; 
        $this->room_name = $room->room_name; 
        $this->type = $room->type; 
        $this->capacity = $room->capacity; 
        $this->specialization = $room->specialization ?? '';
        $this->floor = $room->floor ?? '';
        $this->department_owner = $room->department_owner ?? '';
        $this->allowed_departments = $room->allowed_departments ?? [];
        $this->is_specialized = (bool) $room->is_specialized;
        $this->isEditMode = true; 
        $this->showModal = true;
    }

    public function updateRoom()
    {
        $this->validate();
        Room::findOrFail($this->room_id)->update([
            'room_name' => $this->room_name, 
            'type' => strtoupper($this->type), 
            'room_type' => strtoupper($this->type),
            'capacity' => $this->capacity,
            'specialization' => $this->specialization,
            'floor' => $this->floor,
            'department_owner' => $this->department_owner ?: null,
            'allowed_departments' => array_values($this->allowed_departments ?? []),
            'is_specialized' => (bool) $this->is_specialized,
        ]);
        $this->showModal = false; 
        $this->isEditMode = false;
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
            ->when($this->search, fn($q) => $q->where('room_name', 'like', '%' . $this->search . '%')
                ->orWhere('specialization', 'like', '%' . $this->search . '%')
                ->orWhere('floor', 'like', '%' . $this->search . '%'))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType));

        return view('livewire.manage-rooms', [
            'rooms' => $query->orderBy('room_name', 'asc')->paginate(10)
        ]);
    }
}
