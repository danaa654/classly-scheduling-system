<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Department;
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
    public bool $showPreflightModal = false;
    public array $preflightData = [];
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
    public array $retryFailureDetails = [];
    public array $selectedFailedSubjects = [];
    public bool $selectAllFailedSubjects = false;
    public bool $showPracticum = false;   // toggle to show Practicum / OJT subjects in the sidebar
    public array $bulkFailedInputs = [
        'meetings_per_week' => '',
    ];
    public bool $autoFixingConflicts = false;
    public array $generatedScheduleEditInputs = [];
    public array $compatibleRoomsForEdit = [];
    public array $compatibleFacultyForEdit = [];
    public bool $showConflictModal = false;
    public array $conflictData = [];
    public array $recommendations = [];
    public array $conflictContext = [];
    public ?int $applyingSuggestionIndex = null;
    public ?string $applyingSuggestionId = null;
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
        $role = auth()->user()?->role;

        if (in_array($role, ['dean', 'oic', 'associate_dean'], true) && ! Setting::isSystemReady()) {
            $this->redirectRoute($role === 'associate_dean' ? 'assistant-dean.dashboard' : 'dean.dashboard', navigate: true);

            return;
        }

        $this->loadSettings();
    }

    public function loadSettings()
    {
        $scheduleSettings = Setting::getScheduleSettings();

        $this->days = $scheduleSettings['active_days'];
        $this->dayStart = $scheduleSettings['start_time'];
        $this->dayEnd = $scheduleSettings['end_time'];
        $period = Setting::getAcademicPeriod();
        $this->schoolYear = $period['school_year'];
        $this->semester = $period['semester'];
        $this->semesterName = $period['semester_name'];
    }

    private function activeScheduleQuery()
    {
        return Schedule::activeTerm($this->semester, $this->schoolYear);
    }

    private function activeSubjectQuery()
{
    $period = Setting::getAcademicPeriod();
    return Subject::query()
        ->where('semester', $period['semester'])
        ->where('school_year', $period['school_year'])
        ->where('is_archived', false);
}

    public function maxMeetingDays(): int
    {
        return max(1, count($this->days));
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

    public function updatedShowPracticum()
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
        return in_array($role, ['admin', 'registrar'], true);
    }

    private function normalizedSubjectType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return $type === 'minor' ? 'minor' : 'major';
    }

    private function canUserScheduleSubjectType(Subject $subject): bool
    {
        $role = auth()->user()?->role ?? 'guest';
        $subjectType = $this->normalizedSubjectType($subject->type);

        return match ($role) {
            'admin', 'registrar' => true,
            'associate_dean' => $subjectType === 'minor',
            'dean', 'oic' => $subjectType === 'major',
            default => false,
        };
    }

    private function dispatchSubjectTypeDeniedToast(Subject $subject): void
    {
        $role = auth()->user()?->role ?? 'guest';
        $subjectType = $this->normalizedSubjectType($subject->type);
        $subjectLabel = strtoupper($subjectType);

        $detail = match ($role) {
            'associate_dean' => 'Associate Dean can only schedule minor subjects. Major subjects are handled by the Dean or OIC.',
            'dean', 'oic' => 'Dean and OIC can only schedule major subjects. Minor subjects are handled by the Associate Dean.',
            default => 'Only Admin and Registrar accounts can schedule all subject types.',
        };

        $this->dispatch('toast', [
            'type' => 'error',
            'message' => "Cannot drop {$subjectLabel} subject",
            'detail' => "{$subject->subject_code} is a {$subjectLabel} subject. {$detail}",
        ]);
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
        return Department::normalizeCode($user->department ?? null);
    }

    public function canDeleteSchedule($scheduleId): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        $schedule = $this->activeScheduleQuery()
            ->with('subject')
            ->find($scheduleId);

        if (!$schedule || !$schedule->subject) {
            return false;
        }

        $role = $user->role ?? 'guest';

        if (in_array($role, ['admin', 'registrar'], true)) {
            return true;
        }

        if ($schedule->status === Schedule::STATUS_FINALIZED) {
            return false;
        }

        $subjectType = $this->normalizedSubjectType($schedule->subject->type);

        if ($role === 'associate_dean') {
            return $subjectType === 'minor';
        }

        if (in_array($role, ['dean', 'oic'], true)) {
            $userDepartment = Department::normalizeCode($user->department ?? null);
            $scheduleDepartment = $schedule->subject->department ?? $schedule->department;

            return $subjectType === 'major'
                && $userDepartment
                && Department::codesMatch($userDepartment, $scheduleDepartment);
        }

        return false;
    }

    private function scheduleDeletionDeniedDetail($scheduleId): string
    {
        $role = auth()->user()?->role ?? 'guest';
        $schedule = $this->activeScheduleQuery()
            ->with('subject:id,subject_code,type,department')
            ->find($scheduleId);

        if (!$schedule) {
            return 'The selected schedule may have already been removed or is outside the active term.';
        }

        if ($schedule->status === Schedule::STATUS_FINALIZED && !in_array($role, ['admin', 'registrar'], true)) {
            return 'This schedule is already finalized in Block Schedule. Only Admin or Registrar accounts can remove finalized schedules.';
        }

        return match ($role) {
            'associate_dean' => 'Associate Dean can only remove minor subject schedules.',
            'dean', 'oic' => 'Dean and OIC can only remove major subject schedules from their own department.',
            'admin', 'registrar' => 'This schedule could not be removed. Please refresh the grid and try again.',
            default => 'Only Admin, Registrar, or the authorized department role can remove this schedule.',
        };
    }

    public function getDepartmentColor($department): string
    {
        return self::DEPARTMENT_COLORS[Department::normalizeCode($department)] ?? 'slate';
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
        $subject = $this->activeSubjectQuery()->find($subjectId);
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
    return $this->activeScheduleQuery()
        ->where('subject_id', $subjectId)
        ->whereNotNull('day')           // ← ADD
        ->whereNotNull('start_time')    // ← ADD
        ->whereNotNull('end_time')      // ← ADD
        ->whereNotNull('room_id')       // ← ADD
        ->count();
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
        ?int $ignoreScheduleId = null,
        bool $includeRecommendations = true
    ): ?array {
        $result = app(ScheduleConflictService::class)->validatePlacement(
            $subject,
            $room,
            $day,
            $startTime,
            $endTime,
            $ignoreScheduleId,
            $includeRecommendations
        );

        return (($result['success'] ?? $result['status'] ?? true) === false)
            ? $this->formatConflictResponse($result, $subject, $day, $startTime, $endTime)
            : null;
    }

    private function formatConflictResponse(array $conflict, Subject $subject, string $day, string $startTime, string $endTime): array
    {
        $details = array_merge($conflict['details'] ?? [], [
            'conflict_type' => $conflict['conflict_type'] ?? 'SCHEDULE_CONFLICT',
            'requested_subject' => $subject->subject_code,
            'requested_subject_name' => $subject->description,
            'requested_start' => Carbon::parse($startTime)->format(self::TIME_FORMAT_12H),
            'requested_end' => Carbon::parse($endTime)->format(self::TIME_FORMAT_12H),
            'requested_time' => $this->formatTimeRange12h($startTime, $endTime),
            'requested_day' => $day,
        ]);

        $suggestions = $conflict['suggestions'] ?? [];
        if (!isset($details['suggestion']) && !empty($suggestions[0]['label'])) {
            $details['suggestion'] = $suggestions[0]['label'];
        }

        return array_merge($conflict, [
            'success' => false,
            'status' => false,
            'type' => $conflict['type'] ?? strtolower((string) ($conflict['conflict_type'] ?? 'schedule_conflict')),
            'toast_type' => $conflict['toast_type'] ?? 'error',
            'title' => $conflict['title'] ?? 'Schedule Conflict',
            'message' => $conflict['message'] ?? 'This schedule conflicts with an existing schedule.',
            'details' => $details,
            'conflicting_schedule' => $conflict['conflicting_schedule'] ?? null,
            'suggestions' => $suggestions,
        ]);
    }

    private function showScheduleConflict(array $conflictData, array $context = []): void
    {
        $conflictData['suggestions'] = collect($conflictData['suggestions'] ?? [])
            ->values()
            ->map(function (array $suggestion, int $index) {
                $suggestion['id'] ??= md5(json_encode([
                    $index,
                    $suggestion['type'] ?? 'suggestion',
                    $suggestion['room_id'] ?? null,
                    $suggestion['faculty_id'] ?? null,
                    $suggestion['day'] ?? null,
                    $suggestion['start_time'] ?? null,
                    $suggestion['end_time'] ?? null,
                ]));

                return $suggestion;
            })
            ->all();
        $conflictData['details'] = $conflictData['details'] ?? [];
        $conflictData['conflicting_schedule'] = $conflictData['conflicting_schedule'] ?? null;

        $this->conflictData = $conflictData;
        $this->recommendations = $conflictData['suggestions'];
        $this->conflictContext = $context;
        $this->showConflictModal = true;
    }

    public function closeConflictModal(): void
    {
        $this->showConflictModal = false;
        $this->conflictContext = [];
        $this->applyingSuggestionIndex = null;
        $this->applyingSuggestionId = null;
    }

    public function useConflictSuggestion(int $index): void
    {
        if ($this->applyingSuggestionId !== null) {
            return;
        }

        $suggestion = $this->recommendations[$index] ?? null;

        if (!is_array($suggestion)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Suggestion unavailable.',
                'detail' => 'Choose another room, day, or time manually.'
            ]);
            return;
        }

        $this->applyingSuggestionIndex = $index;
        $this->applyingSuggestionId = (string) ($suggestion['id'] ?? $index);

        try {
            $mode = $this->conflictContext['mode'] ?? null;

            if ($mode === 'generated_edit' && $this->editingGeneratedScheduleKey) {
                $this->applySuggestionToGeneratedEdit($suggestion);
                $this->closeConflictModal();
                $this->showEditScheduleModal = true;

                $this->dispatch('toast', [
                    'type' => 'success',
                    'message' => 'Suggestion applied.',
                    'detail' => 'Review the temporary edit, then apply it again.'
                ]);
                return;
            }

            if ($mode === 'assign' && !empty($this->conflictContext['subject_id'])) {
                $subjectId = (int) $this->conflictContext['subject_id'];
                $day = $suggestion['day'] ?? null;
                $startTime = $suggestion['start_time'] ?? null;

                $this->setSelectedRoomFromSuggestion((int) ($suggestion['room_id'] ?? $this->selectedRoomId));
                $this->closeConflictModal();

                if ($day && $startTime) {
                    $this->assignSubject($subjectId, $day, Carbon::parse($startTime)->format(self::TIME_FORMAT_24H));
                    return;
                }
            }

            $this->closeConflictModal();
        } finally {
            $this->applyingSuggestionIndex = null;
            $this->applyingSuggestionId = null;
        }
    }

    private function applySuggestionToGeneratedEdit(array $suggestion): void
    {
        if (!empty($suggestion['room_id'])) {
            $this->generatedScheduleEditInputs['room_id'] = (string) $suggestion['room_id'];
        }

        if (!empty($suggestion['start_time'])) {
            $this->generatedScheduleEditInputs['start_time'] = Carbon::parse($suggestion['start_time'])->format('H:i');
        }

        if (!empty($suggestion['day'])) {
            $meetingsPerWeek = max(1, (int) ($this->generatedScheduleEditInputs['meetings_per_week'] ?? 1));
            $currentDays = $this->normalizePreferredDays($this->generatedScheduleEditInputs['days'] ?? []);
            $requestedDay = $this->conflictData['details']['requested_day'] ?? null;
            $suggestedDay = Setting::normalizeDayName((string) $suggestion['day']);

            if ($suggestedDay && in_array($suggestedDay, $this->days, true)) {
                if ($meetingsPerWeek === 1) {
                    $currentDays = [$suggestedDay];
                } elseif ($requestedDay && in_array($requestedDay, $currentDays, true) && (!in_array($suggestedDay, $currentDays, true) || $requestedDay === $suggestedDay)) {
                    $currentDays = array_map(
                        fn (string $day) => $day === $requestedDay ? $suggestedDay : $day,
                        $currentDays
                    );
                } elseif (!in_array($suggestedDay, $currentDays, true) && count($currentDays) < $meetingsPerWeek) {
                    $currentDays[] = $suggestedDay;
                } elseif (!in_array($suggestedDay, $currentDays, true) && !empty($currentDays)) {
                    $currentDays[0] = $suggestedDay;
                }

                $this->generatedScheduleEditInputs['days'] = array_slice(
                    array_values(array_unique($currentDays)),
                    0,
                    $meetingsPerWeek
                );
            }
        }

        if (array_key_exists('faculty_id', $suggestion)) {
            $this->generatedScheduleEditInputs['faculty_id'] = filled($suggestion['faculty_id'])
                ? (string) $suggestion['faculty_id']
                : '';
        }

        if (array_key_exists('session_fallback', $suggestion)) {
            $this->generatedScheduleEditInputs['session_fallback'] = (bool) $suggestion['session_fallback'];
            $this->generatedScheduleEditInputs['allow_session_fallback'] = (bool) $suggestion['session_fallback'];
        }
    }

    private function setSelectedRoomFromSuggestion(int $roomId): void
    {
        if (!$roomId) {
            return;
        }

        $room = Room::find($roomId);

        if (!$room) {
            return;
        }

        $this->selectedRoomId = $room->id;
        $this->selectedRoomName = $room->room_name;
        $this->selectedRoomType = $room->type;
    }

    private function checkCurriculumWarning(Subject $subject, Room $room): ?array
    {
        $warning = app(ScheduleConflictService::class)->checkCurriculumConflict($subject, $room);

        return ($warning['type'] ?? null) === 'warning' ? $warning : null;
    }

    public function findCompatibleRooms(int $subjectId): array
    {
        $subject = $this->activeSubjectQuery()->find($subjectId);

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
        return Faculty::query()
            ->approved()
            ->select('id', 'full_name', 'department', 'faculty_scope', 'can_teach_minor')
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Faculty $faculty) => $faculty->isEligibleForSubject($subject))
            ->map(fn (Faculty $faculty) => [
                'id' => $faculty->id,
                'full_name' => $this->cleanScheduleText($faculty->full_name),
                'department' => $this->cleanScheduleText($faculty->displayDepartment()),
            ])
            ->values()
            ->all();
    }

    private function facultyDepartmentForSubject(Subject $subject): string
    {
        $department = Department::normalizeCode($subject->department) ?? '';

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
        $subject = $this->activeSubjectQuery()->find($subjectId);

        if (!$subject) {
            return null;
        }

        $rooms = Room::query()->available()->get();
        $schedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->get();

        return app(AutoGenerateScheduler::class)->findAvailableSlot($subject, $rooms, $schedules);
    }

    public function hasConflict(int $roomId, string $day, string $startTime, string $endTime, ?int $subjectId = null): bool
    {
        $schedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->get();

        if (app(AutoGenerateScheduler::class)->hasRoomConflict($schedules, $roomId, $day, $startTime, $endTime)) {
            return true;
        }

        $subject = $subjectId ? $this->activeSubjectQuery()->find($subjectId) : null;

        return $subject
            ? app(AutoGenerateScheduler::class)->hasSectionConflict($schedules, $subject, $day, $startTime, $endTime)
            : false;
    }

    public function hasFacultyConflict(int $facultyId, string $day, string $startTime, string $endTime): bool
    {
        $conflict = app(ScheduleConflictService::class)->checkFacultyConflict($facultyId, $day, $startTime, $endTime);

        return ($conflict['status'] ?? true) === false;
    }

    // =========================================================================
    // PRE-FLIGHT CHECK — runs before openGenerateModal
    // =========================================================================

    /**
     * Entry point from the Generate button.
     * Runs readiness queries, then either shows the preflight warning modal
     * (when issues are found) or opens the generate modal directly (all clear).
     */
    public function runPreflightCheck(): void
    {
        if (!$this->canAutoGenerateSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Admin and Registrar accounts can use AI auto generation.',
            ]);
            return;
        }

        $this->preflightData = $this->buildPreflightData();
        $this->showPreflightModal = true;
    }

    public function closePreflightModal(): void
    {
        $this->showPreflightModal = false;
        $this->preflightData = [];
    }

    /**
     * Called when admin/registrar acknowledges the warnings and clicks "Generate Anyway".
     */
    public function generateAnyway(): void
    {
        $this->showPreflightModal = false;
        $this->preflightData = [];
        $this->openGenerateModal();
    }

    /**
     * Called when the preflight check shows all-clear and the user clicks "Proceed".
     * Identical to generateAnyway — kept separate for semantic clarity in the blade.
     */
    public function proceedToGenerate(): void
    {
        $this->showPreflightModal = false;
        $this->preflightData = [];
        $this->openGenerateModal();
    }

    /**
     * Queries the active term's subjects and returns a structured preflight report
     * covering Faculty Loading (missing faculty_id) and ManageRooms (missing preferred_room_id).
     *
     * Only non-practicum subjects are checked — Practicum/OJT skip room assignment by design.
     *
     * @return array{
     *     has_warnings: bool,
     *     all_clear: bool,
     *     faculty_loading: array{total_missing: int, items: array},
     *     preferred_rooms: array{total_missing: int, items: array},
     * }
     */
    private function buildPreflightData(): array
    {
        $period = Setting::getAcademicPeriod();

        // Base query — active term, non-archived, non-practicum subjects only
        $base = Subject::query()
            ->where('semester', $period['semester'])
            ->where('school_year', $period['school_year'])
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->where('is_practicum', false)->orWhereNull('is_practicum');
            });

        // ── Faculty Loading: subjects with no faculty pre-assigned ────────────
        $missingFacultyGroups = (clone $base)
            ->whereNull('faculty_id')
            ->selectRaw('department, major, year_level, section, COUNT(*) as count')
            ->groupBy('department', 'major', 'year_level', 'section')
            ->orderBy('department')
            ->orderBy('major')
            ->orderByRaw('CAST(year_level AS UNSIGNED)')
            ->orderBy('section')
            ->get()
            ->map(fn ($row) => [
                'department' => strtoupper((string) ($row->department ?? '—')),
                'major'      => strtoupper((string) ($row->major ?? '—')),
                'year_level' => (int) $row->year_level,
                'section'    => strtoupper((string) ($row->section ?? '—')),
                'count'      => (int) $row->count,
            ])
            ->values()
            ->all();

        $totalMissingFaculty = array_sum(array_column($missingFacultyGroups, 'count'));

        // ── Preferred Rooms: subjects with no preferred_room_id set ──────────
        $missingRoomGroups = (clone $base)
            ->whereNull('preferred_room_id')
            ->selectRaw('department, major, year_level, section, COUNT(*) as count')
            ->groupBy('department', 'major', 'year_level', 'section')
            ->orderBy('department')
            ->orderBy('major')
            ->orderByRaw('CAST(year_level AS UNSIGNED)')
            ->orderBy('section')
            ->get()
            ->map(fn ($row) => [
                'department' => strtoupper((string) ($row->department ?? '—')),
                'major'      => strtoupper((string) ($row->major ?? '—')),
                'year_level' => (int) $row->year_level,
                'section'    => strtoupper((string) ($row->section ?? '—')),
                'count'      => (int) $row->count,
            ])
            ->values()
            ->all();

        $totalMissingRooms = array_sum(array_column($missingRoomGroups, 'count'));

        $hasWarnings = $totalMissingFaculty > 0 || $totalMissingRooms > 0;

        return [
            'has_warnings'    => $hasWarnings,
            'all_clear'       => !$hasWarnings,
            'faculty_loading' => [
                'total_missing' => $totalMissingFaculty,
                'items'         => $missingFacultyGroups,
            ],
            'preferred_rooms' => [
                'total_missing' => $totalMissingRooms,
                'items'         => $missingRoomGroups,
            ],
        ];
    }

    // =========================================================================
    // END PRE-FLIGHT CHECK
    // =========================================================================

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

        $this->generateDepartment = Department::normalizeCode($this->selectedDept);
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
        $this->selectedFailedSubjects = [];
        $this->selectAllFailedSubjects = false;
        $this->retryFailureDetails = [];
        $this->resetBulkFailedInputs();
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
        $this->selectedFailedSubjects = [];
        $this->selectAllFailedSubjects = false;
        $this->resetBulkFailedInputs();
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
            'department' => Department::normalizeCode($this->generateDepartment),
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
                $meetings = max(1, min($this->maxMeetingDays(), (int) ($item['meetings_per_week'] ?? 1)));

                return [
                    $item['subject_id'] => [
                        'meetings_per_week' => $meetings,
                    ],
                ];
            })
            ->all();
        $this->selectedFailedSubjects = [];
        $this->selectAllFailedSubjects = false;
        $this->resetBulkFailedInputs();

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
        $overrides = $this->failedSubjectRetryOverrides($subjectId);

        try {
            $retry = app(AutoGenerateScheduler::class)->retryFailedSubject(
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
            $message = collect($retry['failure_reasons'] ?? [])->first() ?? ($retry['message'] ?? 'No valid slot found.');
            $this->retryFailureDetails = [
                'subject_id' => $subjectId,
                'message' => $message,
                'searched' => $retry['searched'] ?? [
                    'all morning slots',
                    'all afternoon slots',
                    'all compatible rooms',
                    'all available faculty',
                ],
                'recommendations' => $retry['recommendations'] ?? [],
            ];
            $this->generationSummary['failed_items'] = array_values(array_map(function (array $item) use ($subjectId, $message) {
                if ((int) ($item['subject_id'] ?? 0) === $subjectId) {
                    $item['reason'] = $message;
                }

                return $item;
            }, $this->generationSummary['failed_items'] ?? []));
            $this->showRetryingModal = false;
            $this->showSummaryModal = true;
            $this->retrySubjectId = null;
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Retry failed.',
                'detail' => $message
            ]);
            return;
        }

        $firstScheduled = $retry['scheduled_items'][0] ?? null;
        $subjectCode = $firstScheduled['subject_code'] ?? null;
        $retryItems = array_values($retry['scheduled_items'] ?? []);
        $this->retryFailureDetails = [];

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
        $this->selectedFailedSubjects = array_values(array_filter(
            $this->selectedFailedSubjects,
            fn ($selectedId) => (int) $selectedId !== $subjectId
        ));
        $this->selectAllFailedSubjects = false;

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

        $subject = $this->activeSubjectQuery()
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

        $meetingsPerWeek = max(1, min($this->maxMeetingDays(), (int) ($first['meetings_per_week'] ?? (count($days) ?: ($subject->meetings_per_week ?? 1)))));

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
        $inputs['meetings_per_week'] = max(1, min($this->maxMeetingDays(), (int) ($inputs['meetings_per_week'] ?? count($inputs['days']))));

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
                $conflictData = $result['conflict'];
                $subject = $this->activeSubjectQuery()->find((int) ($inputs['subject_id'] ?? 0));
                $room = Room::find((int) ($inputs['room_id'] ?? 0));
                $conflictDay = $conflictData['details']['requested_day'] ?? ($inputs['days'][0] ?? $this->days[0] ?? 'Monday');

                if ($subject && $room && !empty($inputs['start_time'])) {
                    $startTime = Carbon::parse($inputs['start_time'])->format(self::TIME_FORMAT_24H);
                    $endTime = $this->calculateEndTime($startTime, $this->calculateMinutesPerMeeting($subject));

                    $conflictData = app(ScheduleConflictService::class)->enrichConflictWithRecommendations(
                        $conflictData,
                        $subject,
                        $room,
                        $conflictDay,
                        $startTime,
                        $endTime
                    );
                }

                $this->dispatch('toast', [
                    'type' => $conflictData['toast_type'] ?? 'error',
                    'message' => $conflictData['title'] ?? 'Schedule Conflict Detected',
                    'detail' => $conflictData['message'] ?? 'The temporary edit conflicts with another schedule.'
                ]);

                $this->showScheduleConflict($conflictData, ['mode' => 'generated_edit']);
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
        $this->selectedFailedSubjects = [];
        $this->selectAllFailedSubjects = false;
        $this->resetBulkFailedInputs();
    }

    public function updatedSelectAllFailedSubjects(bool $selected): void
    {
        $this->selectedFailedSubjects = $selected
            ? collect($this->generationSummary['failed_items'] ?? [])->pluck('subject_id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearFailedSelection(): void
    {
        $this->selectedFailedSubjects = [];
        $this->selectAllFailedSubjects = false;
        $this->resetBulkFailedInputs();
    }

    public function applyBulkFailedChanges(): void
    {
        $subjectIds = collect($this->selectedFailedSubjects)->map(fn ($id) => (int) $id)->filter()->unique();

        if ($subjectIds->isEmpty()) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'No failed subjects selected.',
                'detail' => 'Select at least one failed subject before applying bulk changes.'
            ]);
            return;
        }

        foreach ($subjectIds as $subjectId) {
            $this->failedRetryInputs[$subjectId] ??= [];

            if (filled($this->bulkFailedInputs['meetings_per_week'] ?? null)) {
                $this->failedRetryInputs[$subjectId]['meetings_per_week'] = $this->bulkFailedInputs['meetings_per_week'];
            }
        }

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Bulk changes applied.',
            'detail' => $subjectIds->count().' failed subject(s) received the retry preferences.'
        ]);
    }

    public function autoFixAllConflicts(): void
    {
        if (!$this->canModifyGeneratedSchedules() || !$this->generationSummary || $this->autoFixingConflicts) {
            return;
        }

        $failedItems = collect($this->generationSummary['failed_items'] ?? []);

        if ($failedItems->isEmpty()) {
            $this->dispatch('toast', [
                'type' => 'info',
                'message' => 'No conflicts to auto-fix.',
                'detail' => 'The current generation summary has no failed subjects.'
            ]);
            return;
        }

        $this->autoFixingConflicts = true;
        $fixed = 0;
        $remainingReasons = [];

        try {
            foreach ($failedItems as $item) {
                $subjectId = (int) ($item['subject_id'] ?? 0);

                if (!$subjectId) {
                    continue;
                }

                $retry = app(AutoGenerateScheduler::class)->retryFailedSubject(
                    $subjectId,
                    $this->generationSummary['filters'] ?? [],
                    $this->failedSubjectRetryOverrides($subjectId),
                    $this->pendingGeneratedSchedules,
                    auth()->id()
                );

                if (($retry['scheduled'] ?? 0) <= 0) {
                    $remainingReasons = array_merge($remainingReasons, $retry['failure_reasons'] ?? [$item['reason'] ?? 'No valid slot found.']);
                    continue;
                }

                $retryItems = array_map(function (array $scheduledItem) {
                    $scheduledItem['auto_fixed'] = true;

                    return $scheduledItem;
                }, array_values($retry['scheduled_items'] ?? []));

                $this->pendingGeneratedSchedules = array_values(array_merge($this->pendingGeneratedSchedules, $retryItems));
                $this->generationSummary['scheduled_items'] = array_values(array_merge(
                    $this->generationSummary['scheduled_items'] ?? [],
                    $this->summarizeScheduledItemsForDisplay($retryItems)
                ));

                $subjectCode = $retryItems[0]['subject_code'] ?? null;
                $this->generationSummary['failed_items'] = array_values(array_filter(
                    $this->generationSummary['failed_items'] ?? [],
                    fn (array $failedItem) => (int) $failedItem['subject_id'] !== $subjectId
                ));
                $this->generationSummary['failure_reasons'] = array_values(array_filter(
                    $this->generationSummary['failure_reasons'] ?? [],
                    fn (string $reason) => !$subjectCode || !str_starts_with($reason, "{$subjectCode}:")
                ));

                unset($this->failedRetryInputs[$subjectId]);
                $fixed++;
                $this->mergeGenerationWarnings($retry);
            }

            $this->refreshGenerationScheduledSummary();
            $this->generationSummary['failed'] = count($this->generationSummary['failed_items'] ?? []);
            $this->selectedFailedSubjects = [];
            $this->selectAllFailedSubjects = false;

            $this->dispatch('toast', [
                'type' => $fixed > 0 ? ($remainingReasons ? 'warning' : 'success') : 'warning',
                'message' => "Auto-fix complete: {$fixed} fixed, ".count($remainingReasons).' remaining.',
                'detail' => $remainingReasons ? collect($remainingReasons)->take(2)->implode(' ') : 'Resolved conflicts were added to the pending schedule preview.'
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Auto-fix failed.',
                'detail' => $e->getMessage(),
            ]);
        } finally {
            $this->autoFixingConflicts = false;
        }
    }

    private function failedSubjectRetryOverrides(int $subjectId): array
    {
        $inputs = $this->failedRetryInputs[$subjectId] ?? [];
        $meetings = max(1, min($this->maxMeetingDays(), (int) ($inputs['meetings_per_week'] ?? 1)));

        return [
            'meetings_per_week'   => $meetings,
            'preferred_time_start' => null,
            'preferred_time_end'   => null,
            'preferred_start_time' => null,
            'preferred_room_type'  => null,
            'preferred_days'       => [],
            'allow_session_fallback' => true,
        ];
    }

    private function resetBulkFailedInputs(): void
    {
        $this->bulkFailedInputs = [
            'meetings_per_week' => '',
        ];
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
            ->map(fn ($day) => Setting::normalizeDayName((string) $day))
            ->filter(fn (?string $day) => $day !== null && in_array($day, $this->days, true))
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
                            $slotEnd->format(self::TIME_FORMAT_24H),
                            null,
                            false
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

    private function createOrUpdateScheduleFromPlaceholder(
    Subject $subject,
    Room $room,
    string $day,
    string $startTime,
    string $endTime,
    ?int $preAssignedFacultyId = null
): Schedule {
    $period = Setting::getAcademicPeriod();

    // Find the existing faculty_locked placeholder (no spacetime yet)
    $placeholder = Schedule::activeTerm()
        ->where('subject_id', $subject->id)
        ->where('status', Schedule::STATUS_FACULTY_LOCKED)
        ->whereNull('day')
        ->whereNull('start_time')
        ->whereNull('end_time')
        ->whereNull('room_id')
        ->lockForUpdate()
        ->first();

    if ($placeholder) {
        // UPDATE the placeholder: fill in room + spacetime, preserve faculty
        $placeholder->update([
            'room_id'        => $room->id,
            'day'            => $day,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'duration_hours' => round(
                Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)) / 60, 2
            ),
            'meetings_per_week' => $subject->meetings_per_week,
            'pairing_key'    => "manual-{$subject->id}-" . now()->format('YmdHisv'),
            'status'         => Schedule::STATUS_FINALIZED,
            'user_id'        => auth()->id() ?? 1,
            'faculty_id'     => $preAssignedFacultyId ?? $placeholder->faculty_id,
        ]);

        return $placeholder->fresh();
    }

    // No placeholder — create a fresh schedule row
    return Schedule::create([
        'subject_id'      => $subject->id,
        'room_id'         => $room->id,
        'faculty_id'      => $preAssignedFacultyId ?? $subject->faculty_id,
        'user_id'         => auth()->id() ?? 1,
        'department'      => $subject->department,
        'major'           => $subject->major,
        'year_level'      => $subject->year_level,
        'section'         => $subject->section,
        'day'             => $day,
        'start_time'      => $startTime,
        'end_time'        => $endTime,
        'duration_hours'  => round(
            Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)) / 60, 2
        ),
        'meetings_per_week' => $subject->meetings_per_week,
        'pairing_key'     => "manual-{$subject->id}-" . now()->format('YmdHisv'),
        'status'          => Schedule::STATUS_PARTIAL,
        'edp_code'        => $subject->edp_code,
        'semester'        => $period['semester'],
        'school_year'     => $period['school_year'],
        'academic_year'   => $period['school_year'],
        'workspace_key'   => $period['workspace_key'],
        'is_archived'     => false,
    ]);
}

    public function generateLinkedMeetingPattern(int $subjectId): ?array
    {
        $subject = $this->activeSubjectQuery()->find($subjectId);

        if (!$subject) {
            return null;
        }

        $rooms = Room::query()->available()->get();
        $schedules = $this->activeScheduleQuery()
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
        $department = Department::normalizeCode($filters['department'] ?? null) ?? '';
        $major = strtoupper((string) ($filters['major'] ?? ''));
        $yearLevel = (string) ($filters['year_level'] ?? '');
        $section = strtoupper((string) ($filters['section'] ?? ''));

        $recipients = User::query()
            ->where(function ($query) use ($department) {
                $query->whereIn('role', ['admin', 'associate_dean'])
                    ->orWhere(function ($inner) use ($department) {
                        $inner->whereIn('role', ['dean', 'oic'])
                            ->when($department !== '', fn ($deptQuery) => $deptQuery->whereIn('department', Department::aliasesFor($department)));
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
        $totalDaysPerWeek = $this->maxMeetingDays();
        $totalAvailableBricks = $totalBricksPerDay * $totalDaysPerWeek;

        if ($totalAvailableBricks === 0) {
            return 0;
        }

        $scheduledSubjects = $this->activeScheduleQuery()
            ->where('room_id', $room->id)
            ->whereIn('day', $this->days)
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

    public function selectRoom($roomId): void
    {
        $roomId = (int) $roomId;

        $this->selectedRoomId = $roomId;
        $this->selectedRoomName = null;
        $this->selectedRoomType = null;
        $this->recommendations = [];
        $this->conflictData = [];
        $this->conflictContext = [];
        $this->showConflictModal = false;
        $this->applyingSuggestionIndex = null;
        $this->applyingSuggestionId = null;

        $room = Room::query()
            ->select('id', 'room_name', 'type')
            ->find($roomId);

        if (!$room) {
            $this->selectedRoomId = null;

            $this->dispatch('toast', [
                'type' => 'warning',
                'message' => 'Room Not Found',
                'detail' => 'The selected room could not be found.'
            ]);

            return;
        }

        $this->selectedRoomName = $room->room_name;
        $this->selectedRoomType = $room->type;

        $this->dispatch('roomChanged', roomId: $roomId);
        $this->dispatch('room-selected', roomId: $roomId);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => '✅ Room Selected',
            'detail' => "Room {$room->room_name} is now active"
        ]);
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
        if (!in_array($day, $this->days, true)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Day Disabled',
                'detail' => "{$day} is not enabled in Global Settings."
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

        $subject = $this->activeSubjectQuery()->find($subjectId);

        // Look up the pre-assigned faculty from the faculty_locked placeholder
        // (Faculty Loading stores the faculty in schedules, not subjects)
        $preAssignedFacultyId = Schedule::activeTerm()
            ->where('subject_id', $subjectId)
            ->whereNotNull('faculty_id')
            ->whereNull('day')
            ->whereNull('start_time')
            ->whereNull('end_time')
            ->whereNull('room_id')
            ->value('faculty_id');

        if (!$subject) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => '❌ Subject Not Found',
                'detail' => 'The selected subject could not be found!'
            ]);
            return;
        }

        if (!$this->canUserScheduleSubjectType($subject)) {
            $this->dispatchSubjectTypeDeniedToast($subject);
            return;
        }

        // Practicum/OJT subjects are off-campus and must never be assigned to a room slot.
        if ((bool) ($subject->is_practicum ?? false)) {
            $this->dispatch('toast', [
                'type'    => 'error',
                'message' => 'Scheduling Unsuccessful',
                'detail'  => 'This subject is marked as Practicum/OJT and does not require a physical room assignment. Practicum/OJT subjects cannot be scheduled in the Master Grid.',
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

        $gridStart = Carbon::parse($this->dayStart);
        $gridEnd = Carbon::parse($this->dayEnd);
        $calculatedStartTime = Carbon::parse($startTime);
        $calculatedEndTime = Carbon::parse($endTime);

        if ($calculatedStartTime < $gridStart) {
            $gridStartDisplay = $this->formatTime12h($this->dayStart);
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Start Time Out of Bounds',
                'detail' => "This subject would start before the grid start time ({$gridStartDisplay}). Please select a later time slot."
            ]);
            return;
        }
        
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

            $this->showScheduleConflict($conflictData, [
                'mode' => 'assign',
                'subject_id' => (int) $subjectId,
            ]);
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

        try {
            $schedule = $this->createOrUpdateScheduleFromPlaceholder(
                $subject, $room, $day, $startTime, $endTime, $preAssignedFacultyId
            );

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
                'detail' => $this->scheduleDeletionDeniedDetail($scheduleId)
            ]);
            return;
        }

        try {
            $schedule = $this->activeScheduleQuery()->find($scheduleId);
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

        $schedule = $this->activeScheduleQuery()
            ->with('subject:id,subject_code')
            ->find($scheduleId);

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
        $query = $this->activeSubjectQuery()
            ->select('id', 'subject_code', 'description', 'edp_code', 'duration_hours', 'meetings_per_week', 'units', 'department', 'section', 'type', 'major', 'year_level', 'faculty_id');

        $role = auth()->user()?->role ?? 'guest';

        if ($role === 'associate_dean') {
            $query->where('type', 'Minor');
        } elseif (in_array($role, ['dean', 'oic'], true)) {
            $userDept = $this->getUserDepartment();
            if ($userDept) {
                $query->whereIn('department', Department::aliasesFor($userDept));
            }
            $query->where(function ($typeQuery) {
                $typeQuery->where('type', 'Major')
                    ->orWhereNull('type')
                    ->orWhere('type', '');
            });
        } elseif (!$this->hasFullAccess()) {
            $query->whereRaw('1 = 0');
        }

        // Practicum / OJT exclusion — hidden from the grid by default
        // because they don't occupy a room or time slot.
        if (! $this->showPracticum) {
            $query->where(function ($q) {
                $q->where('is_practicum', false)
                  ->orWhereNull('is_practicum');
            });
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
            $query->whereIn('department', Department::aliasesFor($this->selectedDept));
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

        // Normalize slot bounds to H:i:s strings to ensure correct time-only
        // comparison (Schedule start_time/end_time are TIME columns in MySQL).
        $slotStartStr = $slotStart instanceof Carbon
            ? $slotStart->format('H:i:s')
            : Carbon::parse($slotStart)->format('H:i:s');

        $slotEndStr = $slotEnd instanceof Carbon
            ? $slotEnd->format('H:i:s')
            : Carbon::parse($slotEnd)->format('H:i:s');

        $schedules = $this->activeScheduleQuery()
            ->where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereRaw('TIME(start_time) < ? AND TIME(end_time) > ?', [$slotEndStr, $slotStartStr])
            ->with('subject')
            ->get()
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

        // Always pass plain H:i:s strings — Carbon datetime objects bound into
        // whereRaw produce full datetime strings (e.g. "2025-06-27 08:00:00")
        // which MySQL cannot compare correctly against TIME columns.
        $slotStartStr = $slotStart instanceof \Carbon\Carbon
            ? $slotStart->format('H:i:s')
            : Carbon::parse($slotStart)->format('H:i:s');

        $slotEndStr = $slotEnd instanceof \Carbon\Carbon
            ? $slotEnd->format('H:i:s')
            : Carbon::parse($slotEnd)->format('H:i:s');

        return $this->activeScheduleQuery()
            ->where('room_id', $this->selectedRoomId)
            ->where('day', $day)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->with('subject')
            ->whereRaw('TIME(start_time) < ? AND TIME(end_time) > ?', [$slotEndStr, $slotStartStr])
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
        // Count Practicum / OJT subjects that are currently hidden
        // so the sidebar can display the "N hidden" indicator.
        $practicumCount = $this->showPracticum ? 0 : (function () {
            $period = Setting::getAcademicPeriod();
            return Subject::query()
                ->where('semester', $period['semester'])
                ->where('school_year', $period['school_year'])
                ->where('is_archived', false)
                ->where('is_practicum', true)
                ->count();
        })();

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
            ? $this->activeScheduleQuery()
                ->where('room_id', $this->selectedRoomId)
                // Only fetch fully-placed schedules (non-null day + times).
                // Pre-assignment placeholders (day=null) are not renderable on
                // the grid and were silently skipped in the blade after fetch.
                ->whereIn('day', $this->days)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->with(['subject' => function ($query) {
                    // NOTE: 'section' is on the Schedule row, not Subject,
                    // so it is not needed in this select.
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
            ->select('id', 'full_name', 'department', 'faculty_scope', 'can_teach_minor')
            ->orderBy('full_name')
            ->get();

        return view('livewire.master-grid', [
            'days'                 => $this->days,
            'dayLabels'            => Setting::getActiveDayLabels(),
            'generationDays'       => $this->days,
            'maxMeetingDays'       => $this->maxMeetingDays(),
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
            'practicumCount'       => $practicumCount,
            'dayStart'             => $this->dayStart,
            'dayEnd'               => $this->dayEnd,
            'schoolYear'           => $this->schoolYear,
            'semester'             => $this->semester,
            'semesterName'         => $this->semesterName,
        ]);
    }
}