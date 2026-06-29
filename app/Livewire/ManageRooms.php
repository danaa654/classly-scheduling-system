<?php

namespace App\Livewire;

use App\Services\RoomCapacityService;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;  
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use App\Notifications\GeneralNotification;

class ManageRooms extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $filterType = '';
    public string $viewMode = 'all'; // 'all' | 'my_rooms'
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

      // ─── Assign Subjects Modal ─────────────────────────────────────────────
    public bool   $showAssignModal     = false;
    public ?int   $assigningRoomId     = null;
    public array  $assigningRoomData   = [];   // Serialised room metadata for Blade
    public array  $modalSubjects       = [];   // Smart-filtered eligible subjects
    public array  $selectedSubjectIds  = [];   // Checkbox-bound IDs (Livewire stores as strings)
    public float  $selectedWeeklyHours  = 0.0;  // Live running total of selected hours
    public string $capacityWarning      = '';   // Non-empty string → soft warning shown in modal
    public string $capacityError        = '';   // Non-empty string → hard error blocking save

    /**
     * Weekly hours from subjects already bound to this room that are NOT visible
     * in the current modal (different dept/type filter, or Practicum excluded).
     * Added to the selected-subject sum so the capacity meter is always accurate.
     */
    public float $currentRoomHiddenLoad = 0.0;

    /**
     * Weekly hours already saved for subjects visible in the modal AND pre-ticked
     * (already assigned to this room). Stored so the meter can show "current load"
     * separately from "pending additions."
     */
    public float $currentRoomVisibleLoad = 0.0;

    // Maximum teaching hours one room should absorb per week (used for validation)
    // Weekly room capacity is now dynamic — always derived from Global Settings
    // via RoomCapacityService::getWeeklyCapacity(). No hardcoded constant here.
    
    public $showModal = false;
    public $bulkOpen = false; 
    public $isEditMode = false;

    /** Tracks which room rows are currently expanded to show their allocated subjects. */
    public $expandedRooms = [];

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

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'all' ? 'my_rooms' : 'all';
        $this->resetPage();
    }

    /**
     * Returns the department-specific keyword list used to match specialized
     * rooms (lab OR lecture) for dean/oic roles when the "My Rooms" filter is active.
     */
    private function getDeptKeywordsForDepartment(string $dept): array
    {
        return match ($dept) {
            'CCS'  => ['IT', 'ACT', 'ICT', 'CCS', 'COMPUTER', 'WORKSHOP'],
            'CTE'  => ['CTE', 'ED', 'EDUCATION', 'TEACHING'],
            'COC'  => ['COC', 'FB', 'LD', 'QD', 'FORENSIC', 'CRIM'],
            'SHTM' => ['SHTM', 'HM', 'TM', 'HOSPITALITY', 'KITCHEN'],
            default => [],
        };
    }

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

    public function openAssignModal(int $roomId): void
    {
        $this->resetAssignModal();
 
        $room         = Room::findOrFail($roomId);
        $roomType     = strtoupper(trim($room->type ?? ''));
        $isLabRoom    = in_array($roomType, ['LAB', 'LABORATORY'], true);
        $groupKey     = $this->detectSpecializationGroupKey($room);
        $allowedDepts = $groupKey ? self::roomSpecializationGroups()[$groupKey] : [];
 
        // ── Store room metadata for the Blade view ──────────────────────────
        $this->assigningRoomId  = $room->id;
        $this->assigningRoomData = [
            'id'               => $room->id,
            'room_name'        => $room->room_name,
            'type'             => $roomType,
            'specialization'   => $room->specialization ?? '',
            'department_owner' => $room->department_owner ?? '',
            'capacity'         => $room->capacity,
            // Human-readable label shown in the modal filter badge
            'filter_label'     => $allowedDepts
                ? implode(' / ', $allowedDepts) . ' · ' . ucfirst(strtolower($roomType)) . ($isLabRoom ? ' · MAJOR + overrides' : ' · MINOR + overrides')
                : ucfirst(strtolower($roomType)) . ($isLabRoom ? ' (All Departments) · MAJOR + overrides' : ' (All Departments) · MINOR + overrides'),
        ];
 
        $query = Subject::activeTerm()
            // Practicum / OJT subjects have no physical room — never show in Manage Rooms.
            ->where(function ($q) {
                $q->where('is_practicum', false)->orWhereNull('is_practicum');
            })
            ->orderBy('department')
            ->orderBy('year_level')
            ->orderBy('section')
            ->orderBy('subject_code');
 
        // ── TIER 1: Room-type filtering (LAB vs LECTURE) — override-aware ──
        //
        // Default routing:  LAB  → Major subjects | LECTURE → Minor subjects
        // Override routing: a subject may opt OUT of the default via preferred_room_type:
        //   Major  + preferred_room_type = 'LECTURE' → show in LECTURE rooms instead
        //   Minor  + preferred_room_type = 'LAB'     → show in LAB rooms instead
        //
        // So:
        //   LAB room   accepts: type=Major (default) OR (type=Minor AND preferred_room_type='LAB')
        //   LECTURE room accepts: type=Minor (default) OR (type=Major AND preferred_room_type='LECTURE')
        $isLabRoom = in_array($roomType, ['LAB', 'LABORATORY'], true);

        if ($isLabRoom) {
            $query->where(function ($q) {
                // Default: Major subjects (go to lab by default)
                $q->where('type', 'Major')
                  // Override: Minor subjects that explicitly request a lab
                  ->orWhere(function ($inner) {
                      $inner->where('type', 'Minor')
                            ->where('preferred_room_type', 'LAB');
                  });
            });
        } else {
            $query->where(function ($q) {
                // Default: Minor subjects (go to lecture by default)
                $q->where('type', 'Minor')
                  // Override: Major subjects that explicitly request a lecture room
                  ->orWhere(function ($inner) {
                      $inner->where('type', 'Major')
                            ->where('preferred_room_type', 'LECTURE');
                  });
            });
        }
 
        if ($allowedDepts && ! empty($allowedDepts)) {
            $query->where(function ($q) use ($allowedDepts) {
                // Match 1: Subject's department is in the allowed departments
                $q->whereIn('department', $allowedDepts)
                  // Match 2: Subject's major is in the allowed departments
                  ->orWhereIn('major', $allowedDepts)
                  // Match 3: Subject's specialization contains keywords from allowed depts
                  ->orWhere(function ($inner) use ($allowedDepts) {
                      foreach ($allowedDepts as $dept) {
                          $inner->orWhere('specialization', 'like', "%{$dept}%");
                      }
                  });
            });
        }

        $this->modalSubjects = $query
            ->with('preferredRoom')   // avoids N+1 when rendering the "claimed by" badge
            ->get()
            ->map(fn (Subject $s) => [
                'id'                  => $s->id,
                'edp_code'            => $s->edp_code,
                'subject_code'        => $s->subject_code,
                'description'         => $s->description,
                'department'          => $s->department,
                'major'               => $s->major,
                'year_level'          => $s->year_level,
                'section'             => $s->section ?? '—',
                'units'               => (int) $s->units,
                'duration_hours'      => (float) $s->duration_hours,
                'meetings_per_week'   => (int) ($s->meetings_per_week ?? 1),
                // weekly_hours = duration_hours only (total weekly room block).
                // meetings_per_week splits the block across days - it does NOT
                // multiply room usage. SA101 4h/2x => 2h Mon + 2h Wed = 4h total.
                'weekly_hours'        => round((float) $s->duration_hours, 1),
                'requires_lab'        => (bool) $s->requires_lab,
                'subject_type'        => (string) ($s->type ?? 'Major'),  // 'Major' or 'Minor'
                'preferred_room_type' => (string) ($s->preferred_room_type ?? ''),
                // Preferred-room data — used to render the "⚠️ Prefers Room: X" badge
                // when the subject is already bound to a *different* room.
                'preferred_room_id'   => $s->preferred_room_id,
                'preferred_room_name' => $s->preferredRoom?->room_name,
            ])
            ->toArray();
 
        // ── Pre-tick subjects already bound to this room ─────────────────────
        $this->selectedSubjectIds = Subject::activeTerm()
            ->where('preferred_room_id', $roomId)
            // Practicum subjects are excluded from room management entirely.
            ->where(function ($q) {
                $q->where('is_practicum', false)->orWhereNull('is_practicum');
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id) // Livewire checkboxes store strings
            ->toArray();

        // ── Calculate hidden load (assigned subjects not visible in modal) ──────
        // Some subjects assigned to this room may not appear in modalSubjects because
        // they belong to a different dept/type filter. We still need their hours in
        // the capacity meter so the total is always accurate.
        $modalSubjectIds = collect($this->modalSubjects)->pluck('id')->all();
        $this->currentRoomHiddenLoad = (float) Subject::activeTerm()
            ->where('preferred_room_id', $roomId)
            ->when(!empty($modalSubjectIds), fn ($q) => $q->whereNotIn('id', $modalSubjectIds))
            ->where(function ($q) {
                $q->where('is_practicum', false)->orWhereNull('is_practicum');
            })
            ->sum('duration_hours');

        // ── Pre-selected visible load (subjects in modal already bound to this room) ─
        $this->currentRoomVisibleLoad = (float) collect($this->modalSubjects)
            ->whereIn('id', $this->selectedSubjectIds)
            ->sum('weekly_hours');

        $this->recalculateCapacity();
        $this->showAssignModal = true;
    }
 
    public function loadAvailableSubjects(): void
{
    if (! $this->assigningRoomId) {
        $this->modalSubjects = [];
        return;
    }

    $room     = Room::findOrFail($this->assigningRoomId);
    
    // =====================================================================
    // CRITICAL FIX: Determine room type (case-insensitive)
    // Handle both 'Lab', 'LAB', 'Laboratory', and 'Lecture', 'LECTURE'
    // =====================================================================
    $rawRoomType = strtoupper(trim($room->type ?? ''));
    $isLabRoom = in_array($rawRoomType, ['LAB', 'LABORATORY'], true);
    
    $groupKey = $this->detectSpecializationGroupKey($room);
    $allowedDepts = $groupKey ? self::roomSpecializationGroups()[$groupKey] : [];

    // Start base query with ordering
    $query = Subject::activeTerm()
        // Practicum / OJT subjects have no physical room — never show in Manage Rooms.
        ->where(function ($q) {
            $q->where('is_practicum', false)->orWhereNull('is_practicum');
        })
        ->orderBy('department')
        ->orderBy('year_level')
        ->orderBy('section')
        ->orderBy('subject_code');

    // =====================================================================
    // ROOM-TYPE FILTERING (by subject type: Major vs Minor) — override-aware
    // =====================================================================
    // Default routing:  LAB  → Major subjects | LECTURE → Minor subjects
    // Override routing: a subject may opt OUT of the default via preferred_room_type:
    //   Major  + preferred_room_type = 'LECTURE' → eligible for LECTURE rooms
    //   Minor  + preferred_room_type = 'LAB'     → eligible for LAB rooms
    //
    //   LAB room   accepts: type=Major (default) OR (type=Minor AND preferred_room_type='LAB')
    //   LECTURE room accepts: type=Minor (default) OR (type=Major AND preferred_room_type='LECTURE')
    if ($isLabRoom) {
        $query->where(function ($q) {
            $q->where('type', 'Major')
              ->orWhere(function ($inner) {
                  $inner->where('type', 'Minor')
                        ->where('preferred_room_type', 'LAB');
              });
        });
    } else {
        $query->where(function ($q) {
            $q->where('type', 'Minor')
              ->orWhere(function ($inner) {
                  $inner->where('type', 'Major')
                        ->where('preferred_room_type', 'LECTURE');
              });
        });
    }

    // =====================================================================
    // IMPROVED DEPARTMENT/SPECIALIZATION FILTERING
    // =====================================================================
    // Apply department filter ONLY if a specialization group was detected
    if ($allowedDepts && ! empty($allowedDepts)) {
        $query->where(function ($q) use ($allowedDepts) {
            // PRIMARY: Check 'major' field directly
            $q->whereIn('major', $allowedDepts)
              // SECONDARY: Check 'department' field
              ->orWhereIn('department', $allowedDepts)
              // TERTIARY: Extract dept from edp_code (format: XX-NNNNNNN)
              ->orWhere(function ($inner) use ($allowedDepts) {
                  foreach ($allowedDepts as $dept) {
                      $inner->orWhere('edp_code', 'like', $dept . '-%');
                  }
              })
              // QUATERNARY: Extract dept from subject_code (format: XXNNN)
              ->orWhere(function ($inner) use ($allowedDepts) {
                  foreach ($allowedDepts as $dept) {
                      $inner->orWhere('subject_code', 'like', $dept . '%');
                  }
              })
              // FALLBACK: Check specialization field
              ->orWhere(function ($innermost) use ($allowedDepts) {
                  foreach ($allowedDepts as $dept) {
                      $innermost->orWhere('specialization', 'like', "%{$dept}%");
                  }
              });
        });
    }
    // If NO specialization group detected, allow ALL subjects of the correct type
    // (no department restriction)

    // =====================================================================
    // SERIALIZE RESULTS
    // =====================================================================
    $results = $query->with('preferredRoom')->get();   // avoids N+1 when rendering the "claimed by" badge
    
    $this->modalSubjects = $results
        ->map(fn (Subject $s) => [
            'id'                  => $s->id,
            'edp_code'            => $s->edp_code,
            'subject_code'        => $s->subject_code,
            'description'         => $s->description,
            'department'          => $s->department,
            'major'               => $s->major,
            'year_level'          => $s->year_level,
            'section'             => $s->section ?? '—',
            'units'               => (int) $s->units,
            'duration_hours'      => (float) $s->duration_hours,
            'meetings_per_week'   => (int) ($s->meetings_per_week ?? 1),
            // weekly_hours = duration_hours only. meetings_per_week = splitting only.
            'weekly_hours'        => round((float) $s->duration_hours, 1),
            'requires_lab'        => (bool) $s->requires_lab,
            'subject_type'        => (string) ($s->type ?? 'Major'),  // 'Major' or 'Minor'
            'preferred_room_type' => (string) ($s->preferred_room_type ?? ''),
            // Preferred-room data — used to render the "⚠️ Prefers Room: X" badge
            // when the subject is already bound to a *different* room.
            'preferred_room_id'   => $s->preferred_room_id,
            'preferred_room_name' => $s->preferredRoom?->room_name,
        ])
        ->toArray();

    $this->recalculateCapacity();
}
 
    /**
     * Toggle a single subject ID in / out of the selected list.
     *
     * This replaces the old  wire:click="$toggle('selectedSubjectIds.X')"  magic
     * call that was on every row div.  $toggle() is designed for boolean properties;
     * using it on an array path with the fallback key 'new' corrupted
     * $selectedSubjectIds from a proper sequential array ["1","2"] into an
     * associative PHP array ["0"=>"1","new"=>true].  When Livewire serialises that
     * to JSON it becomes a JS *object* instead of an *array*, which broke Alpine's
     * wire:model checkbox binding and caused every checkbox to appear checked.
     */
    public function toggleSubjectId(string $id): void
    {
        $id = (string) $id;

        if (in_array($id, $this->selectedSubjectIds, true)) {
            // ── REMOVE: always allowed — deselecting never violates capacity ──
            $this->selectedSubjectIds = array_values(
                array_filter($this->selectedSubjectIds, fn ($v) => $v !== $id)
            );
        } else {
            // ── ADD: hard capacity gate — block if adding would push room over max ──
            $maxWeeklyHours = RoomCapacityService::getWeeklyCapacity();
            $subjectToAdd   = collect($this->modalSubjects)->firstWhere('id', (int) $id);

            if ($subjectToAdd) {
                $subjectHours   = (float) ($subjectToAdd['weekly_hours'] ?? 0);
                $projectedTotal = $this->selectedWeeklyHours + $subjectHours;

                if ($projectedTotal > $maxWeeklyHours) {
                    $roomName  = $this->assigningRoomData['room_name'] ?? 'this room';
                    $remaining = max(0, $maxWeeklyHours - $this->selectedWeeklyHours);

                    // Update the soft-warning panel so the user sees exactly why they're blocked.
                    $this->capacityWarning = sprintf(
                        '⚠ Cannot add "%s" — %s is full. Maximum: %s/wk · Currently at: %s/wk · '
                        . 'Remaining: %s/wk · Subject needs: %s/wk. Remove a subject first.',
                        $subjectToAdd['subject_code'],
                        $roomName,
                        RoomCapacityService::getFormattedCapacity(),
                        RoomCapacityService::formatHours($this->selectedWeeklyHours),
                        RoomCapacityService::formatHours($remaining),
                        RoomCapacityService::formatHours($subjectHours)
                    );

                    // Fire a toast so the user gets immediate feedback.
                    $this->dispatch('toast', [
                        'type'    => 'warning',
                        'message' => 'Room at Capacity',
                        'detail'  => sprintf(
                            'Cannot add %s. %s is full — %s remaining, subject needs %s/wk.',
                            $subjectToAdd['subject_code'],
                            $roomName,
                            RoomCapacityService::formatHours($remaining),
                            RoomCapacityService::formatHours($subjectHours)
                        ),
                    ]);

                    // Hard stop: do NOT push to selectedSubjectIds.
                    // recalculateCapacity() is intentionally NOT called here so
                    // the existing capacityWarning (set above) is preserved for
                    // the re-render, giving the user the block-reason message.
                    return;
                }
            }

            $this->selectedSubjectIds[] = $id;
        }

        $this->recalculateCapacity();
    }

    /**
     * Called automatically by Livewire every time a checkbox changes.
     * Recalculates the running hours total and updates the warning string.
     */
    public function updatedSelectedSubjectIds(): void
    {
        $this->recalculateCapacity();
    }
 
    /**
     * Persist preferred_room_id assignments in a single DB transaction.
     *
     * Selected subjects  → preferred_room_id = this room
     * Deselected subjects that were previously bound → preferred_room_id = null
     *
     * NOTE: Uses the Query Builder update() path intentionally.
     *       Eloquent model events (saving / saved) are NOT fired for bulk
     *       updates, which is correct here — we never want EDP-code
     *       validation or semester normalisation to run on a room FK change.
     */
    public function saveRoomAssignments(): void
    {
        if (! $this->assigningRoomId) {
            return;
        }
 
        $selectedIds = collect($this->selectedSubjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // ── HARD CAPACITY VALIDATION ─────────────────────────────────────────
        // Block the save if the selected subjects would push this room over its
        // maximum weekly hours. This is the authoritative check; the UI warning
        // is advisory only.
        $maxCapacity  = RoomCapacityService::getWeeklyCapacity();
        $selectedHours = (float) collect($this->modalSubjects)
            ->whereIn('id', $selectedIds)
            ->sum('weekly_hours');
        $projectedTotal = $this->currentRoomHiddenLoad + $selectedHours;

        if ($projectedTotal > $maxCapacity) {
            $roomName  = $this->assigningRoomData['room_name'] ?? "Room #{$this->assigningRoomId}";
            $curLoad   = $this->currentRoomHiddenLoad + $this->currentRoomVisibleLoad;
            $newHours  = $projectedTotal - $curLoad;
            $remaining = max(0, $maxCapacity - $curLoad);

            $this->capacityError = sprintf(
                'Cannot save. %s would exceed its weekly capacity. '
                . 'Maximum: %sh/wk · Current: %sh/wk · Remaining: %sh/wk · Trying to add: %sh/wk',
                $roomName,
                RoomCapacityService::formatHours($maxCapacity),
                RoomCapacityService::formatHours($curLoad),
                RoomCapacityService::formatHours($remaining),
                RoomCapacityService::formatHours(max(0, $newHours))
            );

            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Room Capacity Exceeded',
                'detail'  => "Cannot assign subject. {$roomName} maximum is "
                           . RoomCapacityService::formatHours($maxCapacity)
                           . '/wk · Current: '
                           . RoomCapacityService::formatHours($curLoad)
                           . '/wk · Remaining: '
                           . RoomCapacityService::formatHours($remaining)
                           . '/wk · Trying to add: '
                           . RoomCapacityService::formatHours(max(0, $newHours)) . '/wk',
            ]);

            return; // ← Hard stop: do NOT write to DB
        }

        // Clear any stale hard error once the save is valid.
        $this->capacityError = '';
 
        DB::transaction(function () use ($selectedIds) {
            // 1. Bind newly selected subjects to this room
            if (! empty($selectedIds)) {
                Subject::activeTerm()
                    ->whereIn('id', $selectedIds)
                    ->update(['preferred_room_id' => $this->assigningRoomId]);
            }
 
            // 2. Unbind subjects that were previously linked here but were
            //    deselected in the current modal session
            Subject::activeTerm()
                ->where('preferred_room_id', $this->assigningRoomId)
                ->when(
                    ! empty($selectedIds),
                    fn ($q) => $q->whereNotIn('id', $selectedIds)
                )
                ->update(['preferred_room_id' => null]);
        });
 
        $roomName = $this->assigningRoomData['room_name'] ?? "Room #{$this->assigningRoomId}";
        $count    = count($selectedIds);
 
        $this->closeAssignModal();
 
        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => 'Assignments Saved',
            'detail'  => "{$count} subject(s) have been preferred-assigned to {$roomName}.",
        ]);
    }
 
    /**
     * Close the modal and reset all transient state.
     * Called by the Cancel button and the ✕ icon (via $wire.closeAssignModal()).
     */
    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->resetAssignModal();
    }
 
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
 
    /**
     * Sum the weekly hours of every currently-selected subject and update
     * $selectedWeeklyHours + $capacityWarning accordingly.
     */
    private function recalculateCapacity(): void
    {
        $selectedIds = array_map('intval', $this->selectedSubjectIds);
        $maxWeeklyHours = RoomCapacityService::getWeeklyCapacity();

        // Hours from subjects visible in the modal that are currently selected.
        $selectedModalHours = (float) collect($this->modalSubjects)
            ->whereIn('id', $selectedIds)
            ->sum('weekly_hours');

        // Total projected load = hidden subjects (other depts/types) + visible selected.
        $this->selectedWeeklyHours = $this->currentRoomHiddenLoad + $selectedModalHours;

        // ── Current saved load (for display) ─────────────────────────────────
        // = hidden load + visible subjects that are already saved to this room.
        // This lets the blade show "Current: Xh" separate from "Adding: Yh".
        $this->currentRoomVisibleLoad = (float) collect($this->modalSubjects)
            ->whereIn('id', array_map('intval', array_filter(
                $this->selectedSubjectIds,
                fn ($id) => collect($this->modalSubjects)->firstWhere('id', (int) $id) !== null
            )))
            ->sum('weekly_hours');

        // ── Soft warning (informational) ──────────────────────────────────────
        if ($this->selectedWeeklyHours > $maxWeeklyHours) {
            $roomName  = $this->assigningRoomData['room_name'] ?? 'this room';
            $over      = $this->selectedWeeklyHours - $maxWeeklyHours;
            $this->capacityWarning = sprintf(
                '⚠ Selected subjects require %.1fh/wk, exceeding the %s maximum by %s. '
                . 'Remove subjects or reduce assignments before saving.',
                $this->selectedWeeklyHours,
                RoomCapacityService::getFormattedCapacity(),
                RoomCapacityService::formatHours($over)
            );
        } else {
            $this->capacityWarning = '';
        }

        // Clear hard error whenever the selection changes to a valid state.
        if ($this->selectedWeeklyHours <= $maxWeeklyHours) {
            $this->capacityError = '';
        }
    }
 
    /**
     * Detect the canonical department-family key for a room so the correct
     * subject filter can be applied.
     *
     * Strategy:
     *   1. Use room->department_owner (already normalised by inferDepartmentOwner())
     *   2. Fall back to scanning the raw specialization string with keyword matching
     *
     * Mirrors AutoScheduleService::SPECIALIZATION_GROUPS for consistency.
     */
    private function detectSpecializationGroupKey(Room $room): ?string
    {
        $deptOwner = strtoupper(trim($room->department_owner ?? ''));
 
        // Fast path: department_owner is already normalised
        if ($deptOwner && array_key_exists($deptOwner, self::roomSpecializationGroups())) {
            return $deptOwner;
        }
 
        // Fallback: scan the raw specialization text
        $text = strtoupper(trim($room->specialization ?? ''));
        if (! $text) {
            return null;
        }
 
        return match (true) {
            $this->textContainsAny($text, ['IT', 'ACT', 'ICT', 'CCS', 'COMPUTER', 'WORKSHOP']) => 'CCS',
            $this->textContainsAny($text, ['CTE', 'EDUCATION', 'TEACHING'])                    => 'CTE',
            $this->textContainsAny($text, ['SHTM', 'HM', 'TM', 'HOSPITALITY', 'KITCHEN'])     => 'SHTM',
            $this->textContainsAny($text, ['COC', 'FB', 'LD', 'QD', 'FORENSIC', 'CRIM'])      => 'COC',
            default                                                                              => null,
        };
    }

    private function getFilterLabel(Room $room): string
    {
        $groupKey     = $this->detectSpecializationGroupKey($room);
        $rawRoomType  = strtoupper(trim($room->type ?? ''));
        $isLabRoom    = in_array($rawRoomType, ['LAB', 'LABORATORY'], true);
        // LAB → Major subjects (+ Minor overrides) | LECTURE → Minor subjects (+ Major overrides)
        $subjectType  = $isLabRoom ? 'MAJOR + overrides' : 'MINOR + overrides';

        if (! $groupKey) {
            return ucfirst(strtolower($rawRoomType)) . " (All Departments) · {$subjectType}";
        }

        $groups = self::roomSpecializationGroups();
        $depts  = $groups[$groupKey] ?? [];

        return implode(' / ', $depts) . ' · ' . ucfirst(strtolower($rawRoomType)) . " · {$subjectType}";
    }
 
    /**
     * Maps each department-owner code to the family of department codes
     * that are eligible to use rooms owned by that department.
     *
     * Kept in sync with AutoScheduleService::SPECIALIZATION_GROUPS.
     */
    private static function roomSpecializationGroups(): array
    {
        return [
            'CCS'  => ['IT', 'ACT', 'CCS'],
            'CTE'  => ['CTE', 'ED'],
            'SHTM' => ['HM', 'TM', 'SHTM'],
            'COC'  => ['FB', 'LD', 'QD', 'COC'],
        ];
    }
 
    private function textContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
 
        return false;
    }
 
    /** Wipe all assign-modal state so openAssignModal() starts clean. */
    private function resetAssignModal(): void
    {
        $this->assigningRoomId        = null;
        $this->assigningRoomData      = [];
        $this->modalSubjects          = [];
        $this->selectedSubjectIds     = [];
        $this->selectedWeeklyHours    = 0.0;
        $this->currentRoomHiddenLoad  = 0.0;
        $this->currentRoomVisibleLoad = 0.0;
        $this->capacityWarning        = '';
        $this->capacityError          = '';
    }

    /**
     * Toggle the inline subject accordion for a given room row.
     * Adds the ID to $expandedRooms to open, removes it to close.
     */
    public function toggleRoomDetails(int $roomId): void
    {
        if (in_array($roomId, $this->expandedRooms)) {
            $this->expandedRooms = array_values(array_diff($this->expandedRooms, [$roomId]));
        } else {
            $this->expandedRooms[] = $roomId;
        }
    }

    public function render()
    {
        $user = auth()->user();

        $query = Room::query()
            ->when($this->search, fn ($q) => $q->where('room_name', 'like', '%' . $this->search . '%')
                ->orWhere('specialization', 'like', '%' . $this->search . '%')
                ->orWhere('floor', 'like', '%' . $this->search . '%'))
            ->when($this->filterType, fn ($q) => $q->whereRaw('UPPER(type) = ?', [strtoupper($this->filterType)]))
            ->when($this->viewMode === 'my_rooms', function ($q) use ($user) {
                $role = $user->role ?? '';
                $dept = strtoupper(trim($user->department ?? ''));

                if (in_array($role, ['dean', 'oic'])) {
                    // Dean/OIC → their department's specialized rooms (Lab OR Lecture —
                    // e.g. CTE-ED owns lecture rooms, not laboratories, so we can't
                    // gate this by room type the way we used to).
                    $keywords = $this->getDeptKeywordsForDepartment($dept);
                    $q->where(function ($inner) use ($dept, $keywords) {
                        $inner->where('department_owner', $dept);
                        foreach ($keywords as $kw) {
                            $inner->orWhere('specialization', 'like', "%{$kw}%");
                        }
                    });
                } elseif ($role === 'associate_dean') {
                    // Associate Dean → LECTURE rooms only (they handle minor subjects)
                    $q->whereRaw('UPPER(type) = ?', ['LECTURE']);
                }
            });
 
        // ── Paginate rooms with preferred-assigned subjects (via preferred_room_id) ──
        $paginatedRooms = $query
            ->orderBy('room_name', 'asc')
            ->with(['subjects' => fn ($q) => $q
                // Subjects directly assigned to this room via ManageRooms "Manage Subjects" modal.
                // activeTerm() ensures we only show the current semester's subjects.
                ->activeTerm()
                ->where(function ($inner) {
                    $inner->where('is_practicum', false)->orWhereNull('is_practicum');
                })])
            ->paginate(10);

        // ── Also load subjects that have an active Schedule record with room_id on this page ──
        //
        // After a "Subject+Faculty+Room" retrieve from settings, Schedule.room_id is set
        // but Subject.preferred_room_id may still be null.  We need to surface those
        // subjects in the accordion too so the room card is never misleadingly empty.
        //
        // One bulk query for ALL rooms on the current page — zero N+1.
        $pageRoomIds = $paginatedRooms->pluck('id')->all();

        $scheduledSubjectsByRoom = collect();

        if (! empty($pageRoomIds)) {
            $scheduledSubjectsByRoom = Schedule::activeTerm()
                ->whereIn('room_id', $pageRoomIds)
                ->whereNotNull('room_id')
                ->whereNotNull('subject_id')
                ->with(['subject' => fn ($q) => $q
                    ->activeTerm()
                    ->where(function ($inner) {
                        $inner->where('is_practicum', false)->orWhereNull('is_practicum');
                    })])
                ->get()
                ->groupBy('room_id')
                ->map(fn ($schedules) => $schedules
                    ->pluck('subject')
                    ->filter()               // drop nulls (orphaned schedule rows)
                    ->unique('id')           // one entry per subject even if multi-day
                    ->values()
                );
        }

        return view('livewire.manage-rooms', [
            'rooms'                    => $paginatedRooms,
            // Keyed by room_id → Collection of Subject models from Schedule records.
            // The blade merges this with $room->subjects (preferred_room_id) to show
            // the full picture regardless of how the room assignment was made.
            'scheduledSubjectsByRoom'  => $scheduledSubjectsByRoom,
            'maxWeeklyHours'           => RoomCapacityService::getWeeklyCapacity(),
            // Capacity breakdown for the assign modal meter
            'currentRoomHiddenLoad'    => $this->currentRoomHiddenLoad,
            'currentRoomVisibleLoad'   => $this->currentRoomVisibleLoad,
        ]);
    }
}