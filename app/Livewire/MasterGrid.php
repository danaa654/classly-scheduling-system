<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\Scheduling\AutoGenerateScheduler;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\WithPagination;

class MasterGrid extends Component
{
    use WithPagination;

    public $searchSubject = '';
    public $selectedDept = null;
    public $selectedYear = '';
    public $selectedMajor = '';
    public $selectedSection = null;
    public $selectedType = '';

    #[Url]
    public $selectedRoomId = null;
    public $selectedRoomName = null;
    public $selectedRoomType = null;
    public $searchRoom = '';
    public $selectedRoomTypeFilter = '';
    public $selectedFloor = '';

    // Dynamic time bounds from settings
    public $dayStart = '07:00';
    public $dayEnd = '21:00';
    public $schoolYear = '2026-2027';
    public $semester = '1st';
    public $semesterName = 'First Semester 2026-2027';
    public ?array $generationSummary = null;
    public bool $showGenerateModal = false;
    public bool $showGeneratingModal = false;
    public bool $showSummaryModal = false;
    public bool $showSavingModal = false;
    public bool $showRetryingModal = false;
    public bool $showEditScheduleModal = false;
    public int $generationProcessId = 0;
    public int $saveProcessId = 0;
    public int $retryProcessId = 0;
    public ?int $retrySubjectId = null;
    public array $pendingGeneratedSchedules = [];
    public array $failedRetryInputs = [];
    public array $generatedScheduleEditInputs = [];
    public array $compatibleRoomsForEdit = [];
    public array $compatibleFacultyForEdit = [];
    public ?string $editingGeneratedScheduleKey = null;
    public $generateDepartment = null;
    public $generateMajor = null;
    public $generateYearLevel = null;
    public $generateSection = null;

    // Hard-coded lunch break
    public const LUNCH_START = '12:00';
    public const LUNCH_END = '13:00';
    
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    public const BRICK_DURATION_MINUTES = 30;
    
    public const BRICK_HEIGHT_PX = 45;
    public const TIME_COL_WIDTH = 'w-24';
    public const DAY_COL_WIDTH = 'flex-1';
    
    public const TIME_FORMAT_12H = 'h:i A';
    public const TIME_FORMAT_24H = 'H:i:s';

    public const DEPARTMENT_MAJORS = [
        'CCS' => ['IT', 'ACT'],     
        'COC' => ['FB', 'LD', 'QD'],
        'SHTM' => ['HM', 'TM'],
        'CTE' => ['ED'],
    ];

    public const DEPARTMENT_COLORS = [
        'CCS' => 'yellow',
        'IT' => 'yellow',
        'ACT' => 'yellow',
        'CTE' => 'blue',
        'ED' => 'blue',
        'COC' => 'violet',
        'FB' => 'violet',
        'LD' => 'violet',
        'QD' => 'violet',
        'SHTM' => 'orange',
        'HM' => 'orange',
        'TM' => 'orange',
    ];

    protected $listeners = [
        'refreshGrid' => '$refresh',
        'settings-updated' => 'loadSettings',
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->dayStart = Setting::where('key', 'day_start')->first()?->value ?? '07:00';
        $this->dayEnd = Setting::where('key', 'day_end')->first()?->value ?? '21:00';
        $this->schoolYear = Setting::where('key', 'school_year')->first()?->value ?? '2026-2027';
        $this->semester = Setting::where('key', 'semester')->first()?->value ?? '1st';
        $this->semesterName = Setting::where('key', 'semester_name')->first()?->value ?? 'First Semester 2026-2027';
    }

    public function updatedSearchSubject() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedYear() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedMajor() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedDept() 
    { 
        $this->selectedMajor = ''; 
        $this->resetPage(); 
    }

    public function updatedGenerateDepartment(): void
    {
        $this->generateMajor = null;
    }

    public function updatedSelectedSection() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedType() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedRoomTypeFilter() 
    { 
        $this->resetPage(); 
    }

    public function updatedSelectedFloor() 
    { 
        $this->resetPage(); 
    }

    public function hasFullAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        $role = $user->role ?? 'guest';
        return in_array($role, ['admin', 'registrar', 'associate_dean']);
    }

    public function canAutoGenerateSchedules(): bool
    {
        $role = auth()->user()?->role ?? 'guest';

        return in_array($role, ['admin', 'registrar'], true);
    }

    public function canModifyGeneratedSchedules(): bool
    {
        return $this->canAutoGenerateSchedules();
    }

    public function getUserDepartment(): ?string
    {
        $user = auth()->user();
        return $user->department ?? null;
    }

    public function canDeleteSchedule($scheduleId): bool
    {
        return $this->canAutoGenerateSchedules() && Schedule::whereKey($scheduleId)->exists();
    }

    public function getDepartmentColor($department): string
    {
        return self::DEPARTMENT_COLORS[$department] ?? 'slate';
    }

    public function formatTime12h($time): string
    {
        return Carbon::parse($time)->format(self::TIME_FORMAT_12H);
    }

    public function formatTimeRange12h($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format(self::TIME_FORMAT_12H);
        $end = Carbon::parse($endTime)->format(self::TIME_FORMAT_12H);
        return "{$start} - {$end}";
    }

    public function cleanScheduleText($value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00C2}", "\u{00A0}", "\xC2\xA0"], ' ', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    public function calculateMinutesPerMeeting($subject): float
    {
        $totalMinutes = ($subject->duration_hours ?? 0) * 60;
        $meetingsPerWeek = $subject->meetings_per_week ?? 1;

        if ($meetingsPerWeek <= 0) {
            return self::BRICK_DURATION_MINUTES;
        }

        return $totalMinutes / $meetingsPerWeek;
    }

    public function getRemainingHoursDecimal($subjectId): float
    {
        $subject = Subject::find($subjectId);
        if (!$subject) return 0;

        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        $scheduledCount = $this->getScheduledCount($subjectId);
        $usedMinutes = $minutesPerMeeting * $scheduledCount;
        $totalMinutes = ($subject->duration_hours ?? 0) * 60;
        $remainingMinutes = max(0, $totalMinutes - $usedMinutes);
        
        return round($remainingMinutes / 60, 1);
    }

    public function calculateBrickCount($subject): int
    {
        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        return (int)ceil($minutesPerMeeting / self::BRICK_DURATION_MINUTES);
    }

    public function calculateCardHeightPx($subject): int
    {
        $brickCount = $this->calculateBrickCount($subject);
        return ($brickCount * self::BRICK_HEIGHT_PX) + ($brickCount - 1);
    }

    public function calculateEndTime($startTime, $minutesToAdd): string
    {
        return Carbon::parse($startTime)
            ->addMinutes($minutesToAdd)
            ->format(self::TIME_FORMAT_24H);
    }

    private function isTimeAlignedTo30Min($time): bool
    {
        $minutes = (int)Carbon::parse($time)->format('i');
        return in_array($minutes, [0, 30], true);
    }

    public function getScheduledCount($subjectId): int
    {
        return Schedule::where('subject_id', $subjectId)->count();
    }

    public function getRemainingHours($subjectId): float
    {
        return $this->getRemainingHoursDecimal($subjectId);
    }

    public function getRemainingMeetings($subject): int
    {
        $scheduledCount = $this->getScheduledCount($subject->id);
        return max(0, ($subject->meetings_per_week ?? 1) - $scheduledCount);
    }

    private function overlapsLunchBreak($startTime, $endTime): bool
    {
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        return $slotStart < $lunchEnd && $slotEnd > $lunchStart;
    }

    public function isLunchBreakTime($startTime, $endTime): bool
    {
        return $this->overlapsLunchBreak($startTime, $endTime);
    }

    private function hasRoomConflict($roomId, $day, $startTime, $endTime): bool
    {
        $result = app(ScheduleConflictService::class)
            ->checkRoomConflict((int) $roomId, $day, $startTime, $endTime);

        return ($result['status'] ?? true) === false;
    }

    private function validateScheduleWithService(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): ?array {
        $service = app(ScheduleConflictService::class);

        $checks = [
            $service->checkRoomConflict($room->id, $day, $startTime, $endTime, $ignoreScheduleId),
            $service->checkSectionConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId),
            $service->checkFacultyConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId),
        ];

        if ($subject->faculty_id) {
            $faculty = $subject->relationLoaded('faculty')
                ? $subject->faculty
                : Faculty::select('id', 'full_name', 'availability')->find($subject->faculty_id);

            if ($faculty) {
                $checks[] = $service->checkFacultyAvailability($faculty, $day, $startTime, $endTime);
            }
        }

        foreach ($checks as $check) {
            if (($check['status'] ?? true) === false) {
                return $this->formatConflictResponse($check, $subject, $day, $startTime, $endTime);
            }
        }

        return null;
    }

    private function formatConflictResponse(array $conflict, Subject $subject, string $day, string $startTime, string $endTime): array
    {
        $details = array_merge($conflict['details'] ?? [], [
            'conflict_type' => $conflict['conflict_type'] ?? 'SCHEDULE_CONFLICT',
            'requested_subject' => $subject->subject_code,
            'requested_start' => Carbon::parse($startTime)->format(self::TIME_FORMAT_12H),
            'requested_end' => Carbon::parse($endTime)->format(self::TIME_FORMAT_12H),
            'requested_day' => $day,
        ]);

        return [
            'status' => false,
            'type' => $conflict['conflict_type'] ?? 'SCHEDULE_CONFLICT',
            'toast_type' => $conflict['type'] ?? 'error',
            'title' => $conflict['title'] ?? 'Schedule Conflict',
            'message' => $conflict['message'] ?? 'This schedule conflicts with an existing schedule.',
            'details' => $details,
        ];
    }

    private function checkCurriculumWarning(Subject $subject, Room $room): ?array
    {
        $warning = app(ScheduleConflictService::class)->checkCurriculumConflict($subject, $room);

        return ($warning['type'] ?? null) === 'warning' ? $warning : null;
    }

    public function findCompatibleRooms(int $subjectId): array
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return [];
        }

        return app(AutoGenerateScheduler::class)
            ->findCompatibleRooms($subject)
            ->map(fn (array $candidate) => [
                'room_id' => $candidate['room']->id,
                'room_name' => $candidate['room']->room_name,
                'type' => $candidate['room']->type,
                'capacity' => $candidate['room']->capacity,
                'score' => $candidate['score'],
            ])
            ->values()
            ->all();
    }

    private function findCompatibleFacultyForSubject(Subject $subject): array
    {
        $department = $this->facultyDepartmentForSubject($subject);

        return Faculty::query()
            ->approved()
            ->select('id', 'full_name', 'department')
            ->when($department !== '', fn ($query) => $query->where('department', $department))
            ->orderBy('full_name')
            ->get()
            ->map(fn (Faculty $faculty) => [
                'id' => $faculty->id,
                'full_name' => $this->cleanScheduleText($faculty->full_name),
                'department' => $this->cleanScheduleText($faculty->department),
            ])
            ->values()
            ->all();
    }

    private function facultyDepartmentForSubject(Subject $subject): string
    {
        $department = strtoupper(trim((string) $subject->department));

        if (in_array($department, ['CCS', 'COC', 'SHTM', 'CTE'], true)) {
            return $department;
        }

        $major = strtoupper(trim((string) $subject->major));

        return match ($major) {
            'IT', 'ACT' => 'CCS',
            'FB', 'LD', 'QD' => 'COC',
            'HM', 'TM' => 'SHTM',
            'ED' => 'CTE',
            default => $department,
        };
    }

    public function findAvailableTimeSlot(int $subjectId): ?array
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return null;
        }

        $rooms = Room::query()->available()->get();
        $schedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->get();

        return app(AutoGenerateScheduler::class)->findAvailableSlot($subject, $rooms, $schedules);
    }

    public function hasConflict(int $roomId, string $day, string $startTime, string $endTime, ?int $subjectId = null): bool
    {
        $schedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->get();

        if (app(AutoGenerateScheduler::class)->hasRoomConflict($schedules, $roomId, $day, $startTime, $endTime)) {
            return true;
        }

        $subject = $subjectId ? Subject::find($subjectId) : null;

        return $subject
            ? app(AutoGenerateScheduler::class)->hasSectionConflict($schedules, $subject, $day, $startTime, $endTime)
            : false;
    }

    public function hasFacultyConflict(int $facultyId, string $day, string $startTime, string $endTime): bool
    {
        $conflict = app(ScheduleConflictService::class)->checkFacultyConflict($facultyId, $day, $startTime, $endTime);

        return ($conflict['status'] ?? true) === false;
    }

    public function openGenerateModal(): void
    {
        if (!$this->canAutoGenerateSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can use AI auto generation.'
            ]);
            return;
        }

        $this->generateDepartment = $this->selectedDept;
        $this->generateMajor = $this->selectedMajor;
        $this->generateYearLevel = $this->selectedYear;
        $this->generateSection = $this->selectedSection;
        $this->showGenerateModal = true;
        $this->showGeneratingModal = false;
        $this->showSummaryModal = false;
        $this->showSavingModal = false;
        $this->showRetryingModal = false;
        $this->showEditScheduleModal = false;
        $this->retrySubjectId = null;
        $this->editingGeneratedScheduleKey = null;
        $this->generationSummary = null;
        $this->pendingGeneratedSchedules = [];
        $this->failedRetryInputs = [];
        $this->generatedScheduleEditInputs = [];
        $this->compatibleRoomsForEdit = [];
        $this->compatibleFacultyForEdit = [];
    }

    public function closeGenerateModal(): void
    {
        if ($this->showGeneratingModal || $this->showSavingModal || $this->showRetryingModal) {
            return;
        }

        $this->showGenerateModal = false;
    }

    public function startGeneration(): void
    {
        if (!$this->canAutoGenerateSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can use AI auto generation.'
            ]);
            return;
        }

        $this->loadSettings();
        $filters = $this->currentGenerationFilters();
        $missingFilters = app(AutoGenerateScheduler::class)->missingRequiredFilters($filters);

        if ($missingFilters) {
            $this->showGenerateModal = true;
            $this->showGeneratingModal = false;
            $this->showSummaryModal = false;
            $this->showSavingModal = false;
            $this->showRetryingModal = false;
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Select scheduling filters first.',
                'detail' => 'Required: Department, Major, Year Level, and Section.'
            ]);
            return;
        }

        $this->generationSummary = null;
        $this->pendingGeneratedSchedules = [];
        $this->failedRetryInputs = [];
        $this->showGenerateModal = false;
        $this->showSummaryModal = false;
        $this->showSavingModal = false;
        $this->showRetryingModal = false;
        $this->showEditScheduleModal = false;
        $this->showGeneratingModal = true;
        $this->generationProcessId++;
    }

    public function runGeneration(): void
    {
        if (!$this->showGeneratingModal) {
            return;
        }

        if (!$this->canAutoGenerateSchedules()) {
            $this->showGeneratingModal = false;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can use AI auto generation.'
            ]);
            return;
        }

        $filters = $this->currentGenerationFilters();

        try {
            $result = app(AutoGenerateScheduler::class)->generatePartialSchedules(
                $filters,
                auth()->id(),
                false
            );
        } catch (\Throwable $e) {
            $this->showGeneratingModal = false;
            $this->showGenerateModal = true;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Generation failed.',
                'detail' => $e->getMessage(),
            ]);
            return;
        }

        $scheduled = (int) $result['scheduled'];
        $failedCount = (int) $result['failed'];
        $warnings = (int) $result['warnings'];

        $detailParts = [];

        if ($failedCount > 0) {
            $reasons = collect($result['failure_reasons'] ?? [])->take(3)->implode(' ');
            $detailParts[] = "{$failedCount} failed. {$reasons}";
        }

        if ($warnings > 0) {
            $detailParts[] = "{$warnings} fallback/general room warning(s) were allowed.";
        }

        $this->showGenerationSummary($result);

        $hasNoCompatibleRoom = collect($result['failure_reasons'] ?? [])
            ->contains(fn (string $reason) => str_contains(strtolower($reason), 'no compatible room'));

        $this->dispatch('toast', [
            'type' => $scheduled > 0 ? 'success' : ($hasNoCompatibleRoom ? 'error' : 'warning'),
            'message' => "{$scheduled} schedule slot(s) generated for review.",
            'detail' => $detailParts ? implode(' ', $detailParts) : 'Review the summary, edit failed subjects if needed, then save the generated schedule.'
        ]);
    }

    public function autoGeneratePartialSchedule(): void
    {
        $this->openGenerateModal();
    }

    public function confirmGeneratedSchedules(): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can save generated schedules.'
            ]);
            return;
        }

        if ($this->showSavingModal) {
            return;
        }

        if (empty($this->pendingGeneratedSchedules)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'No generated schedules to save.',
                'detail' => 'Run AI generation first.'
            ]);
            return;
        }

        $this->showGenerateModal = false;
        $this->showGeneratingModal = false;
        $this->showSummaryModal = false;
        $this->showRetryingModal = false;
        $this->showEditScheduleModal = false;
        $this->showSavingModal = true;
        $this->saveProcessId++;
    }

    public function saveGeneratedSchedules(): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->showSavingModal = false;
            $this->showSummaryModal = (bool) $this->generationSummary;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can save generated schedules.'
            ]);
            return;
        }

        if (!$this->showSavingModal) {
            return;
        }

        if (empty($this->pendingGeneratedSchedules)) {
            $this->showSavingModal = false;
            $this->showSummaryModal = (bool) $this->generationSummary;
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'No generated schedules to save.',
                'detail' => 'Run AI generation first.'
            ]);
            return;
        }

        try {
            $result = DB::transaction(fn () => app(AutoGenerateScheduler::class)->persistGeneratedSchedules(
                $this->pendingGeneratedSchedules,
                auth()->id()
            ));
        } catch (\Throwable $e) {
            $this->showSavingModal = false;
            $this->showSummaryModal = (bool) $this->generationSummary;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Save failed.',
                'detail' => $e->getMessage(),
            ]);
            return;
        }

        $saved = (int) ($result['saved'] ?? 0);
        $failed = (int) ($result['failed'] ?? 0);
        $filters = $this->generationSummary['filters'] ?? [
            'department' => $this->generateDepartment,
            'major' => $this->generateMajor,
            'year_level' => $this->generateYearLevel,
            'section' => $this->generateSection,
        ];

        if ($saved > 0) {
            $this->syncToBlockSchedule($result['saved_items'] ?? []);
            $this->notifyScheduleStakeholders($filters, $saved, $failed);
        }

        $this->showSavingModal = false;

        if ($saved <= 0) {
            $this->showSummaryModal = (bool) $this->generationSummary;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Generated schedules were not saved.',
                'detail' => collect($result['failure_reasons'] ?? [])->first() ?? 'No valid generated schedules could be persisted.'
            ]);
            return;
        }

        $this->showSummaryModal = false;
        $this->pendingGeneratedSchedules = [];
        $this->generationSummary = null;
        $this->failedRetryInputs = [];

        $this->dispatch('toast', [
            'type' => $failed > 0 ? 'warning' : 'success',
            'message' => 'Schedule successfully generated and saved.',
            'detail' => $failed > 0 ? "{$failed} generated schedule slot(s) could not be saved due to new conflicts." : "{$saved} schedule slot(s) saved to the database."
        ]);

        $this->dispatch('refreshGrid');
    }

    public function showGenerationSummary(array $result): void
    {
        $this->pendingGeneratedSchedules = array_values($result['scheduled_items'] ?? []);
        $this->generationSummary = [
            'scheduled' => (int) ($result['scheduled'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
            'warnings' => (int) ($result['warnings'] ?? 0),
            'filters' => $result['filters'] ?? $this->currentGenerationFilters(),
            'scheduled_items' => array_slice($this->summarizeScheduledItemsForDisplay($result['scheduled_items'] ?? []), 0, 50),
            'failure_reasons' => array_slice($result['failure_reasons'] ?? [], 0, 50),
            'failed_items' => array_slice($result['failed_items'] ?? [], 0, 50),
            'fallback_warnings' => array_slice($result['fallback_warnings'] ?? [], 0, 20),
        ];

        $this->failedRetryInputs = collect($this->generationSummary['failed_items'])
            ->mapWithKeys(function (array $item) {
                $meetings = max(1, min(6, (int) ($item['meetings_per_week'] ?? 1)));

                return [
                    $item['subject_id'] => [
                        'meetings_per_week' => $meetings,
                    ],
                ];
            })
            ->all();

        $this->showGenerateModal = false;
        $this->showGeneratingModal = false;
        $this->showSavingModal = false;
        $this->showRetryingModal = false;
        $this->showSummaryModal = true;
    }

    public function retryFailedSubject(int $subjectId): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can retry generated schedules.'
            ]);
            return;
        }

        if (!$this->generationSummary || $this->showGeneratingModal || $this->showSavingModal || $this->showRetryingModal) {
            return;
        }

        $inputs = $this->failedRetryInputs[$subjectId] ?? [];

        if (!filled($inputs['meetings_per_week'] ?? null) || !is_numeric($inputs['meetings_per_week'] ?? null)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Retry details incomplete.',
                'detail' => 'Meetings Per Week is required. The scheduler will automatically choose the best room, days, and time.'
            ]);
            return;
        }

        $this->retrySubjectId = $subjectId;
        $this->showSummaryModal = false;
        $this->showEditScheduleModal = false;
        $this->showRetryingModal = true;
        $this->retryProcessId++;
    }

    public function runFailedSubjectRetry(): void
    {
        if (!$this->showRetryingModal || !$this->retrySubjectId || !$this->generationSummary) {
            return;
        }

        if (!$this->canModifyGeneratedSchedules()) {
            $this->showRetryingModal = false;
            $this->showSummaryModal = (bool) $this->generationSummary;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can retry generated schedules.'
            ]);
            return;
        }

        $subjectId = $this->retrySubjectId;
        $overrides = $this->failedRetryInputs[$subjectId] ?? [];
        $meetings = max(1, min(6, (int) ($overrides['meetings_per_week'] ?? 1)));

        $overrides = ['meetings_per_week' => $meetings];

        try {
            $retry = app(AutoGenerateScheduler::class)->previewSubjectSchedule(
                $subjectId,
                $this->generationSummary['filters'] ?? [],
                $overrides,
                $this->pendingGeneratedSchedules,
                auth()->id()
            );
        } catch (\Throwable $e) {
            $this->showRetryingModal = false;
            $this->showSummaryModal = true;
            $this->retrySubjectId = null;
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Retry failed.',
                'detail' => $e->getMessage()
            ]);
            return;
        }

        if (($retry['scheduled'] ?? 0) <= 0) {
            $this->showRetryingModal = false;
            $this->showSummaryModal = true;
            $this->retrySubjectId = null;
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Retry failed.',
                'detail' => collect($retry['failure_reasons'] ?? [])->first() ?? 'No valid slot found.'
            ]);
            return;
        }

        $firstScheduled = $retry['scheduled_items'][0] ?? null;
        $subjectCode = $firstScheduled['subject_code'] ?? null;
        $retryItems = array_values($retry['scheduled_items'] ?? []);

        $this->pendingGeneratedSchedules = array_values(array_merge(
            $this->pendingGeneratedSchedules,
            $retryItems
        ));
        $this->generationSummary['scheduled'] = count($this->pendingGeneratedSchedules);
        $this->generationSummary['scheduled_items'] = array_values(array_merge(
            $this->generationSummary['scheduled_items'],
            $this->summarizeScheduledItemsForDisplay($retryItems)
        ));
        $this->generationSummary['scheduled_items'] = array_slice($this->generationSummary['scheduled_items'], 0, 50);
        $this->generationSummary['failed_items'] = array_values(array_filter(
            $this->generationSummary['failed_items'],
            fn (array $item) => (int) $item['subject_id'] !== $subjectId
        ));
        $this->generationSummary['failure_reasons'] = array_values(array_filter(
            $this->generationSummary['failure_reasons'],
            fn (string $reason) => !$subjectCode || !str_starts_with($reason, "{$subjectCode}:")
        ));
        $this->generationSummary['failed'] = count($this->generationSummary['failed_items']);
        $this->generationSummary['warnings'] += (int) ($retry['warnings'] ?? 0);
        $this->generationSummary['fallback_warnings'] = array_slice(array_values(array_merge(
            $this->generationSummary['fallback_warnings'] ?? [],
            $retry['fallback_warnings'] ?? []
        )), 0, 20);

        unset($this->failedRetryInputs[$subjectId]);

        $this->showRetryingModal = false;
        $this->showSummaryModal = true;
        $this->retrySubjectId = null;

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Subject retry generated.',
            'detail' => 'The retry was added to the pending schedule. Review it before saving.'
        ]);
    }

    public function editGeneratedScheduleGroup(string $summaryKey): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can edit generated schedules.'
            ]);
            return;
        }

        $items = $this->pendingGeneratedGroup($summaryKey);
        $first = $items[0] ?? null;

        if (!$first) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Generated schedule not found.',
                'detail' => 'The temporary schedule may have already been removed.'
            ]);
            return;
        }

        $subject = Subject::query()
            ->with('faculty:id,full_name')
            ->find($first['subject_id'] ?? null);

        if (!$subject) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Subject not found.',
                'detail' => 'The selected generated schedule cannot be edited.'
            ]);
            return;
        }

        $days = collect($items)
            ->pluck('day')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $start = $first['raw_start_time'] ?? $first['start_time'] ?? null;

        $this->editingGeneratedScheduleKey = $summaryKey;
        $this->compatibleRoomsForEdit = $this->findCompatibleRooms((int) $subject->id);
        $this->compatibleFacultyForEdit = $this->findCompatibleFacultyForSubject($subject);
        $selectedFacultyId = (string) ($first['faculty_id'] ?? $subject->faculty_id ?? '');
        $validFacultyIds = collect($this->compatibleFacultyForEdit)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($selectedFacultyId !== '' && !in_array($selectedFacultyId, $validFacultyIds, true)) {
            $selectedFacultyId = '';
        }

        $meetingsPerWeek = max(1, min(6, (int) ($first['meetings_per_week'] ?? (count($days) ?: ($subject->meetings_per_week ?? 1)))));

        $this->generatedScheduleEditInputs = [
            'summary_key' => $summaryKey,
            'subject_id' => (int) $subject->id,
            'subject_code' => $subject->subject_code,
            'subject_name' => $subject->description,
            'faculty_department' => $this->facultyDepartmentForSubject($subject) ?: 'Department',
            'room_id' => (string) ($first['room_id'] ?? ''),
            'days' => $days,
            'start_time' => $start ? Carbon::parse($start)->format('H:i') : '',
            'meetings_per_week' => $meetingsPerWeek,
            'faculty_id' => $selectedFacultyId,
        ];

        $this->showEditScheduleModal = true;
    }

    public function closeGeneratedScheduleEdit(): void
    {
        $this->showEditScheduleModal = false;
        $this->editingGeneratedScheduleKey = null;
        $this->generatedScheduleEditInputs = [];
        $this->compatibleRoomsForEdit = [];
        $this->compatibleFacultyForEdit = [];
    }

    public function saveGeneratedScheduleEdit(): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can edit generated schedules.'
            ]);
            return;
        }

        $summaryKey = (string) ($this->generatedScheduleEditInputs['summary_key'] ?? $this->editingGeneratedScheduleKey);

        if ($summaryKey === '' || empty($this->pendingGeneratedGroup($summaryKey))) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Generated schedule not found.',
                'detail' => 'The temporary schedule may have already been removed.'
            ]);
            return;
        }

        $inputs = $this->generatedScheduleEditInputs;
        $inputs['days'] = $this->normalizePreferredDays($inputs['days'] ?? []);
        $inputs['meetings_per_week'] = max(1, min(6, (int) ($inputs['meetings_per_week'] ?? count($inputs['days']))));

        if (empty($inputs['room_id']) || empty($inputs['start_time']) || empty($inputs['days'])) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Complete the schedule edit.',
                'detail' => 'Choose a compatible room, at least one day, and a start time.'
            ]);
            return;
        }

        if (count($inputs['days']) !== (int) $inputs['meetings_per_week']) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Meeting days do not match.',
                'detail' => 'Select the same number of days as the Meetings Per Week value.'
            ]);
            return;
        }

        try {
            $result = app(AutoGenerateScheduler::class)->previewManualScheduleEdit(
                (int) $inputs['subject_id'],
                $this->generationSummary['filters'] ?? [],
                $inputs,
                $this->pendingGeneratedSchedulesExceptGroup($summaryKey),
                auth()->id()
            );
        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Edit failed.',
                'detail' => $e->getMessage()
            ]);
            return;
        }

        if (($result['scheduled'] ?? 0) <= 0) {
            if (!empty($result['conflict']) && is_array($result['conflict'])) {
                $this->dispatch('toast', [
                    'type' => $result['conflict']['toast_type'] ?? 'error',
                    'message' => $result['conflict']['title'] ?? 'Schedule Conflict Detected',
                    'detail' => $result['conflict']['message'] ?? 'The temporary edit conflicts with another schedule.'
                ]);

                $this->dispatch('show-conflict-modal', conflictData: $result['conflict']);
                return;
            }

            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Edit could not be applied.',
                'detail' => collect($result['failure_reasons'] ?? [])->first() ?? 'No valid schedule could be built from the selected edits.'
            ]);
            return;
        }

        $this->pendingGeneratedSchedules = array_values(array_merge(
            $this->pendingGeneratedSchedulesExceptGroup($summaryKey),
            $result['scheduled_items'] ?? []
        ));

        $this->mergeGenerationWarnings($result);
        $this->refreshGenerationScheduledSummary();
        $this->closeGeneratedScheduleEdit();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Generated schedule updated.',
            'detail' => 'The edit is temporary until you save the generated schedule.'
        ]);
    }

    public function removeGeneratedScheduleGroup(string $summaryKey): void
    {
        if (!$this->canModifyGeneratedSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can remove generated schedules.'
            ]);
            return;
        }

        $removedCount = count($this->pendingGeneratedGroup($summaryKey));

        if ($removedCount <= 0) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Generated schedule not found.',
                'detail' => 'The temporary schedule may have already been removed.'
            ]);
            return;
        }

        $this->pendingGeneratedSchedules = $this->pendingGeneratedSchedulesExceptGroup($summaryKey);
        $this->refreshGenerationScheduledSummary();

        if ($this->editingGeneratedScheduleKey === $summaryKey) {
            $this->closeGeneratedScheduleEdit();
        }

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Generated schedule removed.',
            'detail' => "{$removedCount} temporary meeting slot(s) removed before saving."
        ]);
    }

    public function closeGenerationSummary(): void
    {
        if ($this->showGeneratingModal || $this->showSavingModal || $this->showRetryingModal) {
            return;
        }

        $this->closeGeneratedScheduleEdit();
        $this->showSummaryModal = false;
        $this->generationSummary = null;
        $this->pendingGeneratedSchedules = [];
        $this->failedRetryInputs = [];
    }

    private function currentGenerationFilters(): array
    {
        return [
            'department' => $this->generateDepartment,
            'major' => $this->generateMajor,
            'year_level' => $this->generateYearLevel,
            'section' => $this->generateSection,
        ];
    }

    private function normalizePreferredDays(array|string|null $days): array
    {
        if (is_string($days)) {
            $days = preg_split('/[,|\/]+/', $days) ?: [];
        }

        return collect($days ?? [])
            ->map(fn ($day) => ucfirst(strtolower(trim((string) $day))))
            ->filter(fn (string $day) => in_array($day, $this->days, true))
            ->unique()
            ->values()
            ->all();
    }

    private function scheduledItemGroupKey(array $item): string
    {
        return implode('|', [
            $item['pairing_key'] ?? 'single',
            $item['subject_id'] ?? $item['subject_code'] ?? '',
            $item['room_id'] ?? $item['room'] ?? '',
            $item['raw_start_time'] ?? $item['start_time'] ?? '',
            $item['raw_end_time'] ?? $item['end_time'] ?? '',
        ]);
    }

    private function scheduledItemSummaryKey(array $item): string
    {
        return md5($this->scheduledItemGroupKey($item));
    }

    private function pendingGeneratedGroup(string $summaryKey): array
    {
        return collect($this->pendingGeneratedSchedules)
            ->filter(fn (array $item) => $this->scheduledItemSummaryKey($item) === $summaryKey)
            ->values()
            ->all();
    }

    private function pendingGeneratedSchedulesExceptGroup(string $summaryKey): array
    {
        return collect($this->pendingGeneratedSchedules)
            ->reject(fn (array $item) => $this->scheduledItemSummaryKey($item) === $summaryKey)
            ->values()
            ->all();
    }

    private function refreshGenerationScheduledSummary(): void
    {
        if (!$this->generationSummary) {
            return;
        }

        $this->generationSummary['scheduled'] = count($this->pendingGeneratedSchedules);
        $this->generationSummary['scheduled_items'] = array_slice(
            $this->summarizeScheduledItemsForDisplay($this->pendingGeneratedSchedules),
            0,
            50
        );
    }

    private function mergeGenerationWarnings(array $result): void
    {
        if (!$this->generationSummary) {
            return;
        }

        $this->generationSummary['warnings'] += (int) ($result['warnings'] ?? 0);
        $this->generationSummary['fallback_warnings'] = array_slice(array_values(array_merge(
            $this->generationSummary['fallback_warnings'] ?? [],
            $result['fallback_warnings'] ?? []
        )), 0, 20);
    }

    private function summarizeScheduledItemsForDisplay(array $items): array
    {
        return collect($items)
            ->groupBy(fn (array $item) => $this->scheduledItemGroupKey($item))
            ->map(function ($group) {
                $first = $group->first();
                $days = $group->pluck('day')
                    ->filter()
                    ->unique()
                    ->sortBy(fn (string $day) => array_search($day, $this->days, true))
                    ->values()
                    ->all();

                $first['day_pair'] = implode(' / ', $days);
                $first['meeting_days'] = $days;
                $first['summary_key'] = $this->scheduledItemSummaryKey($first);

                foreach (['subject_code', 'subject_name', 'edp_code', 'room', 'instructor', 'start_time', 'end_time'] as $key) {
                    if (array_key_exists($key, $first)) {
                        $first[$key] = $this->cleanScheduleText($first[$key]);
                    }
                }

                return $first;
            })
            ->values()
            ->all();
    }

    private function findFirstValidPlacement(Subject $subject, $rooms, int $meetingIndex): ?array
    {
        $minutesPerMeeting = (int) ceil($this->calculateMinutesPerMeeting($subject));
        $days = $this->prioritizedDays($meetingIndex);

        foreach ($days as $day) {
            foreach ($this->getSectionTimeWindows($subject->section) as $window) {
                if ($window['start']->copy()->addMinutes($minutesPerMeeting)->gt($window['end'])) {
                    continue;
                }

                $period = CarbonPeriod::create(
                    $window['start'],
                    self::BRICK_DURATION_MINUTES . ' minutes',
                    $window['end']->copy()->subMinutes($minutesPerMeeting)
                );

                foreach ($period as $slotStart) {
                    $slotEnd = $slotStart->copy()->addMinutes($minutesPerMeeting);

                    if (!$this->isValidSchedulingWindow($subject, $slotStart, $slotEnd, $window)) {
                        continue;
                    }

                    foreach ($rooms as $room) {
                        if (!$this->roomCanHostSubject($room, $subject)) {
                            continue;
                        }

                        if (!$this->validateScheduleWithService(
                            $subject,
                            $room,
                            $day,
                            $slotStart->format(self::TIME_FORMAT_24H),
                            $slotEnd->format(self::TIME_FORMAT_24H)
                        )) {
                            return [
                                'room' => $room,
                                'day' => $day,
                                'start' => $slotStart->format(self::TIME_FORMAT_24H),
                                'end' => $slotEnd->format(self::TIME_FORMAT_24H),
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    private function prioritizedDays(int $meetingIndex): array
    {
        $days = $this->days;
        $offset = $meetingIndex % count($days);

        return array_merge(array_slice($days, $offset), array_slice($days, 0, $offset));
    }

    private function getSectionTimeWindows(?string $section): array
    {
        $dayStart = Carbon::parse($this->dayStart);
        $dayEnd = Carbon::parse($this->dayEnd);
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);
        $section = strtoupper((string) $section);

        $morning = [
            'start' => $dayStart->copy(),
            'end' => $dayEnd->lt($lunchStart) ? $dayEnd->copy() : $lunchStart->copy(),
        ];

        $afternoon = [
            'start' => $dayStart->gt($lunchEnd) ? $dayStart->copy() : $lunchEnd->copy(),
            'end' => $dayEnd->copy(),
        ];

        return match ($section) {
            'A' => $this->validWindows([$morning]),
            'B' => $this->validWindows([$afternoon]),
            default => $this->validWindows([$morning, $afternoon]),
        };
    }

    private function validWindows(array $windows): array
    {
        return array_values(array_filter($windows, fn (array $window) => $window['start']->lt($window['end'])));
    }

    private function isValidSchedulingWindow(Subject $subject, Carbon $slotStart, Carbon $slotEnd, array $window): bool
    {
        $service = app(ScheduleConflictService::class);

        return $slotStart->gte($window['start'])
            && $slotEnd->lte($window['end'])
            && $slotStart->between(Carbon::parse($this->dayStart), Carbon::parse($this->dayEnd), true)
            && $slotEnd->between(Carbon::parse($this->dayStart), Carbon::parse($this->dayEnd), true)
            && !$service->overlapsLunchBreak($slotStart->format(self::TIME_FORMAT_24H), $slotEnd->format(self::TIME_FORMAT_24H))
            && $service->respectsSectionSession($subject->section, $slotStart->format(self::TIME_FORMAT_24H), $slotEnd->format(self::TIME_FORMAT_24H));
    }

    private function roomCanHostSubject(Room $room, Subject $subject): bool
    {
        $expectedSize = $subject->student_count
            ?? $subject->enrollment
            ?? $subject->class_size
            ?? null;

        return !$expectedSize || !$room->capacity || (int) $room->capacity >= (int) $expectedSize;
    }

    private function createPartialSchedule(Subject $subject, Room $room, string $day, string $startTime, string $endTime): Schedule
    {
        if ($day === 'Sunday') {
            throw new \InvalidArgumentException('Sunday schedules are not allowed.');
        }

        return Schedule::create([
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'user_id' => auth()->id() ?? 1,
            'department' => $subject->department,
            'major' => $subject->major,
            'year_level' => $subject->year_level,
            'section' => $subject->section,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => round(Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)) / 60, 2),
            'meetings_per_week' => $subject->meetings_per_week,
            'pairing_key' => "manual-{$subject->id}-" . now()->format('YmdHisv'),
            'status' => Schedule::STATUS_PARTIAL,
        ]);
    }   

    public function generateLinkedMeetingPattern(int $subjectId): ?array
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return null;
        }

        $rooms = Room::query()->available()->get();
        $schedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->get();

        return app(AutoGenerateScheduler::class)->generateLinkedMeetingPattern(
            $subject,
            $rooms,
            $schedules,
            Setting::getDayBounds(),
            $this->getRemainingMeetings($subject)
        );
    }

    public function findConsistentRoomAndTime(int $subjectId): ?array
    {
        return $this->generateLinkedMeetingPattern($subjectId);
    }

    public function syncToBlockSchedule(array $savedItems = []): int
    {
        $this->dispatch('refreshBlockSchedule')->to(BlockSchedule::class);
        $this->dispatch('refreshGrid')->to(FacultyLoading::class);

        return count($savedItems);
    }

    public function notifyScheduleStakeholders(array $filters, int $scheduledCount, int $failedCount): void
    {
        $department = strtoupper((string) ($filters['department'] ?? ''));
        $major = strtoupper((string) ($filters['major'] ?? ''));
        $yearLevel = (string) ($filters['year_level'] ?? '');
        $section = strtoupper((string) ($filters['section'] ?? ''));

        $recipients = User::query()
            ->where(function ($query) use ($department) {
                $query->whereIn('role', ['admin', 'associate_dean'])
                    ->orWhere(function ($inner) use ($department) {
                        $inner->whereIn('role', ['dean', 'oic'])
                            ->when($department !== '', fn ($deptQuery) => $deptQuery->where('department', $department));
                    });
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $failedMessage = $failedCount > 0
            ? " {$failedCount} subject(s) need manual review."
            : '';

        Notification::send($recipients, new GeneralNotification([
            'title' => 'Partial Schedule Generated',
            'message' => "Partial Schedule Generated for: {$department} - {$major} - {$yearLevel} Year - Section {$section}. {$scheduledCount} slot(s) saved.{$failedMessage}",
            'type' => 'schedule_generation',
            'url' => route('block-schedule', absolute: false),
            'sender_name' => auth()->user()?->name ?? 'Classly',
        ]));

        $this->dispatch('notify')->to(NotificationCenter::class);
    }

    public function generateDisplaySlots()
    {
        $slots = [];
        $current = Carbon::parse($this->dayStart);
        $end = Carbon::parse($this->dayEnd);

        while ($current < $end) {
            $next = $current->copy()->addMinutes(self::BRICK_DURATION_MINUTES);
            if ($next > $end) break;

            $slots[] = [
                'display' => $this->formatTimeRange12h($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
                'start' => $current->format(self::TIME_FORMAT_24H),
                'end' => $next->format(self::TIME_FORMAT_24H),
                'isLunch' => $this->overlapsLunchBreak($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
            ];

            $current = $next;
        }

        return $slots;
    }

    public function getLunchBreakSlots()
    {
        $slots = [];
        $current = Carbon::parse(self::LUNCH_START);
        $end = Carbon::parse(self::LUNCH_END);

        while ($current < $end) {
            $next = $current->copy()->addMinutes(self::BRICK_DURATION_MINUTES);
            if ($next > $end) break;

            $slots[] = [
                'display' => $this->formatTimeRange12h($current->format(self::TIME_FORMAT_24H), $next->format(self::TIME_FORMAT_24H)),
                'start' => $current->format(self::TIME_FORMAT_24H),
                'end' => $next->format(self::TIME_FORMAT_24H),
            ];

            $current = $next;
        }

        return $slots;
    }

    public function calculateRoomUtilization($room): int
    {
        $totalBricksPerDay = count($this->generateDisplaySlots());
        $totalDaysPerWeek = 6;
        $totalAvailableBricks = $totalBricksPerDay * $totalDaysPerWeek;

        if ($totalAvailableBricks === 0) {
            return 0;
        }

        $scheduledSubjects = Schedule::where('room_id', $room->id)
            ->with('subject')
            ->get();

        $occupiedBricks = 0;
        foreach ($scheduledSubjects as $schedule) {
            $subject = $schedule->subject;
            if (!$subject) continue;

            $brickCount = $this->calculateBrickCount($subject);
            $occupiedBricks += $brickCount;
        }

        $utilization = ($occupiedBricks / $totalAvailableBricks) * 100;
        return min(100, (int)round($utilization));
    }

    public function calculateGridRowSpan($subject): int
    {
        return $this->calculateBrickCount($subject);
    }

    private function findGridRowIndex($time)
    {
        $time = Carbon::parse($time);
        $gridStart = Carbon::parse($this->dayStart);
        
        $diffMinutes = $gridStart->diffInMinutes($time);
        $slotIndex = round($diffMinutes / 30);
        
        return (int)$slotIndex;
    }

    public function getRoomDetails($roomId): ?array
    {
        $room = Room::find($roomId);
        if (!$room) return null;

        return [
            'id' => $room->id,
            'name' => $room->room_name,
            'type' => $room->type,
            'capacity' => $room->capacity,
            'utilization' => $this->calculateRoomUtilization($room),
        ];
    }

    public function selectRoom($id)
    {
        $room = Room::find($id);
        if ($room) {
            $this->selectedRoomId = $id;
            $this->selectedRoomName = $room->room_name;
            $this->selectedRoomType = $room->type;
            
            $this->dispatch('room-selected', roomId: $id); 
            $this->dispatch('refreshGrid');

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '✅ Room Selected',
                'detail' => "Room {$room->room_name} is now active"
            ]);
        }
    }

    public function validateRoomSelection(): bool
    {
        if (!$this->selectedRoomId) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⚠️ Select a Room First',
                'detail' => 'Please select a room from the sidebar before assigning subjects to the grid.'
            ]);
            return false;
        }
        return true;
    }

    /**
     * ===== ASSIGN SUBJECT WITH COMPREHENSIVE CONFLICT VALIDATION =====
     */
    public function assignSubject($subjectId, $day, $startTime)
    {
        if ($day === 'Sunday') {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Sunday Blocked',
                'detail' => 'Schedules can only be created from Monday to Saturday.'
            ]);
            return;
        }

        if (!$this->selectedRoomId) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⚠️ Select a Room First',
                'detail' => 'Please select a room from the sidebar before assigning subjects.'
            ]);
            return;
        }

        $subject = Subject::find($subjectId);

        if (!$subject) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Subject Not Found',
                'detail' => 'The selected subject could not be found!'
            ]);
            return;
        }

        $room = Room::find($this->selectedRoomId);

        if (!$room) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Room Not Found',
                'detail' => 'The selected room could not be found.'
            ]);
            return;
        }

        $remaining = $this->getRemainingMeetings($subject);
        if ($remaining <= 0) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => '⏸️ No Meetings Left',
                'detail' => "All {$subject->meetings_per_week} meetings for {$subject->subject_code} have been scheduled!"
            ]);
            return;
        }

        if (!$this->isTimeAlignedTo30Min($startTime)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🧱 Invalid Time Slot',
                'detail' => 'Start time must align to 30-minute increments (:00 or :30).'
            ]);
            return;
        }

        $minutesPerMeeting = $this->calculateMinutesPerMeeting($subject);
        $endTime = $this->calculateEndTime($startTime, $minutesPerMeeting);

        $gridEnd = Carbon::parse($this->dayEnd);
        $calculatedEndTime = Carbon::parse($endTime);
        
        if ($calculatedEndTime > $gridEnd) {
            $gridEndDisplay = $this->formatTime12h($this->dayEnd);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '⏰ End Time Out of Bounds',
                'detail' => "This subject would end at {$this->formatTime12h($endTime)}, which exceeds the grid end time ({$gridEndDisplay}). Please select an earlier time slot."
            ]);
            return;
        }

        if ($this->overlapsLunchBreak($startTime, $endTime)) {
            $lunchDisplay = $this->formatTimeRange12h(self::LUNCH_START, self::LUNCH_END);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '🍽️ Lunch Break Blocked',
                'detail' => "Cannot schedule during lunch break ({$lunchDisplay}). Please select another time."
            ]);
            return;
        }

        $outsidePreferredSectionRange = !app(ScheduleConflictService::class)->respectsSectionSession($subject->section, $startTime, $endTime);
        if ($outsidePreferredSectionRange) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Outside Preferred Range',
                'detail' => 'This schedule is outside the preferred section time range.'
            ]);
        }

        // ===== COMPREHENSIVE CONFLICT VALIDATION =====
        $conflictData = $this->validateScheduleWithService($subject, $room, $day, $startTime, $endTime);

        if ($conflictData) {
            $this->dispatch('toast', [
                'type' => $conflictData['toast_type'] ?? 'error',
                'message' => $conflictData['message']
            ]);

            $this->dispatch('show-conflict-modal', conflictData: $conflictData);
            return;
        }
        // ===== END CONFLICT VALIDATION =====

        $curriculumWarning = $this->checkCurriculumWarning($subject, $room);
        if ($curriculumWarning) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => $curriculumWarning['message']
            ]);
        }

        if (!app(AutoGenerateScheduler::class)->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Room Not Compatible',
                'detail' => "{$room->room_name} is not compatible with {$subject->subject_code}'s room specialization requirements."
            ]);
            return;
        }

        try {
            $schedule = $this->createPartialSchedule($subject, $room, $day, $startTime, $endTime);
            $this->syncToBlockSchedule([[
                'subject_id' => $schedule->subject_id,
                'room_id' => $schedule->room_id,
                'day' => $schedule->day,
                'raw_start_time' => $schedule->start_time,
                'raw_end_time' => $schedule->end_time,
            ]]);

            $remainingHours = $this->getRemainingHoursDecimal($subjectId);
            $remainingMeetings = $this->getRemainingMeetings($subject) - 1;

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => '✅ Subject Scheduled',
                'detail' => "{$subject->subject_code} scheduled on {$day}. {$remainingMeetings} meetings remaining ({$remainingHours}h left)."
            ]);

            $this->dispatch('refreshGrid');
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Error',
                'detail' => 'Failed to schedule subject: ' . $e->getMessage()
            ]);
        }
    }
    // ===== END ASSIGN SUBJECT =====

    public function removeAssignment($scheduleId)
    {
        if (!$this->canDeleteSchedule($scheduleId)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can remove schedules.'
            ]);
            return;
        }

        try {
            $schedule = Schedule::find($scheduleId);
            if ($schedule) {
                $subject = $schedule->subject;
                $schedule->delete();

                $this->dispatch('toast', [
                    'type' => 'info',
                    'message' => '🗑️ Schedule Removed',
                    'detail' => "{$subject->subject_code} has been removed from {$schedule->day}"
                ]);

                $this->dispatch('refreshGrid');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Error',
                'detail' => 'Failed to remove schedule: ' . $e->getMessage()
            ]);
        }
    }

    public function requestScheduleEdit($scheduleId): void
    {
        if (!$this->canAutoGenerateSchedules()) {
            $this->dispatch('toast', [
                'type' => 'info',
                'message' => 'View Only',
                'detail' => 'Request schedule changes from an Admin or Registrar.'
            ]);
            return;
        }

        $schedule = Schedule::with('subject:id,subject_code')->find($scheduleId);

        if (!$schedule) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Schedule not found.',
                'detail' => 'The selected schedule may have already been removed.'
            ]);
            return;
        }

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Edit generated drafts before saving.',
            'detail' => "{$schedule->subject?->subject_code} is already saved. To change it with validation, remove it and place the subject again."
        ]);
    }

    public function getFilteredSubjects()
    {
        $query = Subject::query()
            ->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'meetings_per_week', 'units', 'department', 'section', 'type', 'major', 'year_level', 'faculty_id');

        // Access control for non-admin users
        if (!$this->hasFullAccess()) {
            $userDept = $this->getUserDepartment();
            if ($userDept) {
                $query->where('department', $userDept);
            }
        }

        // Search filter
        if (trim($this->searchSubject)) {
            $query->where(function ($q) {
                $q->where('subject_code', 'like', '%' . $this->searchSubject . '%')
                  ->orWhere('description', 'like', '%' . $this->searchSubject . '%')
                  ->orWhere('edp_code', 'like', '%' . $this->searchSubject . '%');
            });
        }

        // Department filter (case-insensitive)
        if ($this->selectedDept && $this->selectedDept !== '') {
            $query->where('department', strtoupper($this->selectedDept));
        }

        // Year level filter
        if ($this->selectedYear && $this->selectedYear !== '') {
            $query->where('year_level', (int)$this->selectedYear);
        }

        // Major filter (case-insensitive)
        if ($this->selectedMajor && $this->selectedMajor !== '') {
            $query->where('major', strtoupper($this->selectedMajor));
        }

        // Section filter
        if ($this->selectedSection && $this->selectedSection !== '') {
            $query->where('section', strtoupper($this->selectedSection));
        }

        // Type filter
        if ($this->selectedType && $this->selectedType !== '') {
            $query->where('type', $this->selectedType);
        }

        $subjects = $query
            ->where('meetings_per_week', '>', 0)
            ->orderBy('subject_code', 'asc')
            ->get()
            ->filter(function ($subject) {
                return $this->getRemainingHours($subject->id) > 0;
            });

        return $subjects;
    }

    public function calculateScheduleHeightPx($startTime, $endTime): int
    {
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        $brickCount = ceil($durationMinutes / self::BRICK_DURATION_MINUTES);
        return ($brickCount * self::BRICK_HEIGHT_PX) + max(0, ($brickCount - 1));
    }

    public function getScheduleTopOffset($startTime): int
    {
        $slots = $this->generateDisplaySlots();
        $index = 0;
        
        foreach ($slots as $i => $slot) {
            if ($slot['start'] === $startTime) {
                $index = $i;
                break;
            }
        }
        
        return $index * self::BRICK_HEIGHT_PX;
    }

    public function getScheduleDisplayTime($startTime, $endTime): string
    {
        $start = Carbon::parse($startTime)->format('h:i A');
        $end = Carbon::parse($endTime)->format('h:i A');
        return "{$start} - {$end}";
    }

    public function isScheduleStartingAtSlot($schedule, $slotStartTime): bool
    {
        return $schedule->start_time === $slotStartTime;
    }

    public function isSlotWithinSchedule($schedule, $slotStart, $slotEnd): bool
    {
        $schedStart = Carbon::parse($schedule->start_time);
        $schedEnd = Carbon::parse($schedule->end_time);
        $slotStartCarbon = Carbon::parse($slotStart);
        $slotEndCarbon = Carbon::parse($slotEnd);
        
        return $schedStart <= $slotStartCarbon && $schedEnd > $slotStartCarbon;
    }

    public function getOverlappingSchedulesForSlot($day, $slotStart, $slotEnd): array
    {
        if (!$this->selectedRoomId) {
            return [];
        }

        $schedules = Schedule::where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->with('subject')
            ->get()
            ->filter(function ($schedule) use ($slotStart, $slotEnd) {
                $schedStart = Carbon::parse($schedule->start_time);
                $schedEnd = Carbon::parse($schedule->end_time);
                $slotStartCarbon = Carbon::parse($slotStart);
                $slotEndCarbon = Carbon::parse($slotEnd);
                
                return $schedStart < $slotEndCarbon && $schedEnd > $slotStartCarbon;
            })
            ->values();

        $result = [];
        $totalOverlaps = $schedules->count();

        foreach ($schedules as $index => $schedule) {
            $result[] = [
                'schedule' => $schedule,
                'position' => $index,
                'totalOverlaps' => $totalOverlaps,
                'leftPercent' => ($index / $totalOverlaps) * 100,
                'widthPercent' => (100 / $totalOverlaps),
            ];
        }

        return $result;
    }

    public function getSchedulesAtSlot($day, $slotStart, $slotEnd): array
    {
        if (!$this->selectedRoomId) {
            return [];
        }

        return Schedule::where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->with('subject')
            ->whereRaw('start_time < ? AND end_time > ?', [$slotEnd, $slotStart])
            ->get()
            ->toArray();
    }

    public function getAvailableFloors()
    {
        return Room::query()
            ->whereNotNull('floor')
            ->distinct()
            ->pluck('floor')
            ->sort()
            ->values()
            ->toArray();
    }

    public function render()
    {
        $subjects = $this->getFilteredSubjects();

        $rooms = Room::query()
            ->when($this->searchRoom, function ($query) {
                $query->where('room_name', 'like', '%' . $this->searchRoom . '%');
            })
            ->when($this->selectedRoomTypeFilter, function ($query) {
                $query->where('type', $this->selectedRoomTypeFilter);
            })
            ->when($this->selectedFloor, function ($query) {
                $query->where('floor', $this->selectedFloor);
            })
            ->get()
            ->map(function ($room) {
                $room->utilization = $this->calculateRoomUtilization($room);
                return $room;
            })
            ->sortBy('room_name');

        $schedules = $this->selectedRoomId
            ? Schedule::where('room_id', $this->selectedRoomId)
                ->with(['subject' => function ($query) {
                    $query->select(
                        'id', 'subject_code', 'description', 'edp_code', 
                        'duration_hours', 'department', 'type', 'major', 'year_level', 'faculty_id'
                    );
                }, 'faculty:id,full_name', 'room:id,room_name'])
                ->get()
            : collect();

        $lunchSlots = $this->getLunchBreakSlots();
        $availableFloors = $this->getAvailableFloors();
        $facultyOptions = Faculty::query()
            ->approved()
            ->select('id', 'full_name', 'department')
            ->orderBy('full_name')
            ->get();

        return view('livewire.master-grid', [
            'days'                 => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
            'generationDays'       => $this->days,
            'displaySlots'         => $this->generateDisplaySlots(),
            'lunchSlots'           => $lunchSlots,
            'schedules'            => $schedules,
            'subjects'             => $subjects,
            'rooms'                => $rooms,
            'availableFloors'      => $availableFloors,
            'lunchStart'           => self::LUNCH_START,
            'lunchEnd'             => self::LUNCH_END,
            'brickDurationMinutes' => self::BRICK_DURATION_MINUTES,
            'brickHeightPx'        => self::BRICK_HEIGHT_PX,
            'hasFullAccess'        => $this->hasFullAccess(),
            'canAutoGenerate'      => $this->canAutoGenerateSchedules(),
            'canModifyGenerated'   => $this->canModifyGeneratedSchedules(),
            'departmentMajors'     => self::DEPARTMENT_MAJORS,
            'departmentColors'     => self::DEPARTMENT_COLORS,
            'facultyOptions'       => $facultyOptions,
            'selectedRoomId'       => $this->selectedRoomId,
            'selectedRoomName'     => $this->selectedRoomName,
            'selectedRoomType'     => $this->selectedRoomType,
            'dayStart'             => $this->dayStart,
            'dayEnd'               => $this->dayEnd,
            'schoolYear'           => $this->schoolYear,
            'semester'             => $this->semester,
            'semesterName'         => $this->semesterName,
        ]);
    }
}
