<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\FacultyLog;
use App\Models\User;
use App\Notifications\FacultyRequestNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ManageFaculty extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    public $filterDepartment = '';

    public $filterType = '';

    public $filterScope = '';

    public $showModal = false;

    public $bulkOpen = false;

    public $isEditMode = false;

    public $confirmingDeletion = false;

    public $importFile;

    public $importPreview = [];

    public $faculty_id;

    public $employee_id;

    public $full_name;

    public $email;

    public $department;

    public $employment_type = 'Full-time';

    public $faculty_scope = Faculty::SCOPE_DEPARTMENTAL;

    public $can_teach_minor = false;

    public $max_units = 21;

    public $selectedFaculty = [];

    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => ''],
        'filterScope' => ['except' => ''],
    ];

    public function isGlobalViewer(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'registrar', 'associate_dean'], true);
    }

    public function isAdminOrRegistrar(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'registrar'], true);
    }

    public function updatedSelectAll($value): void
    {
        if (! $value) {
            $this->selectedFaculty = [];

            return;
        }

        $this->selectedFaculty = $this->baseFacultyQuery()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatingFilterScope(): void
    {
        $this->resetPage();
    }

    public function updatedFacultyScope($value): void
    {
        if ($value === Faculty::SCOPE_GENED) {
            $this->department = null;
            $this->can_teach_minor = true;
        }

        if (in_array(auth()->user()?->role, ['dean', 'oic'], true)) {
            $this->faculty_scope = Faculty::SCOPE_DEPARTMENTAL;
            $this->department = Department::normalizeCode(auth()->user()->department);
        }
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->employee_id = $this->generateNextEmployeeId();

        $user = auth()->user();
        if (in_array($user?->role, ['dean', 'oic'], true) && $user->department) {
            $this->faculty_scope = Faculty::SCOPE_DEPARTMENTAL;
            $this->department = Department::normalizeCode($user->department);
        }

        $this->showModal = true;
    }

    public function saveFaculty(): void
    {
        try {
            $this->normalizeFormState();
            $this->validate($this->facultyValidationRules(), $this->validationMessages());

            $status = $this->isAdminOrRegistrar() ? 'approved' : 'pending';
            $faculty = Faculty::create($this->facultyPayload([
                'status' => $status,
                'requested_by' => auth()->id(),
            ]));

            $this->notifyStakeholders($faculty, 'pending');
            $this->logAction(
                $faculty->id,
                'created',
                strtoupper(auth()->user()->role)." added faculty: {$faculty->full_name} ({$faculty->scopeLabel()}, {$faculty->employment_type}, {$faculty->max_units} units)",
                $faculty->department
            );

            $facultyName = $faculty->full_name;
            $this->showModal = false;
            $this->resetForm();

            $this->toast('success', 'Faculty Registered', "{$facultyName} has been added as ".strtoupper($status).'.');
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error saving faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'An error occurred while saving the faculty: '.$exception->getMessage());
        }
    }

    public function editFaculty($id): void
    {
        try {
            if (! $this->isAdminOrRegistrar()) {
                $this->toast('error', 'Access Denied', 'Only Admin or Registrar accounts can edit faculty records.');

                return;
            }

            $faculty = Faculty::findOrFail($id);

            $this->resetValidation();
            $this->faculty_id = $faculty->id;
            $this->employee_id = $faculty->employee_id;
            $this->full_name = $faculty->full_name;
            $this->email = $faculty->email;
            $this->department = $faculty->department;
            $this->employment_type = $faculty->employment_type ?? 'Full-time';
            $this->faculty_scope = $faculty->faculty_scope ?? Faculty::SCOPE_DEPARTMENTAL;
            $this->can_teach_minor = (bool) $faculty->can_teach_minor;
            $this->max_units = $faculty->max_units ?? 21;
            $this->isEditMode = true;
            $this->showModal = true;
        } catch (\Throwable $exception) {
            Log::error('Error editing faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'Could not load faculty record.');
        }
    }

    public function updateFaculty(): void
    {
        try {
            if (! $this->faculty_id) {
                $this->toast('error', 'Error', 'Record ID not found.');

                return;
            }

            if (! $this->isAdminOrRegistrar()) {
                $this->toast('error', 'Access Denied', 'Only Admin or Registrar accounts can update faculty records.');

                return;
            }

            $this->normalizeFormState();
            $this->validate($this->facultyValidationRules((int) $this->faculty_id), $this->validationMessages());

            $faculty = Faculty::findOrFail($this->faculty_id);
            $faculty->update($this->facultyPayload());

            $this->logAction(
                $faculty->id,
                'updated',
                strtoupper(auth()->user()->role)." updated details for: {$faculty->full_name} ({$faculty->scopeLabel()}, {$faculty->employment_type}, {$faculty->max_units} units)",
                $faculty->department
            );

            $this->notifyStakeholders($faculty, 'edited');

            $facultyName = $faculty->full_name;
            $this->showModal = false;
            $this->resetForm();

            $this->toast('success', 'Record Updated', "Details for {$facultyName} have been updated successfully.");
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error updating faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'An error occurred while updating the faculty: '.$exception->getMessage());
        }
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedFaculty)) {
            return;
        }

        try {
            $actor = auth()->user();
            $count = 0;

            Faculty::whereIn('id', $this->selectedFaculty)->get()->each(function (Faculty $faculty) use ($actor, &$count) {
                if (! $this->canDeleteFaculty($faculty, $actor)) {
                    return;
                }

                $name = $faculty->full_name;
                $department = $faculty->department;

                FacultyLog::where('faculty_id', $faculty->id)->delete();
                $faculty->delete();

                $this->logAction(null, 'deleted', strtoupper($actor->role)." ({$actor->name}) bulk-deleted faculty: {$name}", $department);
                $this->notifyFacultyDeletion($name, $department, $actor);
                $count++;
            });

            $this->reset(['selectedFaculty', 'selectAll', 'confirmingDeletion']);

            $count > 0
                ? $this->toast('warning', 'Bulk Deletion Complete', "{$count} record(s) were removed.")
                : $this->toast('info', 'No Records Deleted', 'No records were removed due to permission restrictions.');

            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error deleting selected faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'An error occurred during deletion.');
        }
    }

    public function deleteFaculty($id): void
    {
        try {
            $faculty = Faculty::findOrFail($id);
            $actor = auth()->user();

            if (! $this->canDeleteFaculty($faculty, $actor)) {
                $this->toast('error', 'Access Denied', 'You do not have permission to remove this record.');

                return;
            }

            $facultyName = $faculty->full_name;
            $facultyDepartment = $faculty->department;
            $previousStatus = strtoupper($faculty->status);

            FacultyLog::where('faculty_id', $faculty->id)->delete();
            $faculty->delete();

            $this->logAction(null, 'deleted', strtoupper($actor->role)." ({$actor->name}) removed the {$previousStatus} faculty record: {$facultyName}", $facultyDepartment);
            $this->notifyFacultyDeletion($facultyName, $facultyDepartment, $actor);

            $this->toast('warning', 'Record Deleted', "{$facultyName} has been removed.");
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
            $this->dispatch('$refresh');
        } catch (\Throwable $exception) {
            Log::error('Error deleting faculty: '.$exception->getMessage());
            $this->toast('error', 'Action Failed', 'An error occurred while trying to delete the record.');
        }
    }

    public function updatedImportFile(): void
    {
        try {
            if (! $this->isAdminOrRegistrar()) {
                $this->toast('error', 'Access Denied', 'Only Admin or Registrar accounts can import faculty records.');
                $this->reset(['importFile', 'importPreview']);

                return;
            }

            $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);

            $rows = array_map('str_getcsv', file($this->importFile->getRealPath()));
            if (empty($rows)) {
                $this->reset(['importFile', 'importPreview']);
                $this->toast('error', 'Invalid File', 'The CSV file is empty.');

                return;
            }

            $headers = array_map(fn ($header) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $header)), $rows[0]);
            if (in_array('subject_code', $headers, true) || in_array('room_name', $headers, true)) {
                $this->reset(['importFile', 'importPreview']);
                $this->toast('error', 'Wrong File Type', 'This appears to be a Subject or Room CSV. Please upload a Faculty CSV.');

                return;
            }

            foreach (['employee_id', 'full_name', 'faculty_scope', 'can_teach_minor'] as $requiredHeader) {
                if ($this->findHeaderIndex($headers, $requiredHeader) === null) {
                    $this->reset(['importFile', 'importPreview']);
                    $this->toast('error', 'Invalid Format', "The file is missing the '{$requiredHeader}' column.");

                    return;
                }
            }

            $columns = [
                'employee_id' => $this->findHeaderIndex($headers, 'employee_id'),
                'full_name' => $this->findHeaderIndex($headers, 'full_name'),
                'email' => $this->findHeaderIndex($headers, 'email'),
                'department' => $this->findHeaderIndex($headers, 'department'),
                'employment_type' => $this->findHeaderIndex($headers, 'employment_type'),
                'faculty_scope' => $this->findHeaderIndex($headers, 'faculty_scope'),
                'can_teach_minor' => $this->findHeaderIndex($headers, 'can_teach_minor'),
                'max_units' => $this->findHeaderIndex($headers, 'max_units'),
            ];

            $this->importPreview = [];

            foreach (array_slice($rows, 1) as $rowNumber => $row) {
                if (empty($row) || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $this->importPreview[] = $this->previewImportRow($row, $columns, $rowNumber + 2);
            }

            if (empty($this->importPreview)) {
                $this->reset(['importFile', 'importPreview']);
                $this->toast('warning', 'No Valid Records', 'The CSV file contains no valid faculty records to import.');

                return;
            }
        } catch (\Throwable $exception) {
            Log::error('Error processing faculty import file: '.$exception->getMessage());
            $this->reset(['importFile', 'importPreview']);
            $this->toast('error', 'Error Processing File', $exception->getMessage());
        }
    }

    public function processImport(): void
    {
        try {
            if (empty($this->importPreview)) {
                $this->toast('error', 'No Data', 'Please upload a file first.');

                return;
            }

            $invalidCount = collect($this->importPreview)->where('status', 'invalid')->count();
            if ($invalidCount > 0) {
                $this->toast('error', 'Import Blocked', "Fix {$invalidCount} invalid row(s) before importing.");

                return;
            }

            $importCount = 0;
            $skippedCount = 0;
            $importedDepartments = [];

            foreach ($this->importPreview as $data) {
                if (($data['status'] ?? null) !== 'ready') {
                    $skippedCount++;

                    continue;
                }

                if (Faculty::where('employee_id', $data['employee_id'])->exists()
                    || ($data['email'] && Faculty::where('email', $data['email'])->exists())) {
                    $skippedCount++;

                    continue;
                }

                $faculty = Faculty::create([
                    'employee_id' => $data['employee_id'],
                    'full_name' => $data['full_name'],
                    'email' => $data['email'] ?: null,
                    'department' => $data['department'] ?: null,
                    'employment_type' => $data['employment_type'],
                    'faculty_scope' => $data['faculty_scope'],
                    'can_teach_minor' => (bool) $data['can_teach_minor'],
                    'max_units' => (int) $data['max_units'],
                    'status' => 'approved',
                    'requested_by' => auth()->id(),
                ]);

                $this->logAction(
                    $faculty->id,
                    'created',
                    strtoupper(auth()->user()->role)." imported faculty: {$faculty->full_name} ({$faculty->scopeLabel()}, {$faculty->employment_type}, {$faculty->max_units} units)",
                    $faculty->department
                );

                if ($faculty->department) {
                    $importedDepartments[] = $faculty->department;
                }

                $importCount++;
            }

            $this->notifyBulkImport($importCount, array_unique($importedDepartments));
            $this->reset(['importFile', 'importPreview', 'bulkOpen']);
            $this->dispatch('close-import-modal');

            $message = "Successfully added {$importCount} faculty record(s).";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} duplicate row(s) skipped.";
            }

            $this->toast('success', 'Import Complete', $message);
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error processing faculty import: '.$exception->getMessage());
            $this->dispatch('close-import-modal');
            $this->toast('error', 'Import Failed', $exception->getMessage());
        }
    }

    public function approveFaculty($id): void
    {
        try {
            if (! $this->isAdminOrRegistrar()) {
                return;
            }

            $faculty = Faculty::findOrFail($id);
            $faculty->update(['status' => 'approved']);

            $this->logAction($faculty->id, 'approved', strtoupper(auth()->user()->role)." approved faculty: {$faculty->full_name}", $faculty->department);
            $this->notifyStakeholders($faculty, 'approved');
            $this->toast('success', 'Request Approved', "{$faculty->full_name} is now active.");
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error approving faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'An error occurred while approving the faculty.');
        }
    }

    public function declineFaculty($id): void
    {
        try {
            if (! $this->isAdminOrRegistrar()) {
                return;
            }

            $faculty = Faculty::findOrFail($id);
            $faculty->update(['status' => 'rejected']);

            $this->logAction($faculty->id, 'rejected', strtoupper(auth()->user()->role)." declined registration: {$faculty->full_name}", $faculty->department);
            $this->notifyStakeholders($faculty, 'rejected');
            $this->toast('info', 'Request Declined', "Registration for {$faculty->full_name} was rejected.");
            $this->dispatch('facultyUpdated')->to(NotificationCenter::class);
        } catch (\Throwable $exception) {
            Log::error('Error declining faculty: '.$exception->getMessage());
            $this->toast('error', 'Error', 'An error occurred while declining the faculty.');
        }
    }

    public function exportCSV()
    {
        try {
            $filename = 'faculty_list_'.now()->format('Y-m-d').'.csv';
            $columns = ['employee_id', 'full_name', 'email', 'department', 'employment_type', 'faculty_scope', 'can_teach_minor', 'max_units', 'status'];
            $user = auth()->user();

            $callback = function () use ($columns, $user) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                Faculty::query()
                    ->whereIn('status', ['approved', 'rejected'])
                    ->when(! $this->isGlobalViewer(), fn (Builder $query) => $query->whereIn('department', Department::aliasesFor($user->department)))
                    ->orderBy('employee_id')
                    ->get()
                    ->each(function (Faculty $faculty) use ($file) {
                        fputcsv($file, [
                            $faculty->employee_id,
                            $faculty->full_name,
                            $faculty->email,
                            $faculty->department,
                            $faculty->employment_type ?? 'Full-time',
                            $faculty->faculty_scope ?? Faculty::SCOPE_DEPARTMENTAL,
                            $faculty->can_teach_minor ? 'yes' : 'no',
                            $faculty->max_units ?? 21,
                            $faculty->status,
                        ]);
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error exporting faculty CSV: '.$exception->getMessage());

            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function render()
    {
        try {
            $user = auth()->user();

            if (! $this->isGlobalViewer()) {
                $this->filterDepartment = Department::normalizeCode($user->department);
            }

            $pendingRequests = Faculty::where('status', 'pending')
                ->when(! $this->isAdminOrRegistrar(), fn (Builder $query) => $query->where('requested_by', $user->id))
                ->orderByDesc('created_at')
                ->get();

            $faculties = $this->baseFacultyQuery()
                ->orderBy('employee_id')
                ->paginate(10);

            $recentLogs = FacultyLog::with(['user', 'faculty'])
                ->when(! $this->isGlobalViewer(), function (Builder $query) use ($user) {
                    $query->where(function (Builder $visibility) use ($user) {
                        $visibility->where('user_id', $user->id)
                            ->orWhereHas('faculty', fn (Builder $facultyQuery) => $facultyQuery->whereIn('department', Department::aliasesFor($user->department)));
                    });
                })
                ->latest()
                ->take(15)
                ->get();

            return view('livewire.manage-faculty', [
                'faculties' => $faculties,
                'pendingRequests' => $pendingRequests,
                'recentLogs' => $recentLogs,
                'departments' => $this->departmentOptions(),
                'scopeOptions' => $this->scopeOptions(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error rendering ManageFaculty: '.$exception->getMessage());

            return view('livewire.manage-faculty', [
                'faculties' => collect(),
                'pendingRequests' => collect(),
                'recentLogs' => collect(),
                'departments' => ['CCS', 'CTE', 'COC', 'SHTM'],
                'scopeOptions' => $this->scopeOptions(),
            ]);
        }
    }

    private function baseFacultyQuery(): Builder
    {
        $user = auth()->user();

        return Faculty::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->when(! $this->isGlobalViewer(), fn (Builder $query) => $query->whereIn('department', Department::aliasesFor($user->department)))
            ->when($this->filterType, fn (Builder $query) => $query->where('employment_type', $this->filterType))
            ->when($this->filterScope, fn (Builder $query) => $query->where('faculty_scope', $this->filterScope))
            ->when($this->filterDepartment && $this->isGlobalViewer(), function (Builder $query) {
                $query->whereIn('department', Department::aliasesFor($this->filterDepartment));
            })
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $searchQuery) {
                    $searchQuery->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('employee_id', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            });
    }

    private function generateNextEmployeeId(): string
    {
        try {
            $lastFaculty = Faculty::orderByDesc('id')->first();
            if (! $lastFaculty || ! preg_match('/(\d+)-(\d+)$/', (string) $lastFaculty->employee_id, $matches)) {
                return '2026-0001';
            }

            return $matches[1].'-'.str_pad(((int) $matches[2]) + 1, 4, '0', STR_PAD_LEFT);
        } catch (\Throwable $exception) {
            Log::error('Error generating employee ID: '.$exception->getMessage());

            return '2026-0001';
        }
    }

    private function resetForm(): void
    {
        $this->reset(['employee_id', 'full_name', 'email', 'department', 'faculty_id']);
        $this->employment_type = 'Full-time';
        $this->faculty_scope = Faculty::SCOPE_DEPARTMENTAL;
        $this->can_teach_minor = false;
        $this->max_units = 21;
        $this->isEditMode = false;
        $this->resetValidation();
    }

    private function normalizeFormState(): void
    {
        $allowedScopes = array_keys($this->scopeOptions());
        if (! in_array($this->faculty_scope, $allowedScopes, true)) {
            $this->faculty_scope = $allowedScopes[0] ?? Faculty::SCOPE_DEPARTMENTAL;
        }

        if (in_array(auth()->user()?->role, ['dean', 'oic'], true)) {
            $this->faculty_scope = Faculty::SCOPE_DEPARTMENTAL;
            $this->department = Department::normalizeCode(auth()->user()->department);
        }

        $this->department = filled($this->department) ? Department::normalizeCode($this->department) : null;

        if ($this->faculty_scope === Faculty::SCOPE_GENED) {
            $this->department = null;
            $this->can_teach_minor = true;
        }
    }

    private function facultyValidationRules(?int $ignoreId = null): array
    {
        return [
            'employee_id' => ['required', Rule::unique('faculties', 'employee_id')->ignore($ignoreId)],
            'full_name' => ['required', 'min:5', 'regex:/(\s)/', Rule::unique('faculties', 'full_name')->ignore($ignoreId)],
            'email' => ['required', 'email', Rule::unique('faculties', 'email')->ignore($ignoreId)],
            'department' => [Rule::requiredIf(fn () => $this->faculty_scope !== Faculty::SCOPE_GENED), 'nullable', 'string', 'max:50'],
            'employment_type' => ['required', Rule::in(['Full-time', 'Part-time'])],
            'faculty_scope' => ['required', Rule::in(array_keys($this->scopeOptions()))],
            'can_teach_minor' => ['boolean'],
            'max_units' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'employee_id.unique' => 'This employee ID is already assigned.',
            'full_name.unique' => 'This name is already being used.',
            'full_name.regex' => 'Please enter a complete full name.',
            'email.unique' => 'This email address is already being used.',
            'department.required' => 'Department is required for departmental and cross-department faculty.',
            'faculty_scope.in' => 'Choose a valid faculty scope.',
            'max_units.max' => 'Max units cannot exceed 30.',
        ];
    }

    private function facultyPayload(array $extra = []): array
    {
        return array_merge([
            'employee_id' => $this->employee_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'department' => $this->department,
            'employment_type' => $this->employment_type,
            'faculty_scope' => $this->faculty_scope,
            'can_teach_minor' => (bool) $this->can_teach_minor,
            'max_units' => (int) $this->max_units,
        ], $extra);
    }

    private function scopeOptions(): array
    {
        return match (auth()->user()?->role) {
            'admin', 'registrar' => [
                Faculty::SCOPE_DEPARTMENTAL => 'Departmental',
                Faculty::SCOPE_CROSS_DEPARTMENT => 'Cross-department',
                Faculty::SCOPE_GENED => 'GenEd',
            ],
            'associate_dean' => [
                Faculty::SCOPE_DEPARTMENTAL => 'Departmental',
                Faculty::SCOPE_GENED => 'GenEd',
            ],
            default => [
                Faculty::SCOPE_DEPARTMENTAL => 'Departmental',
            ],
        };
    }

    private function departmentOptions(): array
    {
        return collect(['CCS', 'CTE', 'COC', 'SHTM'])
            ->merge(Department::query()->pluck('code'))
            ->merge(Faculty::query()->whereNotNull('department')->pluck('department'))
            ->filter()
            ->map(fn ($department) => Department::normalizeCode($department))
            ->reject(fn ($department) => in_array($department, ['GENED', 'GENERAL EDUCATION'], true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function canDeleteFaculty(Faculty $faculty, ?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        if (in_array($actor->role, ['admin', 'registrar'], true)) {
            return true;
        }

        $isRejected = $faculty->status === 'rejected';
        $isDeptHead = in_array($actor->role, ['dean', 'oic'], true)
            && Department::codesMatch($faculty->department, $actor->department);
        $isAssociateDean = $actor->role === 'associate_dean';

        return $isRejected && ($isDeptHead || $isAssociateDean);
    }

    private function previewImportRow(array $row, array $columns, int $rowNumber): array
    {
        $employeeId = trim((string) ($row[$columns['employee_id']] ?? ''));
        $fullName = trim((string) ($row[$columns['full_name']] ?? ''));
        $email = $columns['email'] !== null ? trim((string) ($row[$columns['email']] ?? '')) : '';
        $rawScope = trim((string) ($row[$columns['faculty_scope']] ?? ''));
        $scope = $this->normalizeFacultyScope($rawScope);
        $rawCanTeachMinor = trim((string) ($row[$columns['can_teach_minor']] ?? ''));
        $canTeachMinor = $this->parseBoolean($rawCanTeachMinor);
        $employmentType = $this->normalizeEmploymentType($columns['employment_type'] !== null ? ($row[$columns['employment_type']] ?? '') : '');
        $department = $this->normalizeImportDepartment($columns['department'] !== null ? ($row[$columns['department']] ?? '') : '', $scope);
        $maxUnits = $this->parseMaxUnits($columns['max_units'] !== null ? ($row[$columns['max_units']] ?? '') : null, $employmentType);
        $errors = [];

        if ($employeeId === '') {
            $errors[] = 'Employee ID is required.';
        }

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email is invalid.';
        }

        if (! $scope) {
            $errors[] = 'Faculty scope must be gened, departmental, or cross_department.';
            $scope = Faculty::SCOPE_DEPARTMENTAL;
        }

        if ($canTeachMinor === null) {
            $errors[] = 'can_teach_minor must be yes/no, true/false, or 1/0.';
            $canTeachMinor = false;
        }

        if ($scope === Faculty::SCOPE_GENED) {
            $canTeachMinor = true;
            $department = null;
        } elseif (! $department) {
            $errors[] = 'Department is required for departmental and cross-department faculty.';
        }

        $duplicate = ($employeeId !== '' && Faculty::where('employee_id', $employeeId)->exists())
            || ($email !== '' && Faculty::where('email', $email)->exists());

        $status = $errors !== [] ? 'invalid' : ($duplicate ? 'duplicate' : 'ready');

        return [
            'row' => $rowNumber,
            'employee_id' => $employeeId,
            'full_name' => $fullName,
            'email' => $email,
            'department' => $department,
            'employment_type' => $employmentType,
            'faculty_scope' => $scope,
            'can_teach_minor' => (bool) $canTeachMinor,
            'max_units' => $maxUnits,
            'status' => $status,
            'error' => $status !== 'ready',
            'errors' => $duplicate && $errors === [] ? ['Duplicate faculty already exists.'] : $errors,
        ];
    }

    private function normalizeFacultyScope(string $value): ?string
    {
        $value = strtolower(str_replace([' ', '-'], '_', trim($value)));

        return match ($value) {
            'gened', 'general_education', 'general_ed' => Faculty::SCOPE_GENED,
            'departmental', 'department' => Faculty::SCOPE_DEPARTMENTAL,
            'cross_department', 'cross' => Faculty::SCOPE_CROSS_DEPARTMENT,
            default => null,
        };
    }

    private function parseBoolean(string $value): ?bool
    {
        return match (strtolower(trim($value))) {
            'yes', 'true', '1' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    private function normalizeEmploymentType($value): string
    {
        return str_contains(strtolower(trim((string) $value)), 'part') ? 'Part-time' : 'Full-time';
    }

    private function normalizeImportDepartment($value, ?string $scope): ?string
    {
        $department = Department::normalizeCode($value);

        if (! $department || ($scope === Faculty::SCOPE_GENED && in_array($department, ['GENED', 'GENERAL EDUCATION'], true))) {
            return null;
        }

        return $department;
    }

    private function parseMaxUnits($value, string $employmentType): int
    {
        $maxUnits = is_numeric($value) ? (int) $value : ($employmentType === 'Part-time' ? 12 : 21);

        return $employmentType === 'Part-time'
            ? max(1, min($maxUnits, 18))
            : max(1, min($maxUnits, 30));
    }

    private function findHeaderIndex(array $headers, string $targetHeader): ?int
    {
        $target = strtolower(str_replace([' ', '_', '-'], '', $targetHeader));

        foreach ($headers as $index => $header) {
            $candidate = strtolower(str_replace([' ', '_', '-'], '', (string) $header));
            if ($candidate === $target) {
                return $index;
            }
        }

        return null;
    }

    private function logAction($facultyId, string $action, string $description, ?string $department = null): void
    {
        try {
            FacultyLog::create([
                'faculty_id' => $facultyId,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'department' => Department::normalizeCode($department ?? (auth()->user()?->role === 'dean' ? auth()->user()->department : null)),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Error creating faculty log: '.$exception->getMessage());
        }
    }

    private function notifyStakeholders(Faculty $faculty, string $action): void
    {
        if ($this->isAdminOrRegistrar() && $action === 'pending') {
            return;
        }

        $this->getStakeholders($faculty->department)
            ->each(function (User $recipient) use ($faculty, $action) {
                if ($recipient->id === auth()->id()) {
                    return;
                }

                try {
                    $recipient->notify(new FacultyRequestNotification($faculty, auth()->user()->name, $action));
                } catch (\Throwable $exception) {
                    Log::error('Error sending faculty notification: '.$exception->getMessage());
                }
            });
    }

    private function notifyFacultyDeletion(string $facultyName, ?string $department, User $actor): void
    {
        $this->getStakeholders($department)
            ->each(function (User $recipient) use ($facultyName, $actor) {
                if ($recipient->id === $actor->id) {
                    return;
                }

                try {
                    $recipient->notify(new FacultyRequestNotification($facultyName, $actor->name, 'deleted'));
                } catch (\Throwable $exception) {
                    Log::error('Error sending deletion notification: '.$exception->getMessage());
                }
            });
    }

    private function notifyBulkImport(int $importCount, array $departments): void
    {
        if ($importCount < 1) {
            return;
        }

        $senderName = auth()->user()->name;

        foreach ($departments as $department) {
            $department = Department::normalizeCode($department);

            User::whereIn('department', Department::aliasesFor($department))
                ->whereIn('role', ['dean', 'oic'])
                ->get()
                ->each(function (User $leader) use ($department, $senderName) {
                    if ($leader->id === auth()->id()) {
                        return;
                    }

                    try {
                        $leader->notify(new FacultyRequestNotification("the {$department} Department", $senderName, 'bulk_added'));
                    } catch (\Throwable $exception) {
                        Log::error('Error sending department bulk import notification: '.$exception->getMessage());
                    }
                });
        }

        User::whereIn('role', ['admin', 'registrar', 'associate_dean'])
            ->get()
            ->each(function (User $leader) use ($importCount, $senderName) {
                if ($leader->id === auth()->id()) {
                    return;
                }

                try {
                    $leader->notify(new FacultyRequestNotification("{$importCount} Faculty Members", $senderName, 'bulk_added'));
                } catch (\Throwable $exception) {
                    Log::error('Error sending global bulk import notification: '.$exception->getMessage());
                }
            });
    }

    private function getStakeholders(?string $department)
    {
        $department = Department::normalizeCode($department);

        return User::query()
            ->whereIn('role', ['admin', 'registrar', 'associate_dean'])
            ->when($department, function (Builder $query) use ($department) {
                $query->orWhere(function (Builder $departmentQuery) use ($department) {
                    $departmentQuery->whereIn('department', Department::aliasesFor($department))
                        ->whereIn('role', ['dean', 'oic']);
                });
            })
            ->get()
            ->unique('id');
    }

    private function toast(string $type, string $message, string $detail = ''): void
    {
        $this->dispatch('toast', [
            'type' => $type,
            'message' => $message,
            'detail' => $detail,
        ]);
    }
}
