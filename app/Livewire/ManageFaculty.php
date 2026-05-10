<?php

namespace App\Livewire;

use App\Models\Faculty;
use App\Models\FacultyLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\FacultyRequestNotification;

class ManageFaculty extends Component
{
    use WithPagination, WithFileUploads;

    // Filters & UI State
    public $search = '';
    public $filterDepartment = ''; 
    public $filterType = ''; 
    public $filterSpecialization = '';
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $importFile;
    public $importPreview = [];
    public $bulk = false;
    
    // Form fields
    public $faculty_id; 
    public $employee_id, $full_name, $email, $department;
    public $employment_type = 'Full-time';
    public $teaching_specialization = 'Both';
    public $max_units = 21;
    
    public $selectedFaculty = [];
    public $selectAll = false;
    public $confirmingDeletion = false;
    public $importSuccess = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => ''],
    ];

    private function isGlobalViewer()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar', 'associate_dean']);
    }

    private function isAdminOrRegistrar()
    {
        return in_array(auth()->user()->role, ['admin', 'registrar']);
    }

    /**
     * Generate next employee ID based on the last ID in database
     */
    private function generateNextEmployeeId()
    {
        try {
            $lastFaculty = Faculty::orderBy('id', 'desc')->first();
            
            if (!$lastFaculty) {
                return "2026-0001";
            }

            $lastId = $lastFaculty->employee_id;
            
            // Extract the number part from the ID (e.g., "2026-1005" -> 1005)
            if (preg_match('/(\d+)-(\d+)$/', $lastId, $matches)) {
                $year = $matches[1];
                $number = (int)$matches[2];
                $nextNumber = $number + 1;
                return $year . "-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            } else {
                // Fallback if format is different
                $number = (int)filter_var($lastId, FILTER_SANITIZE_NUMBER_INT);
                return "2026-" . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
            }
        } catch (\Exception $e) {
            Log::error('Error generating employee ID: ' . $e->getMessage());
            return "2026-0001";
        }
    }

    public function updatedSelectAll($value)
{
    if ($value) {
        $user = auth()->user();
        
        // This query MUST match your render() query filters exactly
        $this->selectedFaculty = Faculty::query()
            ->approved()
            ->when(!$this->isGlobalViewer(), function ($q) use ($user) {
                return $q->where('department', $user->department);
            })
            ->when($this->filterDepartment && $this->isGlobalViewer(), function ($q) {
                return $q->where('department', $this->filterDepartment);
            })
            // Add the new filters here too
            ->when($this->filterType, fn($q) => $q->where('employment_type', $this->filterType))
            ->when($this->filterSpecialization, fn($q) => $q->where('teaching_specialization', $this->filterSpecialization))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('employee_id', 'like', "%{$this->search}%");
                });
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    } else {
        $this->selectedFaculty = [];
    }
}

    private function logAction($facultyId, $action, $description, $department = null) 
    {
        try {
            FacultyLog::create([
                'faculty_id'  => $facultyId, 
                'user_id'     => auth()->id(), 
                'action'      => $action,      
                'description' => $description,
                'department'  => $department ?? (auth()->user()->role === 'dean' ? auth()->user()->department : null),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating faculty log: ' . $e->getMessage());
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterDepartment() { $this->resetPage(); }

    public function openModal() 
    {
        $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id']);
        $this->employment_type = 'Full-time';
        $this->teaching_specialization = 'Both';
        $this->max_units = 21;
        $this->resetValidation();
        $this->isEditMode = false;

        // AUTO-GENERATE NEXT EMPLOYEE ID
        $this->employee_id = $this->generateNextEmployeeId();

        // Only lock department for dean/oic
        $user = auth()->user();
        if (in_array($user->role, ['dean', 'oic']) && $user->department) {
            $this->department = $user->department;
        }

        $this->showModal = true; 
    }

    protected $rules = [
        'employee_id' => 'required|unique:faculties,employee_id',
        'full_name'   => 'required|min:5|regex:/(\s)/',
        'email'       => 'required|email',
        'department'  => 'required',
        'employment_type' => 'required|string',
        'teaching_specialization' => 'required|in:Major,Minor,Both',
        'max_units' => 'required|numeric|min:1',
    ];

    public function saveFaculty() 
    {
        try {
            $this->validate([
                'employee_id' => 'required|unique:faculties,employee_id',
                'full_name'   => 'required|unique:faculties,full_name|min:5|regex:/(\s)/', 
                'email'       => 'required|unique:faculties,email|email', 
                'department'  => 'required',
                'employment_type' => 'required|in:Full-time,Part-time',
                'teaching_specialization' => 'required|in:Major,Minor,Both',
                'max_units' => 'required|integer|min:1|max:30',
            ], [
                'full_name.unique'   => '⚠️ This name is already being used.',
                'email.unique'       => '⚠️ This email is already being used.',
                'employee_id.unique' => '⚠️ This ID is already assigned.',
                'full_name.regex'    => '⚠️ Please enter your complete full name.',
                'email.email'        => "⚠️ Please enter a valid email.",
                'full_name.min'      => '⚠️ Name is too short.',
                'email.required'     => '⚠️ The email address is required.',
                'employment_type.required' => '⚠️ Employment type is required.',
                'teaching_specialization.required' => '⚠️ Teaching specialization is required.',
                'max_units.required' => '⚠️ Max units is required.',
                'max_units.integer' => '⚠️ Max units must be a number.',
                'max_units.min' => '⚠️ Max units must be at least 1.',
                'max_units.max' => '⚠️ Max units cannot exceed 30.',
            ]);

            $status = $this->isAdminOrRegistrar() ? 'approved' : 'pending';

            $newFaculty = Faculty::create([
                'employee_id'  => $this->employee_id,
                'full_name'    => $this->full_name,
                'email'        => $this->email,
                'department'   => $this->department,
                'employment_type' => $this->employment_type,
                'teaching_specialization' => $this->teaching_specialization,
                'max_units' => (int)$this->max_units,
                'status'       => $status,
                'requested_by' => auth()->id(),
            ]);

            if (!$this->isAdminOrRegistrar()) {
                $allRecipients = $this->getStakeholders($newFaculty->department);
                foreach ($allRecipients as $recipient) {
                    if ($recipient->id === auth()->id()) continue;
                    try {
                        $recipient->notify(new FacultyRequestNotification($newFaculty, auth()->user()->name, 'pending'));
                    } catch (\Exception $e) {
                        Log::error('Error sending notification: ' . $e->getMessage());
                    }
                }
            }

            $this->logAction(
                $newFaculty->id, 
                'created', 
                strtoupper(auth()->user()->role) . " added faculty: {$newFaculty->full_name} ({$newFaculty->employment_type}, {$newFaculty->teaching_specialization}, {$newFaculty->max_units} units)",
                $newFaculty->department
            );

            $this->showModal = false;
            $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id', 'employment_type', 'teaching_specialization', 'max_units']);
            $this->employment_type = 'Full-time';
            $this->teaching_specialization = 'Both';
            $this->max_units = 21;

            $this->dispatch('toast', [
                'type' => 'success', 
                'message' => 'Faculty Registered', 
                'detail' => "{$this->full_name} has been added to the registry as " . strtoupper($status) . " ({$this->employment_type}, {$this->max_units} units)."
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error saving faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error', 
                'message' => 'Error', 
                'detail' => 'An error occurred while saving the faculty: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedFaculty)) return;

        try {
            $actor = auth()->user();
            $faculties = Faculty::whereIn('id', $this->selectedFaculty)->get();
            $count = 0;

            foreach ($faculties as $faculty) {
                $isAdminOrRegistrar = in_array($actor->role, ['admin', 'registrar']);
                $isAssociateDean = ($actor->role === 'associate_dean');
                $isDeptHead = in_array($actor->role, ['dean', 'oic']) && ($faculty->department === $actor->department);
                $isRejected = ($faculty->status === 'rejected');

                if (!$isAdminOrRegistrar && !(($isAssociateDean || $isDeptHead) && $isRejected)) {
                    continue;
                }

                $name = $faculty->full_name;
                $dept = $faculty->department;

                $recipients = User::query()
                    ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                    ->orWhere(function($q) use ($dept) {
                        $q->where('department', $dept)
                          ->whereIn('role', ['dean', 'oic']);
                    })
                    ->get();

                foreach ($recipients->unique('id') as $recipient) {
                    if ($recipient->id === $actor->id) continue;

                    try {
                        $recipient->notify(new FacultyRequestNotification(
                            $name, 
                            $actor->name, 
                            'deleted'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Error sending notification: ' . $e->getMessage());
                    }
                }

                FacultyLog::where('faculty_id', $faculty->id)->delete();
                
                $this->logAction(
                    null, 
                    'deleted', 
                    strtoupper($actor->role) . " ({$actor->name}) bulk-deleted faculty: {$name}",
                    $dept
                );

                $faculty->delete();
                $count++;
            }

            $this->reset(['selectedFaculty', 'selectAll', 'confirmingDeletion']);
            
            if ($count > 0) {
                $this->dispatch('toast', [
                    'type'    => 'warning', 
                    'message' => 'Bulk Deletion Complete', 
                    'detail'  => "$count records were removed. Relevant Department Heads notified."
                ]);
            } else {
                $this->dispatch('toast', [
                    'type'    => 'info', 
                    'message' => 'No Records Deleted', 
                    'detail'  => "No records were removed due to permission restrictions."
                ]);
            }

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error deleting selected: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error', 
                'message' => 'Error', 
                'detail' => 'An error occurred during deletion.'
            ]);
        }
    }

    public function editFaculty($id) 
    {
        try {
            $this->resetValidation();
            $f = Faculty::findOrFail($id);
            
            $this->faculty_id  = $f->id;
            $this->employee_id = $f->employee_id;
            $this->full_name   = $f->full_name;
            $this->email       = $f->email;
            $this->department  = $f->department;
            $this->employment_type = $f->employment_type ?? 'Full-time';
            $this->teaching_specialization = $f->teaching_specialization ?? 'Both';
            $this->max_units = $f->max_units ?? 21;
            
            $this->isEditMode = true;
            $this->showModal  = true;
        } catch (\Exception $e) {
            Log::error('Error editing faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error', 
                'message' => 'Error', 
                'detail' => 'Could not load faculty record.'
            ]);
        }
    }

    public function updateFaculty() 
    {
        try {
            if (!$this->faculty_id) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Error', 'detail' => 'Record ID not found.']);
                return;
            }

            $this->validate([
                'employee_id' => [
                    'required', 
                    Rule::unique('faculties', 'employee_id')->ignore($this->faculty_id)
                ],
                'full_name' => [
                    'required', 
                    'min:5', 
                    'regex:/(\s)/', 
                    Rule::unique('faculties', 'full_name')->ignore($this->faculty_id)
                ],
                'email' => [
                    'required', 
                    'email', 
                    Rule::unique('faculties', 'email')->ignore($this->faculty_id)
                ],
                'department' => 'required',
                'employment_type' => 'required|in:Full-time,Part-time',
                'teaching_specialization' => 'required|in:Major,Minor,Both',
                'max_units' => 'required|integer|min:1|max:30',
            ], [
                'full_name.unique'   => '⚠️ That name is already being used by another record.',
                'email.unique'       => '⚠️ That email address is already being used by another record.',
                'employee_id.unique' => '⚠️ That Employee ID is already assigned.',
                'full_name.regex'    => '⚠️ Please enter the complete full name (First and Last).',
                'email.required'     => '⚠️ The email address is required.',
                'max_units.integer' => '⚠️ Max units must be a number.',
            ]);

            $faculty = Faculty::findOrFail($this->faculty_id);
            $actor = auth()->user();
            
            $faculty->update([
                'employee_id' => $this->employee_id,
                'full_name'   => $this->full_name,
                'email'       => $this->email,
                'department'  => $this->department,
                'employment_type' => $this->employment_type,
                'teaching_specialization' => $this->teaching_specialization,
                'max_units' => (int)$this->max_units,
            ]);

            $this->logAction(
                $faculty->id, 
                'updated', 
                strtoupper($actor->role) . " updated details for: {$faculty->full_name} ({$this->employment_type}, {$this->max_units} units)",
                $faculty->department
            );

            if ($this->isAdminOrRegistrar()) {
                $recipients = User::query()
                    ->where('role', 'associate_dean')
                    ->orWhere(function($query) use ($faculty) {
                        $query->where('department', $faculty->department)
                              ->whereIn('role', ['dean', 'oic']);
                    })
                    ->get();

                foreach ($recipients->unique('id') as $recipient) {
                    if ($recipient->id === $actor->id) continue;

                    try {
                        $recipient->notify(new FacultyRequestNotification(
                            $faculty, 
                            $actor->name, 
                            'edited'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Error sending notification: ' . $e->getMessage());
                    }
                }
            }

            $this->showModal = false;
            $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id', 'employment_type', 'teaching_specialization', 'max_units']);
            $this->employment_type = 'Full-time';
            $this->teaching_specialization = 'Both';
            $this->max_units = 21;
            
            $this->dispatch('toast', [
                'type'    => 'success', 
                'message' => 'Record Updated', 
                'detail'  => "Details for {$this->full_name} have been updated successfully."
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error updating faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error', 
                'message' => 'Error', 
                'detail' => 'An error occurred while updating the faculty.'
            ]);
        }
    }

    public function deleteFaculty($id)
    {
        try {
            $faculty = Faculty::findOrFail($id);
            $actor = auth()->user();
            
            $isAdminOrRegistrar = in_array($actor->role, ['admin', 'registrar']);
            $isAssociateDean = ($actor->role === 'associate_dean');
            $isDeptHead = in_array($actor->role, ['dean', 'oic']) && ($faculty->department === $actor->department);
            $isRejected = ($faculty->status === 'rejected');

            $canDelete = false;
            if ($isAdminOrRegistrar) {
                $canDelete = true;
            } elseif (($isAssociateDean || $isDeptHead) && $isRejected) {
                $canDelete = true;
            }

            if (!$canDelete) {
                $this->dispatch('toast', [
                    'type' => 'error', 
                    'message' => 'Access Denied', 
                    'detail' => 'You do not have permission to remove this specific record.'
                ]);
                return;
            }

            $facultyName = $faculty->full_name;
            $facultyDept = $faculty->department;
            $previousStatus = strtoupper($faculty->status);
            $actorInfo = strtoupper($actor->role) . " ({$actor->name})";

            FacultyLog::where('faculty_id', $id)->delete();

            $this->logAction(
                null, 
                'deleted', 
                "{$actorInfo} permanently removed the {$previousStatus} faculty record: {$facultyName}",
                $facultyDept
            );

            $recipients = User::query()
                ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                ->orWhere(function($q) use ($facultyDept) {
                    $q->where('department', $facultyDept)
                      ->whereIn('role', ['dean', 'oic']);
                })
                ->get();

            foreach ($recipients->unique('id') as $recipient) {
                if ($recipient->id === $actor->id) continue;

                try {
                    $recipient->notify(new FacultyRequestNotification(
                        $facultyName, 
                        $actor->name, 
                        'deleted'
                    ));
                } catch (\Exception $e) {
                    Log::error('Error sending notification: ' . $e->getMessage());
                }
            }

            $faculty->delete();

            $this->dispatch('toast', [
                'type' => 'warning', 
                'message' => 'Record Deleted', 
                'detail' => "{$facultyName} has been successfully removed."
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Error deleting faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Action Failed',
                'detail' => 'An error occurred while trying to delete the record.'
            ]);
        }
    }

    /**
     * Helper to find header index with flexible matching
     */
    private function findHeaderIndex($headers, $targetHeader)
    {
        $targetLower = strtolower(str_replace([' ', '_'], '', $targetHeader));
        
        foreach ($headers as $index => $header) {
            $headerLower = strtolower(str_replace([' ', '_'], '', $header));
            if ($headerLower === $targetLower) {
                return $index;
            }
        }
        return null;
    }

    public function updatedImportFile()
    {
        try {
            $this->validate([
                'importFile' => 'required|mimes:csv,txt|max:10240',
            ]);

            $path = $this->importFile->getRealPath();
            $data = array_map('str_getcsv', file($path));
            
            if (empty($data)) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'Invalid File',
                    'detail' => 'The CSV file is empty.'
                ]);
                $this->reset(['importFile', 'importPreview']);
                return;
            }

            $headers = array_map('trim', $data[0]);

            // Check for Subject or Room files
            if (in_array('subject_code', $headers) || in_array('room_name', $headers)) {
                $type = in_array('subject_code', $headers) ? 'SUBJECT' : 'ROOM';
                $this->dispatch('toast', [
                    'type'    => 'error', 
                    'message' => 'Wrong File Type', 
                    'detail'  => "🚨 This is a $type file. Please upload a Faculty CSV."
                ]);
                $this->reset(['importFile', 'importPreview']);
                return;
            }

            // Required fields
            $required = ['employee_id', 'full_name'];
            foreach($required as $key) {
                if ($this->findHeaderIndex($headers, $key) === null) {
                    $this->dispatch('toast', [
                        'type'    => 'error', 
                        'message' => 'Invalid Format', 
                        'detail'  => "The file is missing the '$key' column."
                    ]);
                    $this->reset(['importFile', 'importPreview']);
                    return;
                }
            }

            // Find column indices
            $colIndex = [
                'employee_id' => $this->findHeaderIndex($headers, 'employee_id'),
                'full_name' => $this->findHeaderIndex($headers, 'full_name'),
                'email' => $this->findHeaderIndex($headers, 'email'),
                'department' => $this->findHeaderIndex($headers, 'department'),
                'employment_type' => $this->findHeaderIndex($headers, 'employment_type'),
                'teaching_specialization' => $this->findHeaderIndex($headers, 'teaching_specialization'),
                'max_units' => $this->findHeaderIndex($headers, 'max_units'),
            ];

            $this->importPreview = [];
            foreach (array_slice($data, 1) as $row) {
                // Skip completely empty rows
                if (empty($row) || count(array_filter($row)) === 0) {
                    continue;
                }

                // Parse required fields
                $employeeId = trim($row[$colIndex['employee_id']] ?? '');
                $fullName = trim($row[$colIndex['full_name']] ?? '');

                // Skip if critical fields are missing
                if (empty($employeeId) || empty($fullName)) {
                    continue;
                }

                // Parse optional fields
                $email = '';
                if ($colIndex['email'] !== null && isset($row[$colIndex['email']])) {
                    $email = trim($row[$colIndex['email']]);
                }

                $department = '';
                if ($colIndex['department'] !== null && isset($row[$colIndex['department']])) {
                    $department = strtoupper(trim($row[$colIndex['department']]));
                }

                $employmentType = 'Full-time';
                if ($colIndex['employment_type'] !== null && isset($row[$colIndex['employment_type']])) {
                    $rawType = strtolower(trim($row[$colIndex['employment_type']]));
                    $employmentType = (strpos($rawType, 'part') !== false) ? 'Part-time' : 'Full-time';
                }

                $specialization = 'Both';
                if ($colIndex['teaching_specialization'] !== null && isset($row[$colIndex['teaching_specialization']])) {
                    $rawSpec = strtolower(trim($row[$colIndex['teaching_specialization']]));
                    if (in_array($rawSpec, ['major', 'minor', 'both'])) {
                        $specialization = ucfirst($rawSpec);
                    }
                }

                $maxUnits = null;
                if ($colIndex['max_units'] !== null && isset($row[$colIndex['max_units']])) {
                    $rawUnits = trim($row[$colIndex['max_units']]);
                    if (is_numeric($rawUnits)) {
                        $maxUnits = (int)$rawUnits;
                    }
                }

                // Auto-calculate max units if not provided
                if ($maxUnits === null || $maxUnits < 1) {
                    $maxUnits = ($employmentType === 'Part-time') ? 12 : 21;
                } else {
                    // Enforce limits
                    $maxUnits = $employmentType === 'Part-time' 
                        ? min($maxUnits, 18) 
                        : min($maxUnits, 30);
                }

                // Check if record already exists
                $exists = Faculty::where('employee_id', $employeeId)->exists();
                
                if (!$exists && !empty($email)) {
                    $exists = Faculty::where('email', $email)->exists();
                }

                $this->importPreview[] = [
                    'employee_id'                => $employeeId,
                    'full_name'                  => $fullName,
                    'email'                      => $email,
                    'department'                 => $department,
                    'employment_type'            => $employmentType,
                    'teaching_specialization'    => $specialization,
                    'max_units'                  => $maxUnits,
                    'error'                      => $exists,
                ];
            }

            if (empty($this->importPreview)) {
                $this->dispatch('toast', [
                    'type'    => 'warning', 
                    'message' => 'No Valid Records', 
                    'detail'  => "The CSV file contains no valid faculty records to import."
                ]);
                $this->reset(['importFile', 'importPreview']);
                return;
            }
            
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error updating import file: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Error Processing File',
                'detail' => $e->getMessage()
            ]);
            $this->reset(['importFile', 'importPreview']);
        }
    }

    public function processImport()
    {
        try {
            if (empty($this->importPreview)) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'No Data',
                    'detail' => 'Please upload a file first.'
                ]);
                return;
            }

            $importCount = 0;
            $importedDepartments = [];
            $skippedCount = 0;

            foreach ($this->importPreview as $data) {
                try {
                    // Double-check existence
                    $exists = Faculty::where('employee_id', $data['employee_id'])->exists();

                    if (!$exists && !empty($data['email'])) {
                        $exists = Faculty::where('email', $data['email'])->exists();
                    }

                    if ($exists) {
                        $skippedCount++;
                        continue;
                    }

                    // Final validation before insert
                    $specialization = $data['teaching_specialization'];
                    if (!in_array($specialization, ['Major', 'Minor', 'Both'])) {
                        $specialization = 'Both';
                    }

                    $employmentType = $data['employment_type'];
                    if (!in_array($employmentType, ['Full-time', 'Part-time'])) {
                        $employmentType = 'Full-time';
                    }

                    $maxUnits = (int)$data['max_units'];
                    if ($maxUnits < 1 || $maxUnits > 30) {
                        $maxUnits = $employmentType === 'Part-time' ? 12 : 21;
                    }

                    // Create faculty - FIXED: use correct variables from $data
                    $faculty = Faculty::create([
                        'employee_id'                => $data['employee_id'],
                        'full_name'                  => $data['full_name'],
                        'email'                      => $data['email'] ?: null,
                        'department'                 => $data['department'] ?: 'Unassigned',
                        'employment_type'            => $employmentType,
                        'teaching_specialization'    => $specialization,
                        'max_units'                  => $maxUnits,
                        'status'                     => 'approved', 
                        'requested_by'               => auth()->id(),
                    ]);

                    $this->logAction(
                        $faculty->id, 
                        'created', 
                        strtoupper(auth()->user()->role) . " imported faculty: {$faculty->full_name} ({$employmentType}, {$specialization}, {$maxUnits} units)",
                        $faculty->department
                    );

                    if (!empty($data['department'])) {
                        $importedDepartments[] = $data['department'];
                    }

                    $importCount++;
                } catch (\Exception $e) {
                    Log::error('Error importing individual record: ' . $e->getMessage() . ' | Data: ' . json_encode($data));
                    $skippedCount++;
                    continue;
                }
            }

            if ($importCount > 0) {
                $senderName = auth()->user()->name;
                $uniqueDepts = array_unique($importedDepartments);

                foreach ($uniqueDepts as $deptName) {
                    $deptLeaders = User::where('department', $deptName)
                        ->whereIn('role', ['dean', 'oic'])
                        ->get();

                    foreach ($deptLeaders as $leader) {
                        if ($leader->id === auth()->id()) continue;

                        try {
                            $leader->notify(new FacultyRequestNotification(
                                "the $deptName Department", 
                                $senderName, 
                                'bulk_added'
                            ));
                        } catch (\Exception $e) {
                            Log::error('Error sending notification: ' . $e->getMessage());
                        }
                    }
                }

                $globalLeaders = User::whereIn('role', ['admin', 'registrar', 'associate_dean'])->get();
                foreach ($globalLeaders as $global) {
                    if ($global->id === auth()->id()) continue;

                    try {
                        $global->notify(new FacultyRequestNotification(
                            "$importCount Faculty Members", 
                            $senderName, 
                            'bulk_added'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Error sending notification: ' . $e->getMessage());
                    }
                }
            }

            $this->reset(['importFile', 'importPreview', 'bulkOpen']); 
            $this->dispatch('close-import-modal'); 

            $message = "✅ Successfully added $importCount faculty records with scheduling data";
            if ($skippedCount > 0) {
                $message .= " ($skippedCount skipped - already exist)";
            }

            $this->dispatch('toast', [
                'type'    => 'success', 
                'message' => 'Import Complete', 
                'detail'  => $message
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);

        } catch (\Exception $e) {
            Log::error('Error processing import: ' . $e->getMessage());
            $this->dispatch('close-import-modal');
            $this->dispatch('toast', [
                'type'    => 'error', 
                'message' => 'Import Failed', 
                'detail'  => $e->getMessage()
            ]);
        }
    }

    public function approveFaculty($id) 
    {
        try {
            if (!$this->isAdminOrRegistrar()) return;

            $faculty = Faculty::findOrFail($id);
            $actor = auth()->user();

            $faculty->update(['status' => 'approved']);
            
            $this->logAction(
                $faculty->id, 
                'approved', 
                strtoupper($actor->role) . " approved faculty: {$faculty->full_name} ({$faculty->employment_type}, {$faculty->max_units} units)",
                $faculty->department
            );

            $recipients = User::query()
                ->where('role', 'associate_dean')
                ->orWhere(function($q) use ($faculty) {
                    $q->where('department', $faculty->department)
                      ->whereIn('role', ['dean', 'oic']);
                })
                ->orWhere('id', $faculty->requested_by)
                ->get();

            foreach ($recipients->unique('id') as $recipient) {
                if ($recipient->id === $actor->id) continue;

                try {
                    $recipient->notify(new FacultyRequestNotification(
                        $faculty, 
                        $actor->name, 
                        'approved'
                    ));
                } catch (\Exception $e) {
                    Log::error('Error sending notification: ' . $e->getMessage());
                }
            }

            $this->dispatch('toast', [
                'type'    => 'success',
                'message' => 'Request Approved',
                'detail'  => "{$faculty->full_name} is now active."
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error approving faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Error',
                'detail' => 'An error occurred while approving the faculty.'
            ]);
        }
    }

    public function declineFaculty($id) 
    {
        try {
            if (!$this->isAdminOrRegistrar()) return;

            $faculty = Faculty::findOrFail($id);
            $actor = auth()->user();
            
            $faculty->update(['status' => 'rejected']);

            $this->logAction(
                $faculty->id, 
                'rejected', 
                strtoupper($actor->role) . " declined registration: {$faculty->full_name}",
                $faculty->department
            );
            
            $recipients = User::query()
                ->where('role', 'associate_dean')
                ->orWhere(function($q) use ($faculty) {
                    $q->where('department', $faculty->department)
                      ->whereIn('role', ['dean', 'oic']);
                })
                ->orWhere('id', $faculty->requested_by)
                ->get();

            foreach ($recipients->unique('id') as $recipient) {
                if ($recipient->id === $actor->id) continue;

                try {
                    $recipient->notify(new FacultyRequestNotification(
                        $faculty, 
                        $actor->name, 
                        'rejected'
                    ));
                } catch (\Exception $e) {
                    Log::error('Error sending notification: ' . $e->getMessage());
                }
            }
            
            $this->dispatch('toast', [
                'type'    => 'info',
                'message' => 'Request Declined',
                'detail'  => "Registration for {$faculty->full_name} rejected."
            ]);

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Exception $e) {
            Log::error('Error declining faculty: ' . $e->getMessage());
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Error',
                'detail' => 'An error occurred while declining the faculty.'
            ]);
        }
    }

    public function exportCSV() 
    {
        try {
            $user = auth()->user();
            $filename = "faculty_list_" . now()->format('Y-m-d') . ".csv";
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['Employee ID', 'Full Name', 'Email', 'Department', 'Employment Type', 'Specialization', 'Max Units', 'Status'];

            $callback = function() use ($columns, $user) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                $data = Faculty::whereIn('status', ['approved', 'rejected'])
                    ->when(!$this->isAdminOrRegistrar(), function($query) use ($user) {
                        return $query->where('department', $user->department);
                    })->get();
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->employee_id, 
                        $row->full_name, 
                        $row->email, 
                        $row->department, 
                        $row->employment_type ?? 'Full-time',
                        $row->teaching_specialization ?? 'Both',
                        $row->max_units ?? 21,
                        ucfirst($row->status)
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Error exporting CSV: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed'], 500);
        }
    }
    
    private function getStakeholders($department)
    {
        try {
            return User::query()
                ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
                ->orWhere(function($q) use ($department) {
                    $q->where('department', $department)
                      ->whereIn('role', ['dean', 'oic']);
                })
                ->get()
                ->unique('id');
        } catch (\Exception $e) {
            Log::error('Error getting stakeholders: ' . $e->getMessage());
            return collect();
        }
    }

    public function render()
    {
        try {
            $user = auth()->user();

            if (!$this->isGlobalViewer()) { 
                $this->filterDepartment = $user->department; 
            }

            $pendingRequests = Faculty::where('status', 'pending')
                ->when(!$this->isAdminOrRegistrar(), function ($q) use ($user) {
                    return $q->where('requested_by', $user->id);
                })->orderBy('created_at', 'desc')->get();

            $faculties = Faculty::query()
                ->whereIn('status', ['approved', 'rejected']) 
                ->when(!$this->isGlobalViewer(), function ($q) use ($user) {
                    return $q->where('department', $user->department);
                })
                ->when($this->filterType, fn($q) => $q->where('employment_type', $this->filterType))
                ->when($this->filterSpecialization, fn($q) => $q->where('teaching_specialization', $this->filterSpecialization))
                ->when($this->filterDepartment && $this->isGlobalViewer(), function ($q) {
                    return $q->where('department', $this->filterDepartment);
                })
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('full_name', 'like', "%{$this->search}%")
                            ->orWhere('employee_id', 'like', "%{$this->search}%");
                    });
                })->orderBy('employee_id', 'asc')->paginate(10);

            $recentLogs = FacultyLog::with(['user', 'faculty'])
                ->where(function ($q) use ($user) {
                    if ($this->isGlobalViewer()) {
                        $q->whereRaw('1=1'); 
                    } else {
                        $q->where('user_id', $user->id)
                          ->orWhereHas('faculty', function ($f) use ($user) {
                              $f->where('department', $user->department);
                          });
                    }
                })
                ->latest()
                ->take(15)
                ->get();

            return view('livewire.manage-faculty', [
                'faculties' => $faculties,
                'pendingRequests' => $pendingRequests,
                'recentLogs' => $recentLogs
            ]);
        } catch (\Exception $e) {
            Log::error('Error rendering ManageFaculty: ' . $e->getMessage());
            return view('livewire.manage-faculty', [
                'faculties' => collect(),
                'pendingRequests' => collect(),
                'recentLogs' => collect()
            ]);
        }
    }
}