<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SubjectUpdatedNotification;
use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Activity;
use App\Models\Schedule;
use App\Models\Setting;
use App\Services\EdpCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ManageSubjects extends Component
{
    use WithFileUploads, WithPagination;

    // UI States
    public $showModal = false;
    public $bulkOpen = false;
    public $isEditMode = false;
    public $selectedSection = '';
    public $search = '';
    public $selectedDept = '';
    public $selectedYear = '';
    public $selectedMajor = '';
    public string $catalogMode = 'active';
    public string $selectedArchiveBatch = '';

    // Form Fields
    public $subjectId, $edp_code, $subject_code, $section, $description, $department, $units;
    public $major, $year_level;
    public $type = 'Major';
    public bool $requires_lab = false;
    public $preferred_room_type = '';
    public $duration_hours = 3;
    public $meetings_per_week = 1;

    // CSV Import Logic
    public $importFile;
    public $previewData = [];
    public $selectedSubjects = [];
    public $selectAll = false;
    public $showDuplicateConfirmModal = false;
    public $duplicateCandidateId = null;
    public bool $showProtectedDeleteModal = false;
    public bool $protectedDeleteSecondStep = false;
    public ?int $protectedDeleteSubjectId = null;
    public array $protectedDeleteImpact = [];

    // Preferred Faculty & Room (Combined Modal)
    public $assignFacultyId = null;
    public $assignRoomId = null;

    protected $listeners = ['refreshComponent' => '$refresh'];

    // Major mapping by department
    private $majorsByDept = [
        'CCS'  => ['IT' => 'Information Technology', 'ACT' => 'Assistive Computer Technology'],
        'SHTM' => ['HM' => 'Hospitality Management', 'TM' => 'Tourism Management'],
        'COC'  => ['FB' => 'Forensic Biology', 'LD' => 'Lie Detection', 'QD' => 'Questioned Documents'],
        'CTE'  => ['ED' => 'Education'],
    ];

    // Department → related department codes for faculty filtering
    private $deptFamilies = [
        'CCS'  => ['CCS', 'IT', 'ACT'],
        'SHTM' => ['SHTM', 'HM', 'TM'],
        'COC'  => ['COC', 'FB', 'LD', 'QD'],
        'CTE'  => ['CTE', 'ED'],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedDept($value) { $this->selectedMajor = ''; $this->resetPage(); }
    public function updatedSelectedYear() { $this->resetPage(); }
    public function updatedSelectedMajor() { $this->resetPage(); }
    public function updatedSelectedSection() { $this->resetPage(); }
    public function updatedCatalogMode() { $this->selectedArchiveBatch = ''; $this->selectedSubjects = []; $this->selectAll = false; $this->resetPage(); }
    public function updatedSelectedArchiveBatch() { $this->selectedSubjects = []; $this->selectAll = false; $this->resetPage(); }

    private function activePeriod(): array
    {
        return Setting::getAcademicPeriod();
    }

    private function activeSubjectsQuery()
    {
        $period = $this->activePeriod();
        return Subject::activeTerm($period['semester'], $period['school_year']);
    }

    private function archiveOptions()
    {
        if (Schema::hasTable('schedule_archives')) {
            return DB::table('schedule_archives')
                ->whereNotNull('archive_batch_id')
                ->select('archive_batch_id', 'semester', 'semester_name', 'school_year', 'total_subjects', 'archived_at')
                ->latest('archived_at')
                ->get();
        }

        return Subject::archived()
            ->whereNotNull('archive_batch')
            ->select('archive_batch', 'semester', 'academic_year')
            ->distinct()
            ->orderByDesc('archive_batch')
            ->get()
            ->map(fn ($archive) => (object) [
                'archive_batch_id' => $archive->archive_batch,
                'semester'         => $archive->semester,
                'semester_name'    => Setting::semesterDisplayName($archive->semester, $archive->academic_year),
                'school_year'      => $archive->academic_year,
                'total_subjects'   => null,
                'archived_at'      => null,
            ]);
    }

    // ============================================================
    // SMART PREFERENCE FILTERING
    // ============================================================

    /**
     * Return eligible faculties for a given subject based on smart rules:
     *   - Major subject  → faculties in the same department family (e.g. CCS/IT/ACT)
     *                      OR GenEd faculties
     *   - Minor subject  → GenEd faculties + faculties with can_teach_minor = true
     *                      OR cross_department faculties
     *   - Always exclude rejected/pending faculties
     */
    public function getEligibleFacultiesForSubject(int $subjectId): array
    {
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return [];
        }

        $isMinor    = strtolower($subject->type ?? '') === 'minor';
        $department = strtoupper($subject->department ?? '');
        $major      = strtoupper($subject->major ?? '');

        // Build the department family codes
        $familyCodes = $this->deptFamilies[$department] ?? [$department];
        // Also include the major code itself if not already there
        if ($major && ! in_array($major, $familyCodes)) {
            $familyCodes[] = $major;
        }

        $query = Faculty::approved();

        if ($isMinor) {
            // Minor: GenEd, cross_department, or can_teach_minor
            $query->where(function ($q) {
                $q->where('faculty_scope', 'gened')
                  ->orWhere('faculty_scope', 'cross_department')
                  ->orWhere('can_teach_minor', true);
            });
        } else {
            // Major: same department family OR GenEd
            $query->where(function ($q) use ($familyCodes) {
                $q->whereIn('department', $familyCodes)
                  ->orWhere('faculty_scope', 'gened');
            });
        }

        return $query->orderBy('full_name')
            ->get(['id', 'full_name', 'department', 'faculty_scope', 'employment_type'])
            ->map(function (Faculty $f) {
                $scopeLabel = match ($f->faculty_scope) {
                    'gened'            => 'GenEd',
                    'cross_department' => 'Cross-Dept',
                    default            => $f->department ?? 'Dept',
                };
                return [
                    'id'            => $f->id,
                    'full_name'     => $f->full_name,
                    'department'    => $f->department,
                    'scope_label'   => $scopeLabel,
                    'employment_type' => $f->employment_type ?? 'Full-time',
                ];
            })
            ->toArray();
    }

    /**
     * Return eligible rooms for a given subject based on smart rules:
     *   - Major subject  → Labs preferred (filtered by subject's department/major specialization)
     *                      Lecture rooms also shown but grouped separately
     *   - Minor/GenEd    → Lecture rooms first, all rooms available
     *   - Always shows all rooms but flags recommended ones
     */
    public function getEligibleRoomsForSubject(int $subjectId): array
    {
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return [];
        }

        $isMinor        = strtolower($subject->type ?? '') === 'minor';
        $requiresLab    = (bool) ($subject->requires_lab ?? false);
        $prefRoomType   = strtoupper($subject->preferred_room_type ?? '');
        $major          = strtoupper($subject->major ?? '');
        $department     = strtoupper($subject->department ?? '');
        $familyCodes    = $this->deptFamilies[$department] ?? [$department];
        if ($major && ! in_array($major, $familyCodes)) {
            $familyCodes[] = $major;
        }

        $rooms = Room::orderBy('room_name')->get();

        return $rooms->map(function (Room $room) use ($isMinor, $requiresLab, $prefRoomType, $familyCodes, $major, $department) {
            $roomType         = strtoupper($room->type ?? '');
            $roomSpec         = strtoupper($room->specialization ?? '');
            $isLab            = str_contains($roomType, 'LAB') || str_contains($roomType, 'LABORATORY');
            $isLecture        = str_contains($roomType, 'LECTURE') || str_contains($roomType, 'CLASSROOM');

            // Check if room specialization matches the subject's department family
            $specMatchesDept  = false;
            foreach ($familyCodes as $code) {
                if ($roomSpec && str_contains($roomSpec, $code)) {
                    $specMatchesDept = true;
                    break;
                }
            }
            $isGeneral = in_array($roomSpec, ['GENERAL', 'GEN', 'ALL', 'COMMON', 'MINOR', '', 'LECTURE', 'CLASSROOM']);

            // Determine recommendation tier
            if ($isMinor) {
                // Minor: Lecture rooms preferred
                if ($isLecture || $isGeneral) {
                    $tier = 'recommended';
                    $reason = 'Lecture / General (Minor subject)';
                } else {
                    $tier = 'available';
                    $reason = 'Lab room (non-standard for minor)';
                }
            } else {
                // Major subject logic
                if ($requiresLab || str_contains($prefRoomType, 'LAB')) {
                    // Needs a lab
                    if ($isLab && $specMatchesDept) {
                        $tier = 'recommended';
                        $reason = "Lab — matches {$major}/{$department}";
                    } elseif ($isLab && $isGeneral) {
                        $tier = 'recommended';
                        $reason = 'General Lab';
                    } elseif ($isLab) {
                        $tier = 'available';
                        $reason = 'Lab (different specialization)';
                    } else {
                        $tier = 'available';
                        $reason = 'Lecture (subject needs lab)';
                    }
                } else {
                    // Lecture preferred
                    if ($isLecture && ($specMatchesDept || $isGeneral)) {
                        $tier = 'recommended';
                        $reason = 'Lecture room';
                    } elseif ($isLab && $specMatchesDept) {
                        $tier = 'available';
                        $reason = "Lab — matches {$major} (if needed)";
                    } else {
                        $tier = 'available';
                        $reason = $roomType ?: 'Room';
                    }
                }
            }

            return [
                'id'         => $room->id,
                'room_name'  => $room->room_name,
                'type'       => $room->type,
                'specialization' => $room->specialization,
                'capacity'   => $room->capacity,
                'floor'      => $room->floor,
                'tier'       => $tier,   // 'recommended' | 'available'
                'reason'     => $reason,
            ];
        })
        ->sortBy(fn ($r) => $r['tier'] === 'recommended' ? 0 : 1)
        ->values()
        ->toArray();
    }

    /**
     * Return a smart hint message for the preference modal based on the subject.
     */
    public function getSmartHintForSubject(int $subjectId): array
    {
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return ['faculty_hint' => '', 'room_hint' => '', 'subject_info' => []];
        }

        $isMinor     = strtolower($subject->type ?? '') === 'minor';
        $requiresLab = (bool) ($subject->requires_lab ?? false);
        $major       = strtoupper($subject->major ?? '');
        $department  = strtoupper($subject->department ?? '');

        $facultyHint = $isMinor
            ? "Minor subject: showing GenEd and cross-department faculty who can teach minor subjects."
            : "Major subject ({$major} / {$department}): showing faculty from the {$department} department and GenEd faculty.";

        $roomHint = $isMinor
            ? "Minor subject: lecture rooms are recommended."
            : ($requiresLab
                ? "This subject requires a lab: lab rooms matching {$major}/{$department} are recommended."
                : "Major subject: showing all rooms. Lab rooms matching {$major}/{$department} are highlighted.");

        return [
            'faculty_hint' => $facultyHint,
            'room_hint'    => $roomHint,
            'subject_info' => [
                'subject_code' => $subject->subject_code,
                'description'  => $subject->description,
                'type'         => $subject->type,
                'major'        => $major,
                'department'   => $department,
                'requires_lab' => $requiresLab,
            ],
        ];
    }

    // ============================================================
    // AVAILABLE MAJORS
    // ============================================================

    public function getAvailableMajorsProperty()
    {
        if (empty($this->department)) {
            return [];
        }
        return $this->majorsByDept[strtoupper($this->department)] ?? [];
    }

    // ============================================================
    // EDP CODE GENERATION
    // ============================================================

    public function updatedMajor($value)
    {
        if (! empty($value) && ! empty($this->year_level)) {
            $this->generateEdpCode();
        }
    }

    public function updatedYearLevel($value)
    {
        if (! empty($value) && ! empty($this->major)) {
            $this->generateEdpCode();
        }
    }

    private function generateEdpCode(): void
    {
        if (empty($this->major) || empty($this->year_level) || empty($this->department)) {
            $this->edp_code = '';
            return;
        }
        $period = $this->activePeriod();
        $this->edp_code = Subject::generateEdpCode(
            strtoupper($this->major),
            (int) $this->year_level,
            $period['school_year'],
            $period['semester'],
            $this->subjectId ? (int) $this->subjectId : null
        );
    }

    public function getSubjectCodeDuplicateProperty()
    {
        if (empty($this->subject_code) || empty($this->major) || empty($this->year_level) || empty($this->section)) {
            return false;
        }
        $query = $this->activeSubjectsQuery()
            ->where('subject_code', strtoupper($this->subject_code))
            ->where('major',        strtoupper($this->major))
            ->where('year_level',   (int) $this->year_level)
            ->where('section',      strtoupper($this->section));
        if ($this->isEditMode && $this->subjectId) {
            $query->where('id', '!=', $this->subjectId);
        }
        return $query->exists();
    }

    private function normalizeYearLevel(mixed $raw): int
    {
        $edpService = app(\App\Services\EdpCodeService::class);
        return $edpService->yearLevelDigit($raw ?? 1);
    }

    private function validateDepartmentAccess($dept): bool
    {
        $user      = auth()->user();
        $userRole  = strtolower($user->role ?? '');
        $powerRoles = ['admin', 'registrar', 'associate_dean'];
        if (in_array($userRole, $powerRoles)) {
            return true;
        }
        if (in_array($userRole, ['dean', 'oic'])) {
            return strtoupper($dept) === strtoupper($user->department);
        }
        return false;
    }

    // ============================================================
    // CSV IMPORT
    // ============================================================

    public function updatedImportFile()
    {
        $this->validate(['importFile' => 'required|mimes:csv,txt|max:10240']);
        usleep(500000);
        $path = $this->importFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headerRow = array_shift($data);
        $normalizedHeader = array_map(fn ($h) => strtolower(trim($h)), $headerRow);

        if (in_array('room_name', $normalizedHeader) || in_array('building', $normalizedHeader)) {
            $this->abortImport('Room Registry'); return;
        }
        if (in_array('faculty_name', $normalizedHeader) || in_array('employee_id', $normalizedHeader)) {
            $this->abortImport('Faculty Directory'); return;
        }

        $required    = ['edp_code', 'subject_code', 'section', 'description', 'major', 'year_level', 'units', 'department', 'duration_hours', 'meetings_per_week'];
        $missing     = array_diff($required, $normalizedHeader);
        $hasSubjectType = in_array('type', $normalizedHeader, true) || in_array('subject_type', $normalizedHeader, true);

        if ($missing || ! $hasSubjectType) {
            $this->abortImport('Invalid Subject Template'); return;
        }

        $indexes    = array_flip($normalizedHeader);
        $period     = $this->activePeriod();
        $edpService = app(EdpCodeService::class);

        $this->previewData = collect(array_slice($data, 0, 10))
            ->map(function ($row, $index) use ($indexes, $period, $edpService) {
                $edp    = strtoupper(trim($row[$indexes['edp_code']] ?? ''));
                $exists = $edp !== '' && Subject::edpExistsInWorkspace($edp, $period['school_year'], $period['semester']);
                $formatLabel = $edp !== '' ? $edpService->formatLabel($edp) : 'empty';
                $semesterMismatch = $formatLabel === 'new'
                    && ! $edpService->validateSemesterMatch($edp, $period['semester']);
                return [
                    'row'               => $index + 2,
                    'edp_code'          => $edp,
                    'subject'           => $row[$indexes['subject_code']] ?? '',
                    'exists'            => $exists,
                    'format_label'      => $formatLabel,
                    'semester_mismatch' => $semesterMismatch,
                ];
            })
            ->toArray();
    }

    private function abortImport($detectedType): void
    {
        $this->reset(['importFile', 'previewData']);
        $this->dispatch('toast', [
            'type'    => 'warning',
            'message' => 'Incorrect CSV Detected',
            'detail'  => "This file appears to be for the {$detectedType}. Please upload the Subject CSV template instead.",
        ]);
    }

    public function importSubjects()
    {
        if (! $this->importFile) { return; }

        $path   = $this->importFile->getRealPath();
        $file   = fopen($path, 'r');
        $header = true;
        $indexes = [];
        $count        = 0;
        $skipped      = 0;
        $formatErrors   = [];
        $semesterErrors = [];
        $detectedDept = null;
        $actor        = auth()->user();
        $userRole    = strtolower($actor->role);
        $powerRoles  = ['admin', 'registrar', 'associate_dean'];
        $isPowerUser = in_array($userRole, $powerRoles);
        $edpService = app(EdpCodeService::class);
        $period     = $this->activePeriod();
        $rowNumber  = 1;

        if (empty($period['semester']) || empty($period['school_year'])) {
            fclose($file);
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No Active Workspace', 'detail' => 'No active semester workspace found.']);
            return;
        }

        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            if ($header) {
                $indexes = array_flip(array_map(fn ($h) => strtolower(trim($h)), $row));
                $header  = false;
                continue;
            }
            $rowNumber++;
            $value = fn (string $key, $default = '') => trim((string) ($row[$indexes[$key] ?? -1] ?? $default));
            $typeColumn = array_key_exists('subject_type', $indexes) ? 'subject_type' : 'type';

            if ($value('edp_code') === '') { $skipped++; continue; }

            $edpCode  = strtoupper($value('edp_code'));
            $rowDept  = strtoupper($value('department'));

            if (! $edpService->isValidForCreation($edpCode)) {
                $formatErrors[] = $edpService->validationMessage($edpCode, $rowNumber);
                $skipped++;
                continue;
            }
            if (! $edpService->validateSemesterMatch($edpCode, $period['semester'])) {
                $semesterErrors[] = $edpService->semesterMismatchMessage($edpCode, $period['semester'], $rowNumber);
                $skipped++;
                continue;
            }
            if (! $this->validateDepartmentAccess($rowDept)) { $skipped++; continue; }
            if (! $detectedDept && ! empty($rowDept)) { $detectedDept = $rowDept; }
            $exists  = Subject::edpExistsInWorkspace($edpCode, $period['school_year'], $period['semester']);
            if ($exists) { \Log::warning("CSV import row {$rowNumber}: {$edpCode} already exists."); $skipped++; continue; }

            $rowMajor      = strtoupper($value('major'));
            $rowYearLevel  = $this->normalizeYearLevel($value('year_level', 1));
            $rawDuration   = $value('duration_hours', '3');
            $rawType       = $value($typeColumn, 'Major');
            $rawMeetings   = (int) $value('meetings_per_week', 1);
            $rawUnits      = (int) $value('units', 3);
            $clampedUnits  = max(3, min(5, $rawUnits));
            $section       = strtoupper($value('section', 'A'));
            $normalizedType = str_contains(strtolower($rawType), 'minor') ? 'Minor' : 'Major';
            $specialization = strtoupper($value('specialization', $rowMajor));
            $requiresLab = filter_var($value('requires_lab', false), FILTER_VALIDATE_BOOLEAN)
                || str_contains(strtoupper($value('preferred_room_type', '')), 'LAB')
                || str_contains(strtoupper($rawType.' '.$value('description').' '.$specialization), 'LAB');
            $preferredRoomType = strtoupper($value('preferred_room_type', $requiresLab ? 'LAB' : 'LECTURE'));

            Subject::create([
                'edp_code'          => $edpCode,
                'subject_code'      => strtoupper($value('subject_code')),
                'section'           => $section,
                'description'       => $value('description'),
                'major'             => $rowMajor,
                'year_level'        => $rowYearLevel,
                'units'             => $clampedUnits,
                'department'        => $rowDept,
                'duration_hours'    => (float) filter_var($rawDuration, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 3,
                'meetings_per_week' => $rawMeetings,
                'type'              => $normalizedType,
                'subject_type'      => $rawType,
                'requires_lab'      => $requiresLab,
                'preferred_room_type' => $preferredRoomType,
                'specialization'    => $specialization,
                'semester'          => $period['semester'],
                'school_year'       => $period['school_year'],
                'academic_year'     => $period['school_year'],
                'workspace_key'     => $period['workspace_key'],
                'is_archived'       => false,
            ]);
            $count++;
        }
        fclose($file);

        if (! empty($semesterErrors)) {
            $semSample = array_slice($semesterErrors, 0, 3);
            $more = count($semesterErrors) > 3 ? (' … and ' . (count($semesterErrors) - 3) . ' more.') : '';
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Wrong Semester in EDP Code', 'detail' => implode(' | ', $semSample) . $more]);
        }
        if (! empty($formatErrors)) {
            $errorSample = array_slice($formatErrors, 0, 5);
            $more = count($formatErrors) > 5 ? (' … and ' . (count($formatErrors) - 5) . ' more.') : '';
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid EDP Code Format', 'detail' => implode(' | ', $errorSample) . $more]);
        }

        if ($count > 0) {
            $targetDept = ! empty($this->selectedDept) ? $this->selectedDept : ($detectedDept ?? $actor->department ?? 'General');
            Activity::create(['user_id' => $actor->id, 'action' => 'Import', 'module' => 'Subjects', 'description' => "Successfully batch-imported {$count} subjects into the {$targetDept} department." . ($skipped > 0 ? " ({$skipped} rows skipped.)" : ''),]);
            $recipients = User::where('id', '!=', $actor->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
            if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification((object) ['subject_code' => 'BATCH IMPORT', 'subject_description' => "{$count} Subjects synchronized for {$targetDept}"], 'subject_imported')); }
            $this->dispatch('notify', ['type' => 'success', 'title' => 'CATALOG SYNCED', 'message' => "Successfully batch-imported {$count} subjects." . ($skipped > 0 ? " ({$skipped} skipped.)" : ''), 'sender_name' => $actor->name]);
            $this->dispatch('subjectUpdated');
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Import Complete', 'detail' => "{$count} subjects added for {$targetDept}." . ($skipped > 0 ? " {$skipped} rows were skipped." : '')]);
        } else {
            $allRejected = ! empty($formatErrors) || ! empty($semesterErrors);
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Import Failed', 'detail' => ! $allRejected ? 'No valid subjects found or unauthorized department access.' : 'All rows were rejected. Check EDP codes format and semester digit.']);
        }

        $this->reset(['importFile', 'bulkOpen', 'previewData']);
    }

    // ============================================================
    // MODAL MANAGEMENT
    // ============================================================

    public function openModal()
    {
        if ($this->catalogMode !== 'active') {
            $this->catalogMode = 'active';
            $this->selectedArchiveBatch = '';
        }
        $this->resetValidation();
        $this->resetExcept(['selectedDept', 'search', 'selectedYear', 'selectedMajor', 'selectedSection']);
        $this->isEditMode       = false;
        $this->units            = 3;
        $this->type             = 'Major';
        $this->requires_lab     = false;
        $this->preferred_room_type = '';
        $this->duration_hours   = 3;
        $this->meetings_per_week = 1;
        $this->major            = '';
        $this->year_level       = '';
        $this->edp_code         = '';
        $this->subject_code     = '';
        $this->section          = '';
        $user     = auth()->user();
        $userRole = strtolower($user->role);
        $this->department = in_array($userRole, ['admin', 'registrar', 'associate_dean']) ? '' : $user->department;
        $this->showModal = true;
    }

    public function editSubject($id)
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before editing subjects.']);
            return;
        }
        $this->resetValidation();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);
        if (! $this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to edit subjects in this department.']);
            return;
        }
        $this->isEditMode        = true;
        $this->subjectId         = $subject->id;
        $this->edp_code          = $subject->edp_code;
        $this->subject_code      = $subject->subject_code;
        $this->section           = $subject->section;
        $this->description       = $subject->description;
        $this->units             = $subject->units;
        $this->type              = $subject->type ?? 'Major';
        $this->requires_lab      = (bool) ($subject->requires_lab ?? false);
        $this->preferred_room_type = $subject->preferred_room_type ?? '';
        $this->duration_hours    = $subject->duration_hours ?? 3;
        $this->meetings_per_week = $subject->meetings_per_week ?? 1;
        $this->major             = $subject->major ?? '';
        $this->year_level        = $subject->year_level ?? 1;
        $this->department        = $subject->department;
        $this->showModal = true;
    }

    public function updatedUnits($value)
    {
        if (! is_numeric($value) || $value < 3) {
            $this->units = 3;
        } elseif ($value > 5) {
            $this->units = 5;
        }
    }

    // ============================================================
    // SAVE SUBJECT (Create / Update)
    // ============================================================

    public function saveSubject()
    {
        if ($this->catalogMode !== 'active') {
            $this->addError('edp_code', 'Archived subjects are read only.');
            return;
        }
        $user        = auth()->user();
        $userRole    = strtolower($user->role ?? '');
        $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
        if (! $isPowerUser && ! $this->validateDepartmentAccess($this->department)) {
            $this->addError('department', 'You do not have permission to manage subjects in this department.');
            return;
        }
        $edpUpper         = strtoupper(trim($this->edp_code));
        $sectionUpper     = strtoupper($this->section);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();

        $this->validate([
            'edp_code'          => ['required', 'regex:/^[A-Z]{2,4}-\d{7}$/', Rule::unique('subjects', 'edp_code')->where(fn ($query) => $query->where('school_year', $period['school_year'])->where('semester', $period['semester']))->ignore($this->subjectId)],
            'subject_code'      => 'required',
            'section'           => 'required|max:10',
            'department'        => 'required',
            'major'             => 'required',
            'year_level'        => 'required|integer|min:1|max:4',
            'description'       => 'required',
            'units'             => 'required|integer|min:3|max:5',
            'type'              => 'required|in:Major,Minor',
            'requires_lab'      => 'boolean',
            'preferred_room_type' => 'nullable|string|max:80',
            'duration_hours'    => 'required|numeric|min:1|max:10',
            'meetings_per_week' => 'required|integer|min:1|max:5',
        ], [
            'edp_code.required' => 'The EDP code is required.',
            'edp_code.regex'    => 'Invalid EDP code format. Required: [MAJOR]-[YY][SEM][LEVEL][SEQ] — e.g. IT-2611001.',
            'edp_code.unique'   => "EDP code \"{$edpUpper}\" already exists in {$period['semester']} {$period['school_year']}.",
        ]);

        $edpService = app(EdpCodeService::class);
        if (! $edpService->isNew($edpUpper)) {
            $this->addError('edp_code', $edpService->validationMessage($edpUpper));
            return;
        }
        if (! $edpService->validateSemesterMatch($edpUpper, $period['semester'])) {
            $this->addError('edp_code', $edpService->semesterMismatchMessage($edpUpper, $period['semester']));
            return;
        }
        if (! $this->isEditMode && $this->getSubjectCodeDuplicateProperty()) {
            $this->addError('subject_code', "Subject code '{$subjectCodeUpper}' already exists in Section {$sectionUpper} for {$majorUpper} - Year {$this->year_level}.");
            return;
        }
        if (! $this->isEditMode && Subject::edpExistsInWorkspace($edpUpper, $period['school_year'], $period['semester'])) {
            $this->addError('edp_code', "EDP code '{$edpUpper}' already exists in {$period['semester']} {$period['school_year']}.");
            return;
        }

        $this->executeSave();
    }

    public function executeSave()
    {
        $user             = auth()->user();
        $deptUpper        = strtoupper($this->department);
        $edpUpper         = strtoupper($this->edp_code);
        $subjectCodeUpper = strtoupper($this->subject_code);
        $majorUpper       = strtoupper($this->major);
        $period           = $this->activePeriod();
        $normalizedType = in_array($this->type, ['Major', 'Minor']) ? $this->type : 'Major';

        $subject = Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'edp_code'          => $edpUpper,
                'subject_code'      => $subjectCodeUpper,
                'section'           => strtoupper($this->section),
                'description'       => $this->description,
                'major'             => $majorUpper,
                'year_level'        => (int) $this->year_level,
                'units'             => (int) $this->units,
                'type'              => $normalizedType,
                'subject_type'      => $normalizedType,
                'requires_lab'      => (bool) $this->requires_lab,
                'preferred_room_type' => $this->preferred_room_type ?: ((bool) $this->requires_lab ? 'LAB' : 'LECTURE'),
                'specialization'    => $majorUpper,
                'duration_hours'    => (float) $this->duration_hours,
                'meetings_per_week' => (int) $this->meetings_per_week,
                'department'        => $deptUpper,
                'semester'          => $period['semester'],
                'school_year'       => $period['school_year'],
                'academic_year'     => $period['school_year'],
                'workspace_key'     => $period['workspace_key'],
                'is_archived'       => false,
                'archived_at'       => null,
                'archive_batch'     => null,
            ]
        );

        $this->logActivityAndNotify($subject, $user, $deptUpper);
        $this->showDuplicateConfirmModal = false;
        $this->showModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->isEditMode ? 'Subject Updated' : 'Subject Created', 'detail' => "{$subject->subject_code} is now synchronized."]);
        $this->dispatch('subjectUpdated');
        $this->completeFormReset();
    }

    private function completeFormReset(): void
    {
        $this->reset(['edp_code', 'subject_code', 'section', 'description', 'units', 'type', 'duration_hours', 'major', 'year_level', 'department', 'subjectId', 'isEditMode', 'meetings_per_week', 'requires_lab', 'preferred_room_type']);
    }

    private function logActivityAndNotify($subject, $user, $deptUpper): void
    {
        Activity::create(['user_id' => $user->id, 'action' => $this->isEditMode ? 'Update' : 'Add', 'module' => 'Subjects', 'description' => $this->isEditMode ? "Updated {$subject->subject_code} in {$deptUpper}." : "Manually added {$subject->subject_code} to {$deptUpper}."]);
        $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($deptUpper) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($deptUpper) { $q->whereIn('role', ['dean', 'oic'])->where('department', $deptUpper); }); })->get();
        if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification($subject, $this->isEditMode ? 'updated' : 'created')); }
    }

    // ============================================================
    // BULK OPERATIONS
    // ============================================================

    public function bulkDuplicate()
    {
        if ($this->catalogMode !== 'active') {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before duplicating subjects.']);
            return;
        }
        $count = count($this->selectedSubjects);
        if ($count === 0) return;
        $actor           = auth()->user();
        $duplicatedCount = 0;
        $skippedCount    = 0;
        $skippedReasons  = [];
        $period          = $this->activePeriod();

        foreach ($this->selectedSubjects as $id) {
            $original = $this->activeSubjectsQuery()->find($id);
            if (! $original) continue;
            if (! $this->validateDepartmentAccess($original->department)) { $skippedCount++; continue; }
            $currentSection = strtoupper($original->section ?: 'A');
            $nextSection    = $currentSection === 'Z' ? 'AA' : ($currentSection === 'AA' ? 'AB' : chr(ord($currentSection) + 1));
            $newEdp = Subject::generateEdpCode($original->major ?: strtok((string) $original->edp_code, '-'), (int) $original->year_level, $period['school_year'], $period['semester']);
            $subjectExistsInNextSection = $this->activeSubjectsQuery()->where('subject_code', strtoupper($original->subject_code))->where('section', $nextSection)->where('major', $original->major)->where('year_level', $original->year_level)->where('department', $original->department)->exists();
            if ($subjectExistsInNextSection) { $skippedCount++; $skippedReasons[] = "{$original->subject_code} in Section {$nextSection} already exists"; continue; }
            if (Subject::edpExistsInWorkspace($newEdp, $period['school_year'], $period['semester'])) { $skippedCount++; continue; }
            try {
                Subject::create(['edp_code' => $newEdp, 'subject_code' => $original->subject_code, 'section' => $nextSection, 'description' => $original->description, 'major' => $original->major, 'year_level' => $original->year_level, 'units' => $original->units, 'department' => $original->department, 'type' => $original->type ?? 'Major', 'subject_type' => $original->subject_type, 'requires_lab' => (bool) ($original->requires_lab ?? false), 'preferred_room_type' => $original->preferred_room_type, 'specialization' => $original->specialization, 'duration_hours' => $original->duration_hours, 'meetings_per_week' => $original->meetings_per_week ?? 1, 'semester' => $period['semester'], 'school_year' => $period['school_year'], 'academic_year' => $period['school_year'], 'workspace_key' => $period['workspace_key'], 'is_archived' => false]);
                $duplicatedCount++;
            } catch (\Exception $e) { \Log::error("Error duplicating subject {$original->id}: " . $e->getMessage()); $skippedCount++; }
        }

        Activity::create(['user_id' => $actor->id, 'action' => 'Bulk Duplicate', 'module' => 'Subjects', 'description' => "Created {$duplicatedCount} new subject sections via bulk duplication." . ($skippedCount > 0 ? " ({$skippedCount} skipped)" : '')]);
        $this->reset(['selectedSubjects', 'selectAll']);
        $this->dispatch('subjectUpdated');

        if ($skippedCount > 0) {
            $detail = "Duplicated {$duplicatedCount} subjects, {$skippedCount} skipped.";
            if (! empty($skippedReasons)) { $detail .= ' Reasons: ' . implode(', ', array_slice($skippedReasons, 0, 3)); if (count($skippedReasons) > 3) { $detail .= ', and more…'; } }
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Bulk Duplicate Partial', 'detail' => $detail]);
        } else {
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Bulk Duplicate Complete', 'detail' => "{$duplicatedCount} subjects have been copied to the next section."]);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($this->catalogMode !== 'active') { $this->selectAll = false; $this->selectedSubjects = []; return; }
        if ($value) {
            $user        = auth()->user();
            $userRole    = strtolower($user->role ?? '');
            $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
            $query = $this->activeSubjectsQuery();
            if (! $isPowerUser) { $query->where('department', $user->department); } elseif (! empty($this->selectedDept)) { $query->where('department', $this->selectedDept); }
            if (! empty($this->selectedSection)) { $query->where('section', $this->selectedSection); }
            if (! empty($this->search)) { $query->where(function ($q) { $q->where('subject_code', 'like', "%{$this->search}%")->orWhere('edp_code', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%"); }); }
            if (! empty($this->selectedYear)) { $query->where('year_level', $this->selectedYear); }
            if (! empty($this->selectedMajor)) { $query->where('major', strtoupper($this->selectedMajor)); }
            $this->selectedSubjects = $query->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedSubjects = [];
        }
    }

    public function deleteSelected()
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Switch back to the current semester before editing subjects.']); return; }
        $count = count($this->selectedSubjects);
        $user  = auth()->user();
        if ($count > 0) {
            $sampleSubject = $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->first();
            $targetDept    = $sampleSubject ? $sampleSubject->department : ($user->department ?? 'General');
            if (! $this->validateDepartmentAccess($targetDept)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
            $protectedCount = Schedule::activeTerm()->whereIn('subject_id', $this->selectedSubjects)->where('status', Schedule::STATUS_FINALIZED)->distinct('subject_id')->count('subject_id');
            if ($protectedCount > 0) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Finalized Subjects Protected', 'detail' => "{$protectedCount} selected subject(s) are finalized. Delete them individually to complete the double confirmation."]); return; }
            $this->activeSubjectsQuery()->whereIn('id', $this->selectedSubjects)->delete();
            Activity::create(['user_id' => $user->id, 'action' => 'Delete', 'module' => 'Subjects', 'description' => "Bulk removed {$count} subjects from the {$targetDept} catalog."]);
            $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
            if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification((object) ['subject_code' => 'BATCH PURGE', 'subject_description' => "{$count} Records removed from {$targetDept}"], 'deleted')); }
            $this->dispatch('notify', ['type' => 'error', 'title' => 'REGISTRY PURGED', 'message' => "Successfully removed {$count} subjects from the database.", 'sender_name' => $user->name]);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Batch Deleted', 'detail' => "{$count} subjects successfully removed."]);
            $this->dispatch('subjectUpdated');
            $this->reset(['selectedSubjects', 'selectAll']);
        }
    }

    public function confirmDeleteSubject($id): void
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Archived subjects cannot be edited or deleted from this view.']); return; }
        $subject = $this->activeSubjectsQuery()->findOrFail($id);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
        $finalizedSchedules = Schedule::activeTerm()->where('subject_id', $subject->id)->where('status', Schedule::STATUS_FINALIZED)->with(['room:id,room_name', 'faculty:id,full_name'])->get();
        if ($finalizedSchedules->isEmpty()) { $this->deleteSubject($id, true); return; }
        $this->protectedDeleteSubjectId = $subject->id;
        $this->protectedDeleteSecondStep = false;
        $this->protectedDeleteImpact = ['subject_code' => $subject->subject_code, 'description' => $subject->description, 'count' => $finalizedSchedules->count(), 'schedules' => $finalizedSchedules->take(5)->map(fn (Schedule $schedule) => ['day' => $schedule->day, 'time' => $schedule->time_display, 'room' => $schedule->room?->room_name ?? 'Unassigned', 'faculty' => $schedule->faculty?->full_name ?? 'Unassigned'])->all()];
        $this->showProtectedDeleteModal = true;
    }

    public function advanceProtectedDeleteConfirmation(): void { $this->protectedDeleteSecondStep = true; }
    public function cancelProtectedDelete(): void { $this->showProtectedDeleteModal = false; $this->protectedDeleteSecondStep = false; $this->protectedDeleteSubjectId = null; $this->protectedDeleteImpact = []; }
    public function deleteProtectedSubject(): void { if (! $this->protectedDeleteSubjectId || ! $this->protectedDeleteSecondStep) { return; } $id = $this->protectedDeleteSubjectId; $this->cancelProtectedDelete(); $this->deleteSubject($id, true); }

    public function deleteSubject($id, bool $confirmedProtected = false)
    {
        if ($this->catalogMode !== 'active') { $this->dispatch('toast', ['type' => 'warning', 'message' => 'Archive is read only', 'detail' => 'Archived subjects cannot be edited or deleted from this view.']); return; }
        $user    = auth()->user();
        $subject = $this->activeSubjectsQuery()->findOrFail($id);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to delete subjects in this department.']); return; }
        $hasFinalizedSchedule = Schedule::activeTerm()->where('subject_id', $subject->id)->where('status', Schedule::STATUS_FINALIZED)->exists();
        if ($hasFinalizedSchedule && ! $confirmedProtected) { $this->confirmDeleteSubject($id); return; }
        $subjectCode = $subject->subject_code;
        $subjectDesc = $subject->description;
        $targetDept  = $subject->department;
        $recipients = User::where('id', '!=', $user->id)->where(function ($query) use ($targetDept) { $query->whereIn('role', ['admin', 'registrar', 'associate_dean'])->orWhere(function ($q) use ($targetDept) { $q->whereIn('role', ['dean', 'oic'])->where('department', $targetDept); }); })->get();
        if ($recipients->count() > 0) { Notification::send($recipients, new SubjectUpdatedNotification($subject, 'deleted')); }
        $subject->delete();
        Activity::create(['user_id' => $user->id, 'action' => 'Delete', 'module' => 'Subjects', 'description' => "Manually removed subject {$subjectCode} from the {$targetDept} catalog."]);
        $this->dispatch('notify', ['type' => 'error', 'title' => 'SUBJECT REMOVED', 'message' => "{$subjectCode} has been deleted from the registry.", 'sender_name' => $user->name]);
        $this->dispatch('toast', ['type' => 'warning', 'message' => 'Subject Deleted', 'detail' => "{$subjectCode} - {$subjectDesc} removed."]);
        $this->dispatch('subjectUpdated');
    }

    // ============================================================
    // LIFECYCLE
    // ============================================================

    public function mount()
    {
        $user     = auth()->user();
        $userRole = strtolower($user->role);
        $this->selectedDept = in_array($userRole, ['admin', 'registrar', 'associate_dean']) ? '' : $user->department;
    }

    // ============================================================
    // PREFERENCE SAVE METHODS
    // ============================================================

    public function savePreferredFaculty(?int $subjectId): void
    {
        if (! $subjectId || $this->catalogMode !== 'active') { return; }
        $subject = $this->activeSubjectsQuery()->findOrFail($subjectId);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to update subjects in this department.']); return; }
        $subject->update(['preferred_faculty_id' => $this->assignFacultyId ?: null]);
        $facultyName = $this->assignFacultyId ? (\App\Models\Faculty::find($this->assignFacultyId)?->full_name ?? 'Unknown') : 'cleared';
        \App\Models\Activity::create(['user_id' => auth()->id(), 'action' => 'Edit', 'module' => 'Subjects', 'description' => "Set preferred faculty for {$subject->subject_code} to {$facultyName}."]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Preferred Faculty Updated', 'detail' => "Saved preferred faculty for {$subject->subject_code}."]);
        $this->assignFacultyId = null;
    }

    public function savePreferredRoom(?int $subjectId): void
    {
        if (! $subjectId || $this->catalogMode !== 'active') { return; }
        $subject = $this->activeSubjectsQuery()->findOrFail($subjectId);
        if (! $this->validateDepartmentAccess($subject->department)) { $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to update subjects in this department.']); return; }
        $subject->update(['preferred_room_id' => $this->assignRoomId ?: null]);
        $roomName = $this->assignRoomId ? (\App\Models\Room::find($this->assignRoomId)?->room_name ?? 'Unknown') : 'cleared';
        \App\Models\Activity::create(['user_id' => auth()->id(), 'action' => 'Edit', 'module' => 'Subjects', 'description' => "Set preferred room for {$subject->subject_code} to {$roomName}."]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Preferred Room Updated', 'detail' => "Saved preferred room for {$subject->subject_code}."]);
        $this->assignRoomId = null;
    }

    /**
     * Save both preferred faculty and preferred room in a single operation.
     */
    public function savePreferredFacultyAndRoom(?int $subjectId): void
    {
        if (! $subjectId || $this->catalogMode !== 'active') { return; }
        $subject = $this->activeSubjectsQuery()->findOrFail($subjectId);
        if (! $this->validateDepartmentAccess($subject->department)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Access Denied', 'detail' => 'You do not have permission to update subjects in this department.']);
            return;
        }

        $subject->update([
            'preferred_faculty_id' => $this->assignFacultyId ?: null,
            'preferred_room_id'    => $this->assignRoomId ?: null,
        ]);

        $facultyName = $this->assignFacultyId ? (\App\Models\Faculty::find($this->assignFacultyId)?->full_name ?? 'Unknown') : 'None';
        $roomName    = $this->assignRoomId ? (\App\Models\Room::find($this->assignRoomId)?->room_name ?? 'Unknown') : 'None';

        \App\Models\Activity::create([
            'user_id'     => auth()->id(),
            'action'      => 'Edit',
            'module'      => 'Subjects',
            'description' => "Updated preferences for {$subject->subject_code}: Faculty → {$facultyName}, Room → {$roomName}.",
        ]);

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => 'Preferences Saved',
            'detail'  => "Faculty: {$facultyName} | Room: {$roomName}",
        ]);

        $this->assignFacultyId = null;
        $this->assignRoomId    = null;
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        $user        = auth()->user();
        $userRole    = strtolower($user->role ?? '');
        $isPowerUser = in_array($userRole, ['admin', 'registrar', 'associate_dean']);
        $period         = $this->activePeriod();
        $archiveOptions = $this->archiveOptions();
        $isArchiveMode  = $this->catalogMode === 'archive';

        $query = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') { $query->whereRaw('1 = 0'); }
        if (! $isPowerUser) { $query->where('department', $user->department); } elseif (! empty($this->selectedDept)) { $query->where('department', $this->selectedDept); }
        if (! empty($this->selectedSection)) { $query->where('section', $this->selectedSection); }
        if (! empty($this->selectedYear)) { $query->where('year_level', (int) $this->selectedYear); }
        if (! empty($this->selectedMajor)) { $query->where('major', strtoupper($this->selectedMajor)); }
        if (! empty($this->search)) { $query->where(function ($q) { $q->where('subject_code', 'like', "%{$this->search}%")->orWhere('edp_code', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%"); }); }

        $subjects = $query->with(['preferredFaculty', 'preferredRoom'])->orderBy('edp_code', 'asc')->paginate(10);

        $sectionsQuery = $isArchiveMode
            ? Subject::archived()->where('archive_batch', $this->selectedArchiveBatch)
            : Subject::activeTerm($period['semester'], $period['school_year']);

        if ($isArchiveMode && $this->selectedArchiveBatch === '') { $sectionsQuery->whereRaw('1 = 0'); }
        if (! $isPowerUser) { $sectionsQuery->where('department', $user->department); } elseif ($isPowerUser && ! empty($this->selectedDept)) { $sectionsQuery->where('department', $this->selectedDept); }

        $sections = $sectionsQuery->distinct()->pluck('section')->filter()->sort()->values();

        return view('livewire.manage-subjects', [
            'subjects'        => $subjects,
            'availableMajors' => $this->getAvailableMajorsProperty(),
            'sections'        => $sections,
            'activities'      => \App\Models\Activity::with('user')->latest()->take(10)->get(),
            'isPowerUser'     => $isPowerUser,
            'activePeriod'    => $period,
            'catalogMode'     => $this->catalogMode,
            'archiveOptions'  => $archiveOptions,
            'isArchiveMode'   => $isArchiveMode,
        ]);
    }
}