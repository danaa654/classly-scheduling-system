<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
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

    public function canFinalizeSchedules(): bool
    {
        $role = auth()->user()?->role ?? 'guest';

        return in_array($role, ['admin', 'registrar'], true);
    }

    public function finalizeFacultyAssignedSchedules(): void
    {
        if (!$this->canFinalizeSchedules()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Permission Denied',
                'detail' => 'Only Registrar/Admin can finalize schedules.'
            ]);
            return;
        }

        $updated = Schedule::where('status', Schedule::STATUS_FACULTY_ASSIGNED)
            ->update(['status' => Schedule::STATUS_FINALIZED]);

        $this->dispatch('toast', [
            'type' => $updated > 0 ? 'success' : 'warning',
            'message' => $updated > 0 ? 'Schedules Finalized' : 'No Schedules Ready',
            'detail' => $updated > 0
                ? "{$updated} faculty-assigned schedule(s) are now finalized."
                : 'There are no faculty-assigned schedules pending final approval.'
        ]);

        $this->dispatch('refreshGrid');
    }

    public function getUserDepartment(): ?string
    {
        $user = auth()->user();
        return $user->department ?? null;
    }

    public function canDeleteSchedule($scheduleId): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $schedule = Schedule::find($scheduleId);
        if (!$schedule || !$schedule->subject) {
            return false;
        }

        $user = auth()->user();
        $userRole = $user->role ?? 'guest';
        $userDept = $user->department ?? null;

        if (in_array($userRole, ['dean', 'oic'])) {
            return $schedule->subject->department === $userDept;
        }

        return false;
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

    private function hasFacultyConflict($subjectId, $day, $startTime, $endTime): bool
    {
        return false;
    }

    /**
     * ===== COMPREHENSIVE CONFLICT VALIDATION =====
     * Checks all three types of conflicts before scheduling
     */
    private function validateSchedule($subjectId, $day, $startTime, $endTime, $roomId): ?array
    {
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return null;
        }

        $room = Room::find($roomId);

        if (!$room) {
            return null;
        }

        return $this->validateScheduleWithService($subject, $room, $day, $startTime, $endTime);

        // ===== 1. SECTION CONFLICT CHECK =====
        // Check if this specific Section (within the same Department, Major, Year, and Section) 
        // is already scheduled in ANY room during this time slot
        $sectionConflict = Schedule::whereHas('subject', function ($query) use ($subject) {
            $query->where('department', $subject->department)
                  ->where('major', $subject->major)
                  ->where('year_level', $subject->year_level)
                  ->where('section', $subject->section);
        })
        ->where('day', $day)
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        })
        ->with(['subject', 'room'])
        ->first();

        if ($sectionConflict) {
            return [
                'type' => 'SECTION_CONFLICT',
                'title' => '⚠️ Student Group Already Scheduled',
                'message' => 'This student group is already scheduled in another class during this time.',
                'details' => [
                    'conflict_type' => 'SECTION',
                    'group' => "{$subject->department}-{$subject->major}-{$subject->year_level}-{$subject->section}",
                    'conflicting_subject' => $sectionConflict->subject->subject_code ?? 'Unknown',
                    'conflicting_room' => $sectionConflict->room->room_name ?? 'Unknown',
                    'conflicting_start' => Carbon::parse($sectionConflict->start_time)->format(self::TIME_FORMAT_12H),
                    'conflicting_end' => Carbon::parse($sectionConflict->end_time)->format(self::TIME_FORMAT_12H),
                    'conflicting_day' => $sectionConflict->day,
                    'requested_subject' => $subject->subject_code,
                    'requested_start' => Carbon::parse($startTime)->format(self::TIME_FORMAT_12H),
                    'requested_end' => Carbon::parse($endTime)->format(self::TIME_FORMAT_12H),
                    'requested_day' => $day,
                    'suggestion' => 'Choose a different time slot when this group is free.',
                ]
            ];
        }

        // ===== 2. FACULTY CONFLICT CHECK =====
        // Check if the assigned Faculty Member is already teaching in ANY room during this time slot
        if ($subject->faculty_id) {
            $facultyConflict = Schedule::whereHas('subject', function ($query) use ($subject) {
                $query->where('faculty_id', $subject->faculty_id);
            })
            ->where('day', $day)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->with(['subject', 'room'])
            ->first();

            if ($facultyConflict) {
                $facultyName = $subject->faculty->full_name ?? 'Faculty Member';
                return [
                    'type' => 'FACULTY_CONFLICT',
                    'title' => '👨‍🏫 Faculty Member Already Teaching',
                    'message' => $facultyName . ' is already assigned to teach another class during this time.',
                    'details' => [
                        'conflict_type' => 'FACULTY',
                        'faculty_name' => $facultyName,
                        'conflicting_subject' => $facultyConflict->subject->subject_code ?? 'Unknown',
                        'conflicting_room' => $facultyConflict->room->room_name ?? 'Unknown',
                        'conflicting_start' => Carbon::parse($facultyConflict->start_time)->format(self::TIME_FORMAT_12H),
                        'conflicting_end' => Carbon::parse($facultyConflict->end_time)->format(self::TIME_FORMAT_12H),
                        'conflicting_day' => $facultyConflict->day,
                        'requested_subject' => $subject->subject_code,
                        'requested_start' => Carbon::parse($startTime)->format(self::TIME_FORMAT_12H),
                        'requested_end' => Carbon::parse($endTime)->format(self::TIME_FORMAT_12H),
                        'requested_day' => $day,
                        'suggestion' => 'Assign a different faculty member or choose another time slot.',
                    ]
                ];
            }
        }

        // ===== 3. ROOM CONFLICT CHECK =====
        // Ensure the specific Room is not already occupied during this time slot
        $roomConflict = Schedule::where('room_id', $roomId)
            ->where('day', $day)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->with(['subject', 'room'])
            ->first();

        if ($roomConflict) {
            return [
                'type' => 'ROOM_CONFLICT',
                'title' => '🏢 Room Already Occupied',
                'message' => 'This room is already booked during the requested time slot.',
                'details' => [
                    'conflict_type' => 'ROOM',
                    'room_name' => $roomConflict->room->room_name ?? 'Unknown',
                    'conflicting_subject' => $roomConflict->subject->subject_code ?? 'Unknown',
                    'conflicting_start' => Carbon::parse($roomConflict->start_time)->format(self::TIME_FORMAT_12H),
                    'conflicting_end' => Carbon::parse($roomConflict->end_time)->format(self::TIME_FORMAT_12H),
                    'conflicting_day' => $roomConflict->day,
                    'requested_subject' => $subject->subject_code,
                    'requested_start' => Carbon::parse($startTime)->format(self::TIME_FORMAT_12H),
                    'requested_end' => Carbon::parse($endTime)->format(self::TIME_FORMAT_12H),
                    'requested_day' => $day,
                    'suggestion' => 'Select a different room or time slot.',
                ]
            ];
        }

        // ===== NO CONFLICTS FOUND =====
        return null;
    }
    // ===== END CONFLICT VALIDATION =====

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
        ];

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

        try {
            Schedule::create([
                'subject_id' => $subjectId,
                'room_id' => $this->selectedRoomId,
                'user_id' => auth()->id() ?? 1,
                'department' => $subject->department,
                'major' => $subject->major,
                'year_level' => $subject->year_level,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'section' => $subject->section,
                'status' => 'partial',
            ]);

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
                'detail' => 'You don\'t have permission to delete this schedule. Only your department\'s schedules can be deleted.'
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
                }, 'faculty:id,full_name'])
                ->get()
            : collect();

        $lunchSlots = $this->getLunchBreakSlots();
        $availableFloors = $this->getAvailableFloors();

        return view('livewire.master-grid', [
            'days'                 => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
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
            'canFinalizeSchedules'  => $this->canFinalizeSchedules(),
            'departmentMajors'     => self::DEPARTMENT_MAJORS,
            'departmentColors'     => self::DEPARTMENT_COLORS,
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
