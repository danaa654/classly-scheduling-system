<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\Scheduling\AutoGenerateScheduler;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

class ScheduleConflictService
{
    private const SLOT_MINUTES = 30;
    private const LUNCH_START = '12:00:00';
    private const LUNCH_END = '13:00:00';
    private bool $includeSuggestions = true;

    public function validatePlacement(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null,
        bool $includeSuggestions = true
    ): array {
        $previousIncludeSuggestions = $this->includeSuggestions;
        $this->includeSuggestions = $includeSuggestions;
        $subject->loadMissing('faculty:id,full_name,availability');

        try {
            $checks = [
                $this->checkInactiveDayConflict($day, $subject, $room, $startTime, $endTime, $ignoreScheduleId),
                $this->checkCapacityConflict($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
                $this->checkRoomTypeConflict($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
                $this->checkRoomConflict($room->id, $day, $startTime, $endTime, $ignoreScheduleId, $subject, $room),
                $this->checkSectionConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId, $room),
                $this->checkFacultyConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId, $room),
            ];

            if ($subject->faculty_id && $subject->faculty) {
                $checks[] = $this->checkFacultyAvailability($subject->faculty, $day, $startTime, $endTime, $subject, $room, $ignoreScheduleId);
            }

            foreach ($checks as $check) {
                if (($check['success'] ?? $check['status'] ?? true) === false) {
                    return $check;
                }
            }

            return $this->pass();
        } finally {
            $this->includeSuggestions = $previousIncludeSuggestions;
        }
    }

    public function checkInactiveDayConflict(
        string $day,
        ?Subject $subject = null,
        ?Room $room = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $ignoreScheduleId = null
    ): array {
        if (Setting::dayIsActive($day)) {
            return $this->pass();
        }

        return $this->conflictResponse(
            'inactive_day_conflict',
            'INACTIVE_DAY_CONFLICT',
            'Inactive Schedule Day',
            "{$day} is disabled in Global Settings.",
            $subject,
            $room,
            $day,
            $startTime ?? Setting::getDayBounds()['start'],
            $endTime ?? Setting::getDayBounds()['end'],
            null,
            [
                'disabled_day' => $day,
                'suggestion' => 'Choose one of the active scheduling days.',
            ],
            $ignoreScheduleId
        );
    }

    public function checkCapacityConflict(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        $expectedSize = $this->expectedClassSize($subject);

        if (!$expectedSize || !$room->capacity || (int) $room->capacity >= $expectedSize) {
            return $this->pass();
        }

        return $this->conflictResponse(
            'capacity_conflict',
            'CAPACITY_CONFLICT',
            'Room Capacity Too Small',
            "{$room->room_name} can seat {$room->capacity} students, but {$subject->subject_code} expects {$expectedSize}.",
            $subject,
            $room,
            $day,
            $startTime,
            $endTime,
            null,
            [
                'room_capacity' => (int) $room->capacity,
                'expected_students' => $expectedSize,
                'conflicting_room' => $room->room_name,
                'suggestion' => 'Choose a room with enough capacity for this section.',
            ],
            $ignoreScheduleId
        );
    }

    public function checkRoomTypeConflict(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        if ($this->roomCanHostSubject($room, $subject)) {
            return $this->pass();
        }

        $message = $this->subjectRequiresLab($subject) && !$this->roomIsLab($room)
            ? "{$subject->subject_code} requires a laboratory room, but {$room->room_name} is a lecture room."
            : "{$room->room_name} does not match {$subject->subject_code}'s room requirements.";

        return $this->conflictResponse(
            'room_type_conflict',
            'ROOM_TYPE_CONFLICT',
            'Room Requirement Conflict',
            $message,
            $subject,
            $room,
            $day,
            $startTime,
            $endTime,
            null,
            [
                'subject_type' => $subject->type,
                'room_type' => $room->type,
                'room_name' => $room->room_name,
                'conflicting_room' => $room->room_name,
                'suggestion' => 'Choose a compatible room for this subject.',
            ],
            $ignoreScheduleId
        );
    }

    public function checkRoomConflict(
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null,
        ?Subject $subject = null,
        ?Room $requestedRoom = null
    ): array {
        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where('room_id', $roomId)
            ->with($this->summaryRelations())
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $requestedRoom ??= $conflict->room ?: Room::select('id', 'room_name', 'type', 'capacity', 'specialization')->find($roomId);
        $roomName = $requestedRoom?->room_name ?? $conflict->room?->room_name ?? 'This room';
        $subjectCode = $conflict->subject?->subject_code ?? 'another subject';

        return $this->conflictResponse(
            'room_conflict',
            'ROOM_CONFLICT',
            'Room Already Occupied',
            "{$roomName} is already occupied by {$subjectCode} during this schedule.",
            $subject,
            $requestedRoom,
            $day,
            $startTime,
            $endTime,
            $conflict,
            [
                'conflicting_room' => $roomName,
            ],
            $ignoreScheduleId
        );
    }

    public function checkFacultyConflict(
        Subject|int $subject,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null,
        ?Room $requestedRoom = null
    ): array {
        $facultyId = $subject instanceof Subject ? $subject->faculty_id : $subject;

        if (!$facultyId) {
            return $this->pass();
        }

        $faculty = $subject instanceof Subject
            ? $subject->loadMissing('faculty:id,full_name')->faculty
            : Faculty::select('id', 'full_name')->find($facultyId);

        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where(function (Builder $query) use ($facultyId) {
                $query->where('faculty_id', $facultyId)
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('faculty_id', $facultyId));
            })
            ->with($this->summaryRelations())
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $facultyName = $faculty?->full_name ?? 'Faculty Member';
        $subjectCode = $conflict->subject?->subject_code ?? 'another subject';
        $roomName = $conflict->room?->room_name ?? 'Unknown Room';

        return $this->conflictResponse(
            'faculty_conflict',
            'FACULTY_CONFLICT',
            'Faculty Member Already Teaching',
            "{$facultyName} is already teaching {$subjectCode} in {$roomName} during this time.",
            $subject instanceof Subject ? $subject : null,
            $requestedRoom,
            $day,
            $startTime,
            $endTime,
            $conflict,
            [
                'faculty_name' => $facultyName,
            ],
            $ignoreScheduleId
        );
    }

    public function checkFacultyAvailability(
        Faculty $faculty,
        string $day,
        string $startTime,
        string $endTime,
        ?Subject $subject = null,
        ?Room $requestedRoom = null,
        ?int $ignoreScheduleId = null
    ): array {
        $availability = $faculty->getAttribute('availability');

        if (empty($availability) || !is_array($availability)) {
            return $this->pass();
        }

        $dayWindows = $availability[$day] ?? $availability[strtolower($day)] ?? [];

        if (empty($dayWindows)) {
            return $this->conflictResponse(
                'faculty_availability_conflict',
                'FACULTY_AVAILABILITY',
                'Faculty Not Available',
                "{$faculty->full_name} is not available on {$day}.",
                $subject,
                $requestedRoom,
                $day,
                $startTime,
                $endTime,
                null,
                [
                    'faculty_name' => $faculty->full_name,
                    'day' => $day,
                    'suggestion' => 'Choose a day or time within the instructor availability.',
                ],
                $ignoreScheduleId
            );
        }

        $requestedStart = Carbon::parse($startTime);
        $requestedEnd = Carbon::parse($endTime);

        foreach ($dayWindows as $window) {
            $windowStartValue = $window['start'] ?? $window[0] ?? null;
            $windowEndValue = $window['end'] ?? $window[1] ?? null;

            if (!$windowStartValue || !$windowEndValue) {
                continue;
            }

            $windowStart = Carbon::parse($windowStartValue);
            $windowEnd = Carbon::parse($windowEndValue);

            if ($requestedStart->gte($windowStart) && $requestedEnd->lte($windowEnd)) {
                return $this->pass();
            }
        }

        return $this->conflictResponse(
            'faculty_availability_conflict',
            'FACULTY_AVAILABILITY',
            'Faculty Not Available',
            "{$faculty->full_name} is not available during this time.",
            $subject,
            $requestedRoom,
            $day,
            $startTime,
            $endTime,
            null,
            [
                'faculty_name' => $faculty->full_name,
                'suggestion' => 'Choose a time within the instructor availability.',
            ],
            $ignoreScheduleId
        );
    }

    public function checkSectionConflict(
        Subject $subject,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null,
        ?Room $requestedRoom = null
    ): array {
        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where(function (Builder $query) use ($subject) {
                $query->where(function (Builder $scheduleQuery) use ($subject) {
                    $scheduleQuery->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', $subject->year_level)
                        ->where('section', $subject->section);
                })->orWhereHas('subject', function (Builder $subjectQuery) use ($subject) {
                    $subjectQuery->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', $subject->year_level)
                        ->where('section', $subject->section);
                });
            })
            ->with($this->summaryRelations())
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $group = $this->studentGroup($subject);

        return $this->conflictResponse(
            'section_conflict',
            'SECTION_CONFLICT',
            'Student Group Already Scheduled',
            "{$group} already has another subject during this time.",
            $subject,
            $requestedRoom,
            $day,
            $startTime,
            $endTime,
            $conflict,
            [
                'group' => $group,
            ],
            $ignoreScheduleId
        );
    }

    public function checkCurriculumConflict(Subject $subject, Room $room): array
    {
        if (!$this->subjectRequiresLab($subject) || $this->roomIsLab($room)) {
            return $this->pass();
        }

        return [
            'success' => true,
            'status' => true,
            'type' => 'warning',
            'toast_type' => 'warning',
            'conflict_type' => 'CURRICULUM_CONFLICT',
            'title' => 'Room Specialization Warning',
            'message' => 'Warning: This subject requires a laboratory room.',
            'details' => [
                'subject_type' => $subject->type,
                'room_type' => $room->type,
                'room_name' => $room->room_name,
            ],
            'suggestions' => [],
        ];
    }

    public function enrichConflictWithRecommendations(
        array $conflict,
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        $legacyType = $conflict['conflict_type']
            ?? $conflict['details']['conflict_type']
            ?? $this->legacyTypeFromType((string) ($conflict['type'] ?? 'SCHEDULE_CONFLICT'));
        $type = $this->normalTypeFromLegacy($legacyType);
        $details = array_merge($conflict['details'] ?? [], [
            'conflict_type' => $legacyType,
            'requested_subject' => $conflict['details']['requested_subject'] ?? $subject->subject_code,
            'requested_subject_name' => $conflict['details']['requested_subject_name'] ?? $subject->description,
            'requested_day' => $conflict['details']['requested_day'] ?? $day,
            'requested_start' => $conflict['details']['requested_start'] ?? Carbon::parse($startTime)->format('h:i A'),
            'requested_end' => $conflict['details']['requested_end'] ?? Carbon::parse($endTime)->format('h:i A'),
            'requested_time' => $this->formatTimeRange($startTime, $endTime),
        ]);

        $suggestions = $conflict['suggestions'] ?? $this->buildSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId, $type);

        if (!isset($details['suggestion']) && !empty($suggestions[0]['label'])) {
            $details['suggestion'] = $suggestions[0]['label'];
        }

        return array_merge($conflict, [
            'success' => false,
            'status' => false,
            'type' => $type,
            'toast_type' => $conflict['toast_type'] ?? 'error',
            'severity' => $conflict['severity'] ?? 'error',
            'conflict_type' => $legacyType,
            'title' => $conflict['title'] ?? $this->titleFromType($legacyType),
            'message' => $this->cleanText($conflict['message'] ?? 'This schedule conflicts with another schedule.'),
            'details' => $details,
            'conflicting_schedule' => $conflict['conflicting_schedule'] ?? $this->conflictingScheduleFromDetails($details),
            'suggestions' => $suggestions,
        ]);
    }

    public function hasTimeOverlap(string $newStart, string $newEnd, string $existingStart, string $existingEnd): bool
    {
        $newStartCarbon = Carbon::parse($newStart);
        $newEndCarbon = Carbon::parse($newEnd);
        $existingStartCarbon = Carbon::parse($existingStart);
        $existingEndCarbon = Carbon::parse($existingEnd);

        return $newStartCarbon->lt($existingEndCarbon)
            && $newEndCarbon->gt($existingStartCarbon);
    }

    public function overlapsLunchBreak(string $startTime, string $endTime): bool
    {
        return $this->hasTimeOverlap($startTime, $endTime, self::LUNCH_START, self::LUNCH_END);
    }

    public function respectsSectionSession(?string $section, string $startTime, string $endTime): bool
    {
        $section = strtoupper((string) $section);
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);

        if ($section === 'A') {
            return $start->lt($lunchStart) && $end->lte($lunchStart);
        }

        if ($section === 'B') {
            return $start->gte($lunchEnd) && $end->gt($lunchEnd);
        }

        return true;
    }

    public function baseScheduleQuery(
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): Builder {
        return Schedule::activeTerm()
            ->where('day', $day)
            ->when($ignoreScheduleId, fn (Builder $query) => $query->whereKeyNot($ignoreScheduleId))
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $this->normalizeTime($endTime))
                    ->where('end_time', '>', $this->normalizeTime($startTime));
            });
    }

    private function conflictResponse(
        string $type,
        string $legacyType,
        string $title,
        string $message,
        ?Subject $subject,
        ?Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?Schedule $conflictingSchedule = null,
        array $extraDetails = [],
        ?int $ignoreScheduleId = null,
        string $severity = 'error'
    ): array {
        $details = array_merge([
            'conflict_type' => $legacyType,
            'requested_subject' => $subject?->subject_code ?? 'Requested subject',
            'requested_subject_name' => $subject?->description ?? null,
            'requested_day' => $day,
            'requested_start' => Carbon::parse($startTime)->format('h:i A'),
            'requested_end' => Carbon::parse($endTime)->format('h:i A'),
            'requested_time' => $this->formatTimeRange($startTime, $endTime),
        ], $conflictingSchedule ? $this->details($conflictingSchedule) : [], $extraDetails);

        $suggestions = $this->includeSuggestions
            ? $this->buildSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId, $type)
            : [];

        if (!isset($details['suggestion']) && !empty($suggestions[0]['label'])) {
            $details['suggestion'] = $suggestions[0]['label'];
        }

        return [
            'success' => false,
            'status' => false,
            'type' => $type,
            'toast_type' => $severity,
            'severity' => $severity,
            'conflict_type' => $legacyType,
            'title' => $title,
            'message' => $this->cleanText($message),
            'details' => $details,
            'conflicting_schedule' => $conflictingSchedule ? $this->scheduleSummary($conflictingSchedule) : null,
            'suggestions' => $suggestions,
        ];
    }

    private function buildSuggestions(
        ?Subject $subject,
        ?Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId,
        string $type
    ): array {
        if (!$subject || !$room) {
            return [];
        }

        $suggestions = [];

        if (in_array($type, ['room_conflict', 'room_type_conflict', 'capacity_conflict'], true)) {
            $suggestions = array_merge(
                $suggestions,
                $this->roomSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId)
            );
        }

        if (in_array($type, ['room_conflict', 'faculty_conflict', 'section_conflict', 'inactive_day_conflict', 'faculty_availability_conflict', 'time_conflict'], true)) {
            $suggestions = array_merge(
                $suggestions,
                $this->timeSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId)
            );
        }

        return collect($suggestions)
            ->unique(fn (array $suggestion) => ($suggestion['type'] ?? '') . '|' . ($suggestion['label'] ?? ''))
            ->take(6)
            ->values()
            ->all();
    }

    private function roomSuggestions(
        Subject $subject,
        Room $requestedRoom,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): array {
        $preferredType = $this->subjectRequiresLab($subject)
            ? 'lab'
            : $this->canonicalRoomType($requestedRoom);

        return Room::query()
            ->select('id', 'room_name', 'type', 'capacity', 'specialization', 'floor')
            ->available()
            ->whereKeyNot($requestedRoom->id)
            ->orderBy('room_name')
            ->get()
            ->filter(fn (Room $room) => $this->canonicalRoomType($room) === $preferredType)
            ->filter(fn (Room $room) => $this->roomCanHostSubject($room, $subject))
            ->filter(fn (Room $room) => $this->roomAvailable($room->id, $day, $startTime, $endTime, $ignoreScheduleId))
            ->take(3)
            ->map(fn (Room $room) => [
                'type' => 'room',
                'label' => "{$room->room_name} available at the same time",
                'room_id' => $room->id,
                'room_name' => $room->room_name,
                'day' => $day,
                'start_time' => $this->normalizeTime($startTime),
                'end_time' => $this->normalizeTime($endTime),
            ])
            ->values()
            ->all();
    }

    private function timeSuggestions(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): array {
        $settings = Setting::getScheduleSettings();
        $activeDays = $settings['active_days'];
        $boundsStart = Carbon::parse($settings['start_time']);
        $boundsEnd = Carbon::parse($settings['end_time']);
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        $requestedDayIndex = array_search($day, $activeDays, true);
        $requestedDayIndex = $requestedDayIndex === false ? 0 : $requestedDayIndex;
        $requestedStartMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse('00:00:00'));
        $candidates = [];

        foreach ($activeDays as $dayIndex => $candidateDay) {
            $latestStart = $boundsEnd->copy()->subMinutes($durationMinutes);

            if ($latestStart->lt($boundsStart)) {
                continue;
            }

            $period = CarbonPeriod::create(
                $boundsStart->copy(),
                self::SLOT_MINUTES . ' minutes',
                $latestStart
            );

            foreach ($period as $slotStart) {
                $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);
                $candidateStart = $slotStart->format('H:i:s');
                $candidateEnd = $slotEnd->format('H:i:s');

                if ($candidateDay === $day && $candidateStart === $this->normalizeTime($startTime)) {
                    continue;
                }

                if ($this->overlapsLunchBreak($candidateStart, $candidateEnd)) {
                    continue;
                }

                if (!$this->placementAvailable($subject, $room, $candidateDay, $candidateStart, $candidateEnd, $ignoreScheduleId)) {
                    continue;
                }

                $candidateStartMinutes = $slotStart->diffInMinutes(Carbon::parse('00:00:00'));
                $dayDistance = abs($dayIndex - $requestedDayIndex);
                $timeDistance = abs($candidateStartMinutes - $requestedStartMinutes);

                $candidates[] = [
                    'type' => 'time',
                    'label' => "{$candidateDay} " . $this->formatTimeRange($candidateStart, $candidateEnd),
                    'room_id' => $room->id,
                    'room_name' => $room->room_name,
                    'day' => $candidateDay,
                    'start_time' => $candidateStart,
                    'end_time' => $candidateEnd,
                    'sort' => ($dayDistance * 10000) + $timeDistance,
                ];
            }
        }

        return collect($candidates)
            ->sortBy('sort')
            ->take(4)
            ->map(function (array $suggestion) {
                unset($suggestion['sort']);

                return $suggestion;
            })
            ->values()
            ->all();
    }

    private function placementAvailable(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): bool {
        if (!Setting::dayIsActive($day)) {
            return false;
        }

        if (!$this->roomCanHostSubject($room, $subject)) {
            return false;
        }

        if ($this->overlapsLunchBreak($startTime, $endTime)) {
            return false;
        }

        if (!$this->roomAvailable($room->id, $day, $startTime, $endTime, $ignoreScheduleId)) {
            return false;
        }

        if (!$this->sectionAvailable($subject, $day, $startTime, $endTime, $ignoreScheduleId)) {
            return false;
        }

        if ($subject->faculty_id && !$this->facultyAvailable((int) $subject->faculty_id, $day, $startTime, $endTime, $ignoreScheduleId)) {
            return false;
        }

        if ($subject->faculty_id && $subject->relationLoaded('faculty') && $subject->faculty) {
            $availability = $this->checkFacultyAvailability($subject->faculty, $day, $startTime, $endTime);

            return ($availability['success'] ?? $availability['status'] ?? true) !== false;
        }

        return true;
    }

    private function roomAvailable(int $roomId, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId = null): bool
    {
        return !$this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where('room_id', $roomId)
            ->exists();
    }

    private function sectionAvailable(Subject $subject, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId = null): bool
    {
        return !$this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where(function (Builder $query) use ($subject) {
                $query->where(function (Builder $scheduleQuery) use ($subject) {
                    $scheduleQuery->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', $subject->year_level)
                        ->where('section', $subject->section);
                })->orWhereHas('subject', function (Builder $subjectQuery) use ($subject) {
                    $subjectQuery->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', $subject->year_level)
                        ->where('section', $subject->section);
                });
            })
            ->exists();
    }

    private function facultyAvailable(int $facultyId, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId = null): bool
    {
        return !$this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where(function (Builder $query) use ($facultyId) {
                $query->where('faculty_id', $facultyId)
                    ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('faculty_id', $facultyId));
            })
            ->exists();
    }

    private function roomCanHostSubject(Room $room, Subject $subject): bool
    {
        try {
            return app(AutoGenerateScheduler::class)->isRoomCompatible($room, $subject, allowMinorLabFallback: true);
        } catch (\Throwable) {
            if (!$this->capacityFits($room, $subject)) {
                return false;
            }

            return !$this->subjectRequiresLab($subject) || $this->roomIsLab($room);
        }
    }

    private function capacityFits(Room $room, Subject $subject): bool
    {
        $expectedSize = $this->expectedClassSize($subject);

        return !$expectedSize || !$room->capacity || (int) $room->capacity >= $expectedSize;
    }

    private function expectedClassSize(Subject $subject): ?int
    {
        foreach (['student_count', 'enrollment', 'class_size', 'expected_students'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function scheduleSummary(Schedule $schedule): array
    {
        return [
            'subject_code' => $this->cleanText($schedule->subject?->subject_code ?? 'Unknown'),
            'subject_name' => $this->cleanText($schedule->subject?->description ?? 'No subject description'),
            'room' => $this->cleanText($schedule->room?->room_name ?? 'Unassigned room'),
            'day' => $schedule->day,
            'time' => $this->formatTimeRange($schedule->start_time, $schedule->end_time),
            'faculty' => $this->cleanText($schedule->faculty?->full_name ?? $schedule->subject?->faculty?->full_name ?? 'Unassigned'),
        ];
    }

    private function conflictingScheduleFromDetails(array $details): ?array
    {
        if (empty($details['conflicting_subject']) && empty($details['conflicting_room'])) {
            return null;
        }

        $start = $details['conflicting_start'] ?? null;
        $end = $details['conflicting_end'] ?? null;

        return [
            'subject_code' => $details['conflicting_subject'] ?? 'Unknown',
            'subject_name' => $details['conflicting_subject_name'] ?? 'No subject description',
            'room' => $details['conflicting_room'] ?? 'Unassigned room',
            'day' => $details['conflicting_day'] ?? null,
            'time' => $start && $end ? "{$start} - {$end}" : ($details['conflicting_time'] ?? null),
            'faculty' => $details['faculty_name'] ?? 'Unassigned',
        ];
    }

    private function details(Schedule $schedule): array
    {
        return [
            'conflicting_schedule_id' => $schedule->id,
            'conflicting_subject' => $this->cleanText($schedule->subject?->subject_code ?? 'Unknown'),
            'conflicting_subject_name' => $this->cleanText($schedule->subject?->description ?? 'No subject description'),
            'conflicting_room' => $this->cleanText($schedule->room?->room_name ?? 'Unknown'),
            'conflicting_start' => Carbon::parse($schedule->start_time)->format('h:i A'),
            'conflicting_end' => Carbon::parse($schedule->end_time)->format('h:i A'),
            'conflicting_day' => $schedule->day,
            'faculty_name' => $this->cleanText($schedule->faculty?->full_name ?? $schedule->subject?->faculty?->full_name ?? 'Unassigned'),
        ];
    }

    private function summaryRelations(): array
    {
        return [
            'subject:id,subject_code,description,department,major,year_level,section,faculty_id',
            'subject.faculty:id,full_name',
            'room:id,room_name,type,capacity,specialization',
            'faculty:id,full_name',
        ];
    }

    private function pass(): array
    {
        return [
            'success' => true,
            'status' => true,
            'type' => 'success',
            'toast_type' => 'success',
            'message' => null,
            'suggestions' => [],
        ];
    }

    private function studentGroup(Subject $subject): string
    {
        return "{$subject->department}-{$subject->major}-{$subject->year_level}{$subject->section}";
    }

    private function subjectRequiresLab(Subject $subject): bool
    {
        if (isset($subject->requires_lab) && (bool) $subject->requires_lab) {
            return true;
        }

        $haystack = strtoupper(trim(implode(' ', [
            (string) $subject->type,
            (string) ($subject->subject_type ?? ''),
            (string) $subject->subject_code,
            (string) $subject->description,
            (string) ($subject->specialization ?? ''),
        ])));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COMPUTER LAB')
            || str_contains($haystack, 'PROGRAMMING')
            || str_contains($haystack, 'NETWORKING')
            || str_contains($haystack, 'DATABASE')
            || str_contains($haystack, 'SYSTEMS');
    }

    private function roomIsLab(Room $room): bool
    {
        $haystack = strtoupper(trim("{$room->type} {$room->room_name} {$room->specialization}"));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COM LAB')
            || str_contains($haystack, 'COMPUTER');
    }

    private function canonicalRoomType(Room $room): string
    {
        return $this->roomIsLab($room) ? 'lab' : 'lecture';
    }

    private function normalTypeFromLegacy(string $legacyType): string
    {
        return match ($legacyType) {
            'ROOM_COMPATIBILITY' => 'room_type_conflict',
            'BLOCKED_DAY' => 'inactive_day_conflict',
            'BLOCKED_TIME' => 'time_conflict',
            'LUNCH_BREAK_CONFLICT' => 'time_conflict',
            'DUPLICATE_SCHEDULE' => 'section_conflict',
            'FACULTY_AVAILABILITY' => 'faculty_availability_conflict',
            default => strtolower($legacyType),
        };
    }

    private function legacyTypeFromType(string $type): string
    {
        return strtoupper($type);
    }

    private function titleFromType(string $legacyType): string
    {
        return match ($legacyType) {
            'ROOM_CONFLICT' => 'Room Already Occupied',
            'FACULTY_CONFLICT' => 'Faculty Member Already Teaching',
            'SECTION_CONFLICT' => 'Student Group Already Scheduled',
            'INACTIVE_DAY_CONFLICT', 'BLOCKED_DAY' => 'Inactive Schedule Day',
            'ROOM_TYPE_CONFLICT', 'ROOM_COMPATIBILITY' => 'Room Requirement Conflict',
            'CAPACITY_CONFLICT' => 'Room Capacity Too Small',
            'FACULTY_AVAILABILITY' => 'Faculty Not Available',
            default => 'Scheduling Conflict Detected',
        };
    }

    private function formatTimeRange(string $startTime, string $endTime): string
    {
        return Carbon::parse($startTime)->format('h:i A') . ' - ' . Carbon::parse($endTime)->format('h:i A');
    }

    private function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }

    private function cleanText($value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00C2}", "\u{00A0}", "\xC2\xA0"], ' ', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
