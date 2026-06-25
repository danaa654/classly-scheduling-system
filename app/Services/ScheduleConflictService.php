<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\Scheduling\AutoGenerateScheduler;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
            // Core structural checks that always apply regardless of instructor assignment
            $checks = [
                $this->checkInactiveDayConflict($day, $subject, $room, $startTime, $endTime, $ignoreScheduleId),
                $this->checkCapacityConflict($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
                $this->checkRoomTypeConflict($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
                $this->checkRoomConflict($room->id, $day, $startTime, $endTime, $ignoreScheduleId, $subject, $room),
                $this->checkSectionConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId, $room),
            ];

            // 👇 FIX: Only validate instructor schedule overlaps if a faculty member is assigned
            if (filled($subject->faculty_id ?? null) && $subject->faculty) {
                $checks[] = $this->checkFacultyConflict($subject, $day, $startTime, $endTime, $ignoreScheduleId, $room);
                $checks[] = $this->checkFacultyAvailability($subject->faculty, $day, $startTime, $endTime, $subject, $room, $ignoreScheduleId);
            }

            // Loop through and validate all criteria entries
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

        $requestedRoom ??= $conflict->room ?: Room::select($this->roomSelectColumns(['floor']))->find($roomId);
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
        $conflictingCode = $conflict->subject?->subject_code ?? 'another subject';
        $conflictingTime = $this->formatTimeRange($conflict->start_time, $conflict->end_time);

        return $this->conflictResponse(
            'section_conflict',
            'SECTION_CONFLICT',
            'Student Group Already Scheduled',
            "{$group} already has {$conflictingCode} on {$day} {$conflictingTime}, which overlaps the requested {$this->formatTimeRange($startTime, $endTime)}.",
            $subject,
            $requestedRoom,
            $day,
            $startTime,
            $endTime,
            $conflict,
            [
                'group' => $group,
                'conflict_reason' => "{$group} cannot take two classes at the same time. Move this subject to another available time.",
                'suggestion' => 'Choose a different time for this same section.',
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

    public function respectsSectionSession(?string $section, string $startTime, string $endTime, bool $allowFallback = false): bool
    {
        $section = strtoupper((string) $section);
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);

        if ($section === 'A') {
            $isMorning = $start->lt($lunchStart) && $end->lte($lunchStart);
            $isAfternoonFallback = $allowFallback && $start->gte($lunchEnd) && $end->gt($lunchEnd);

            return $isMorning || $isAfternoonFallback;
        }

        if ($section === 'B') {
            $isAfternoon = $start->gte($lunchEnd) && $end->gt($lunchEnd);
            $isMorningFallback = $allowFallback && $start->lt($lunchStart) && $end->lte($lunchStart);

            return $isAfternoon || $isMorningFallback;
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

    public function validateEditableWorkspace(array $rows, ?string $semester = null, ?string $schoolYear = null): array
    {
        $conflicts = $this->detectWorkspaceConflicts($rows, $semester, $schoolYear);

        return [
            'valid' => empty($conflicts['errors']),
            'errors' => $conflicts['errors'],
            'conflict_keys' => $conflicts['conflict_keys'],
            'recommendations' => $conflicts['recommendations'],
        ];
    }

    public function detectWorkspaceConflicts(array $rows, ?string $semester = null, ?string $schoolYear = null): array
    {
        $errors = [];
        $recommendations = [];
        $conflictKeys = [];
        $placements = [];
        $editedIds = collect($rows)
            ->flatMap(fn (array $row) => $row['schedule_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $subjects = Subject::whereIn('id', collect($rows)->pluck('subject_id')->filter()->unique())->get()->keyBy('id');
        $rooms = Room::whereIn('id', collect($rows)->pluck('room_id')->filter()->unique())->get()->keyBy('id');
        $faculties = Faculty::whereIn('id', collect($rows)->pluck('faculty_id')->filter()->unique())->get()->keyBy('id');

        foreach ($rows as $row) {
            $editKey = (string) ($row['edit_key'] ?? md5(json_encode($row)));
            $subject = $subjects->get((int) ($row['subject_id'] ?? 0));
            $room = $rooms->get((int) ($row['room_id'] ?? 0));
            $faculty = filled($row['faculty_id'] ?? null) ? $faculties->get((int) $row['faculty_id']) : null;
            $days = collect($row['days'] ?? [])
                ->map(fn ($day) => Setting::normalizeDayName((string) $day))
                ->filter()
                ->unique()
                ->values();
            $label = $row['label'] ?? $subject?->subject_code ?? 'Schedule row';

            if (blank($row['start_time'] ?? null) || blank($row['end_time'] ?? null)) {
                $errors[] = "{$label}: choose both start and end time.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            try {
                $start = $this->normalizeTime((string) $row['start_time']);
                $end = $this->normalizeTime((string) $row['end_time']);
            } catch (\Throwable) {
                $errors[] = "{$label}: selected time is invalid.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            if (!$subject) {
                $errors[] = "{$label}: subject record was not found.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            if (!$room) {
                $errors[] = "{$label}: choose a room before finishing edit mode.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            if ($days->isEmpty()) {
                $errors[] = "{$label}: choose at least one active schedule day.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            if (!Carbon::parse($start)->lt(Carbon::parse($end))) {
                $errors[] = "{$label}: start time must be earlier than end time.";
                $conflictKeys[$editKey] = true;
                continue;
            }

            if (!$this->roomCanHostSubject($room, $subject)) {
                $errors[] = "{$label}: {$room->room_name} is not compatible with this subject.";
                $conflictKeys[$editKey] = true;
            }

            if ($faculty && !$faculty->isEligibleForSubject($subject)) {
                $errors[] = "{$label}: {$faculty->full_name} is not eligible to teach this subject.";
                $conflictKeys[$editKey] = true;
            }

            foreach ($days as $day) {
                if (!Setting::dayIsActive($day)) {
                    $errors[] = "{$label}: {$day} is disabled in Global Settings.";
                    $conflictKeys[$editKey] = true;
                    continue;
                }

                if ($this->overlapsLunchBreak($start, $end)) {
                    $errors[] = "{$label}: schedule overlaps the lunch break.";
                    $conflictKeys[$editKey] = true;
                    continue;
                }

                $allowSessionFallback = in_array(strtoupper((string) $subject->section), ['A', 'B'], true);

                if (!$this->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
                    $errors[] = "{$label}: selected time is outside the allowed section session.";
                    $conflictKeys[$editKey] = true;
                    continue;
                }

                $external = Schedule::activeTerm($semester, $schoolYear)
                    ->whereNotIn('id', $editedIds)
                    ->where('day', $day)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->with(['subject:id,subject_code,department,major,year_level,section', 'room:id,room_name', 'faculty:id,full_name'])
                    ->get();

                if ($external->firstWhere('room_id', $room->id)) {
                    $errors[] = "{$label}: {$room->room_name} is already occupied on {$day}.";
                    $conflictKeys[$editKey] = true;
                }

                $sectionConflict = $external->first(function (Schedule $schedule) use ($subject) {
                    return $this->sameStudentGroup($schedule, $subject);
                });

                if ($sectionConflict) {
                    $errors[] = "{$label}: section {$subject->section} already has {$sectionConflict->subject?->subject_code} on {$day}.";
                    $conflictKeys[$editKey] = true;
                }

                if ($faculty) {
                    $facultyConflict = $external->firstWhere('faculty_id', $faculty->id);

                    if ($facultyConflict) {
                        $errors[] = "{$label}: {$faculty->full_name} is already assigned on {$day}.";
                        $conflictKeys[$editKey] = true;
                    }

                    $availability = $this->checkFacultyAvailability($faculty, $day, $start, $end, $subject, $room);
                    if (($availability['status'] ?? true) === false) {
                        $errors[] = "{$label}: " . ($availability['message'] ?? "{$faculty->full_name} is not available.");
                        $conflictKeys[$editKey] = true;
                    }
                }

                $placements[] = [
                    'edit_key' => $editKey,
                    'label' => $label,
                    'subject' => $subject,
                    'room' => $room,
                    'faculty' => $faculty,
                    'day' => $day,
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        foreach ($placements as $i => $first) {
            for ($j = $i + 1; $j < count($placements); $j++) {
                $second = $placements[$j];

                if ($first['day'] !== $second['day'] || !$this->hasTimeOverlap($first['start'], $first['end'], $second['start'], $second['end'])) {
                    continue;
                }

                if ((int) $first['room']->id === (int) $second['room']->id) {
                    $errors[] = "{$first['label']} conflicts with {$second['label']}: same room on {$first['day']}.";
                    $conflictKeys[$first['edit_key']] = true;
                    $conflictKeys[$second['edit_key']] = true;
                }

                if ($this->sameSubjectGroup($first['subject'], $second['subject'])) {
                    $errors[] = "{$first['label']} conflicts with {$second['label']}: same section on {$first['day']}.";
                    $conflictKeys[$first['edit_key']] = true;
                    $conflictKeys[$second['edit_key']] = true;
                }

                if ($first['faculty'] && $second['faculty'] && (int) $first['faculty']->id === (int) $second['faculty']->id) {
                    $errors[] = "{$first['label']} conflicts with {$second['label']}: same faculty on {$first['day']}.";
                    $conflictKeys[$first['edit_key']] = true;
                    $conflictKeys[$second['edit_key']] = true;
                }
            }
        }

        $firstPlacement = collect($placements)->first(fn (array $placement) => isset($conflictKeys[$placement['edit_key']]));
        if ($firstPlacement) {
            $recommendations = $this->buildSuggestions(
                $firstPlacement['subject'],
                $firstPlacement['room'],
                $firstPlacement['day'],
                $firstPlacement['start'],
                $firstPlacement['end'],
                null,
                'section_conflict'
            );
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'conflict_keys' => array_keys($conflictKeys),
            'recommendations' => $recommendations,
        ];
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
            'conflict_reason' => $this->defaultConflictReason($type),
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

        $suggestions = match ($type) {
            'room_conflict', 'room_type_conflict', 'capacity_conflict' =>
                $this->roomSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
            'faculty_conflict' =>
                $this->facultySuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
            'section_conflict', 'inactive_day_conflict', 'faculty_availability_conflict', 'time_conflict' =>
                $this->timeSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
            default => $this->timeSuggestions($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId),
        };

        return collect($suggestions)
            ->unique(fn (array $suggestion) => ($suggestion['type'] ?? '') . '|' . ($suggestion['label'] ?? ''))
            ->sortByDesc(fn (array $suggestion) => $suggestion['score'] ?? 0)
            ->take(8)
            ->map(function (array $suggestion) {
                unset($suggestion['sort']);

                return array_merge([
                    'id' => $suggestion['id'] ?? md5(json_encode([
                        $suggestion['type'] ?? 'suggestion',
                        $suggestion['room_id'] ?? null,
                        $suggestion['faculty_id'] ?? null,
                        $suggestion['day'] ?? null,
                        $suggestion['start_time'] ?? null,
                        $suggestion['end_time'] ?? null,
                    ])),
                    'match_label' => $this->rankLabel((int) ($suggestion['score'] ?? 0)),
                    'badge' => strtoupper((string) ($suggestion['type'] ?? 'suggestion')),
                ], $suggestion);
            })
            ->values()
            ->all();
    }

    private function defaultConflictReason(string $type): string
    {
        return match ($type) {
            'section_conflict' => 'The student section already has a class that overlaps this day and time.',
            'faculty_conflict' => 'The assigned faculty member is already teaching another class during this time.',
            'room_conflict' => 'The selected room is already occupied during this time.',
            'room_type_conflict' => 'The selected room does not match this subject requirement.',
            'capacity_conflict' => 'The selected room is too small for this class.',
            'faculty_availability_conflict' => 'The assigned faculty member is not available during this day or time.',
            'inactive_day_conflict' => 'This day is disabled in Global Settings.',
            default => 'This placement violates an existing schedule rule.',
        };
    }

    private function roomSuggestions(
        Subject $subject,
        Room $requestedRoom,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): array {
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        $allowSessionFallback = $this->sectionFallbackAllowedForSuggestions($subject, $requestedRoom, $ignoreScheduleId, $durationMinutes);

        $scheduler = app(AutoGenerateScheduler::class);
        $rooms = Room::query()
            ->select($this->roomSelectColumns())
            ->available()
            ->whereKeyNot($requestedRoom->id)
            ->orderBy('room_name')
            ->get();

        return $scheduler->findCompatibleRooms($subject, $rooms)
            ->filter(fn (array $candidate) => $this->roomAvailable($candidate['room']->id, $day, $startTime, $endTime, $ignoreScheduleId))
            ->filter(fn (array $candidate) => $this->respectsSectionSession($subject->section, $startTime, $endTime, $allowSessionFallback))
            ->filter(fn (array $candidate) => $this->facultyAndSectionAvailable($subject, $day, $startTime, $endTime, $ignoreScheduleId))
            ->take(4)
            ->map(function (array $candidate) use ($subject, $day, $startTime, $endTime) {
                $sessionFallback = $this->isSectionFallbackSlot($subject->section, $startTime, $endTime);

                return [
                    'type' => 'room',
                    'label' => "{$candidate['room']->room_name} available at the same time",
                    'room_id' => $candidate['room']->id,
                    'room_name' => $candidate['room']->room_name,
                    'day' => $day,
                    'start_time' => $this->normalizeTime($startTime),
                    'end_time' => $this->normalizeTime($endTime),
                    'session_fallback' => $sessionFallback,
                    'score' => (int) ($candidate['score'] ?? 0) + ($sessionFallback ? 45 : 70),
                ];
            })
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
        $requestedPeriod = Carbon::parse($startTime)->lt(Carbon::parse(self::LUNCH_START)) ? 'morning' : 'afternoon';
        $allowSessionFallback = $this->sectionFallbackAllowedForSuggestions($subject, $room, $ignoreScheduleId, $durationMinutes);
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

                if (!$this->respectsSectionSession($subject->section, $candidateStart, $candidateEnd, $allowSessionFallback)) {
                    continue;
                }

                if (!$this->placementAvailable($subject, $room, $candidateDay, $candidateStart, $candidateEnd, $ignoreScheduleId, $allowSessionFallback)) {
                    continue;
                }

                $candidateStartMinutes = $slotStart->diffInMinutes(Carbon::parse('00:00:00'));
                $dayDistance = abs($dayIndex - $requestedDayIndex);
                $timeDistance = abs($candidateStartMinutes - $requestedStartMinutes);
                $candidatePeriod = $slotStart->lt(Carbon::parse(self::LUNCH_START)) ? 'morning' : 'afternoon';
                $sessionFallback = $this->isSectionFallbackSlot($subject->section, $candidateStart, $candidateEnd);
                $score = 95;

                if ($candidateDay === $day) {
                    $score += 20;
                }

                if ($sessionFallback) {
                    $score += 10;
                } elseif ($requestedPeriod === $candidatePeriod) {
                    $score += 25;
                }

                if ($this->facultyDailyHoursValid($subject, $candidateDay, $candidateStart, $candidateEnd, $ignoreScheduleId)) {
                    $score += 20;
                }

                if (!$this->wouldCreateLongFacultyRun($subject, $candidateDay, $candidateStart, $candidateEnd, $ignoreScheduleId)) {
                    $score += 15;
                }

                $candidates[] = [
                    'type' => 'time',
                    'label' => ($sessionFallback ? 'Afternoon fallback: ' : '') . "{$candidateDay} " . $this->formatTimeRange($candidateStart, $candidateEnd),
                    'room_id' => $room->id,
                    'room_name' => $room->room_name,
                    'day' => $candidateDay,
                    'start_time' => $candidateStart,
                    'end_time' => $candidateEnd,
                    'session_fallback' => $sessionFallback,
                    'sort' => ($dayDistance * 10000) + $timeDistance - ($score * 20),
                    'score' => $score,
                ];
            }
        }

        return collect($candidates)
            ->sortBy('sort')
            ->take(4)
            ->values()
            ->all();
    }

    private function facultySuggestions(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): array {
        $allowSessionFallback = $this->respectsSectionSession($subject->section, $startTime, $endTime, true)
            && !$this->respectsSectionSession($subject->section, $startTime, $endTime, false);

        return Faculty::query()
            ->approved()
            ->select('id', 'full_name', 'department', 'faculty_scope', 'can_teach_minor', 'max_units', 'availability')
            ->with(['schedules' => fn ($query) => $query->activeTerm()->with('subject:id,units')])
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Faculty $faculty) => $faculty->isEligibleForSubject($subject))
            ->filter(fn (Faculty $faculty) => $this->facultyAvailable($faculty->id, $day, $startTime, $endTime, $ignoreScheduleId))
            ->filter(fn (Faculty $faculty) => $this->facultyAvailabilityAllows($faculty, $day, $startTime, $endTime))
            ->filter(fn (Faculty $faculty) => $this->facultyWorkloadAllows($faculty, $subject))
            ->map(function (Faculty $faculty) use ($subject, $room, $day, $startTime, $endTime, $ignoreScheduleId) {
                $score = $this->facultyRecommendationScore($faculty, $subject, $room, $day, $startTime, $endTime, $ignoreScheduleId);
                $sessionFallback = $this->isSectionFallbackSlot($subject->section, $startTime, $endTime);

                return [
                    'type' => 'faculty',
                    'label' => "{$faculty->full_name} is available for {$this->formatTimeRange($startTime, $endTime)}",
                    'faculty_id' => $faculty->id,
                    'faculty_name' => $faculty->full_name,
                    'room_id' => $room->id,
                    'room_name' => $room->room_name,
                    'day' => $day,
                    'start_time' => $this->normalizeTime($startTime),
                    'end_time' => $this->normalizeTime($endTime),
                    'session_fallback' => $sessionFallback,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->take(4)
            ->values()
            ->all();
    }

    private function placementAvailable(
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId,
        bool $allowSessionFallback = false
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

        if (!$this->respectsSectionSession($subject->section, $startTime, $endTime, $allowSessionFallback)) {
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

    private function sectionFallbackAllowedForSuggestions(
        Subject $subject,
        Room $room,
        ?int $ignoreScheduleId,
        int $durationMinutes
    ): bool {
        if (!in_array(strtoupper((string) $subject->section), ['A', 'B'], true)) {
            return false;
        }

        return !$this->preferredSessionPlacementExists($subject, $room, $ignoreScheduleId, $durationMinutes);
    }

    private function preferredSessionPlacementExists(
        Subject $subject,
        Room $room,
        ?int $ignoreScheduleId,
        int $durationMinutes
    ): bool {
        $settings = Setting::getScheduleSettings();
        $preferredWindows = $this->preferredSectionWindows($subject->section, $settings);
        $rooms = app(AutoGenerateScheduler::class)
            ->findCompatibleRooms($subject, Room::query()->select($this->roomSelectColumns())->available()->get())
            ->pluck('room')
            ->whenEmpty(fn (Collection $rooms) => $rooms->push($room));

        if (empty($preferredWindows)) {
            return false;
        }

        foreach ($settings['active_days'] as $candidateDay) {
            foreach ($preferredWindows as $window) {
                $latestStart = $window['end']->copy()->subMinutes($durationMinutes);

                if ($latestStart->lt($window['start'])) {
                    continue;
                }

                $period = CarbonPeriod::create(
                    $window['start']->copy(),
                    self::SLOT_MINUTES . ' minutes',
                    $latestStart
                );

                foreach ($period as $slotStart) {
                    $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);
                    $candidateStart = $slotStart->format('H:i:s');
                    $candidateEnd = $slotEnd->format('H:i:s');

                    foreach ($rooms as $candidateRoom) {
                        if ($this->placementAvailable($subject, $candidateRoom, $candidateDay, $candidateStart, $candidateEnd, $ignoreScheduleId, false)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function isSectionFallbackSlot(?string $section, string $startTime, string $endTime): bool
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);
        $section = strtoupper((string) $section);

        return ($section === 'A' && $start->gte($lunchEnd) && $end->gt($lunchEnd))
            || ($section === 'B' && $start->lt($lunchStart) && $end->lte($lunchStart));
    }

    private function facultyAndSectionAvailable(Subject $subject, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId): bool
    {
        if (!$this->sectionAvailable($subject, $day, $startTime, $endTime, $ignoreScheduleId)) {
            return false;
        }

        if ($subject->faculty_id && !$this->facultyAvailable((int) $subject->faculty_id, $day, $startTime, $endTime, $ignoreScheduleId)) {
            return false;
        }

        if ($subject->faculty_id && $subject->relationLoaded('faculty') && $subject->faculty) {
            return $this->facultyAvailabilityAllows($subject->faculty, $day, $startTime, $endTime);
        }

        return true;
    }

    private function facultyAvailabilityAllows(Faculty $faculty, string $day, string $startTime, string $endTime): bool
    {
        $previous = $this->includeSuggestions;
        $this->includeSuggestions = false;

        try {
            $availability = $this->checkFacultyAvailability($faculty, $day, $startTime, $endTime);

            return ($availability['status'] ?? true) !== false;
        } finally {
            $this->includeSuggestions = $previous;
        }
    }

    private function facultyWorkloadAllows(Faculty $faculty, Subject $subject): bool
    {
        $currentUnits = $faculty->schedules
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sum(fn (Subject $assignedSubject) => (int) ($assignedSubject->units ?? 0));

        return ($currentUnits + (int) ($subject->units ?? 0)) <= (int) ($faculty->max_units ?? 21);
    }

    private function facultyDailyHoursValid(Subject $subject, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId, ?int $facultyId = null): bool
    {
        $facultyId ??= $subject->faculty_id ? (int) $subject->faculty_id : null;

        if (!$facultyId) {
            return true;
        }

        $maxDailyHours = (float) Setting::getValue('max_daily_hours_per_faculty', 8);
        $newHours = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)) / 60;
        $existingHours = Schedule::activeTerm()
            ->where('faculty_id', $facultyId)
            ->when($ignoreScheduleId, fn (Builder $query) => $query->whereKeyNot($ignoreScheduleId))
            ->where('day', $day)
            ->get()
            ->sum(fn (Schedule $schedule) => Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time)) / 60);

        return ($existingHours + $newHours) <= $maxDailyHours;
    }

    private function wouldCreateLongFacultyRun(Subject $subject, string $day, string $startTime, string $endTime, ?int $ignoreScheduleId, ?int $facultyId = null): bool
    {
        $facultyId ??= $subject->faculty_id ? (int) $subject->faculty_id : null;

        if (!$facultyId) {
            return false;
        }

        $blocks = Schedule::activeTerm()
            ->where('faculty_id', $facultyId)
            ->when($ignoreScheduleId, fn (Builder $query) => $query->whereKeyNot($ignoreScheduleId))
            ->where('day', $day)
            ->get(['start_time', 'end_time'])
            ->map(fn (Schedule $schedule) => [
                'start' => Carbon::parse($schedule->start_time),
                'end' => Carbon::parse($schedule->end_time),
            ])
            ->push([
                'start' => Carbon::parse($startTime),
                'end' => Carbon::parse($endTime),
            ])
            ->sortBy('start')
            ->values();

        $runStart = null;
        $runEnd = null;

        foreach ($blocks as $block) {
            if (!$runStart) {
                $runStart = $block['start']->copy();
                $runEnd = $block['end']->copy();
                continue;
            }

            if ($block['start']->diffInMinutes($runEnd, false) >= -30) {
                $runEnd = $block['end']->gt($runEnd) ? $block['end']->copy() : $runEnd;
            } else {
                if ($runStart->diffInMinutes($runEnd) > 240) {
                    return true;
                }

                $runStart = $block['start']->copy();
                $runEnd = $block['end']->copy();
            }
        }

        return $runStart && $runStart->diffInMinutes($runEnd) > 240;
    }

    private function facultyRecommendationScore(
        Faculty $faculty,
        Subject $subject,
        Room $room,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId
    ): int {
        $score = 80;

        if (Department::codesMatch($faculty->department, $subject->department)) {
            $score += 70;
        }

        if (Department::codesMatch($faculty->department, $subject->major)) {
            $score += 25;
        }

        if ($faculty->isGenEd() && $this->subjectIsMinorOrGenEd($subject)) {
            $score += 40;
        }

        if ($this->facultyDailyHoursValid($subject, $day, $startTime, $endTime, $ignoreScheduleId, $faculty->id)) {
            $score += 25;
        }

        if (!$this->wouldCreateLongFacultyRun($subject, $day, $startTime, $endTime, $ignoreScheduleId, $faculty->id)) {
            $score += 15;
        }

        if ($this->roomCanHostSubject($room, $subject)) {
            $score += 10;
        }

        return $score;
    }

    private function subjectIsMinorOrGenEd(Subject $subject): bool
    {
        $type = strtolower(trim((string) $subject->type));
        $subjectType = strtolower(trim((string) ($subject->subject_type ?? '')));
        $department = Department::normalizeCode($subject->department);
        $major = Department::normalizeCode($subject->major);

        return $type === 'minor'
            || $subjectType === 'minor'
            || $department === 'GENED'
            || $major === 'GENED'
            || str_contains(strtoupper((string) $subject->subject_code), 'NSTP')
            || str_contains(strtoupper((string) $subject->subject_code), 'PATHFIT');
    }

    private function rankLabel(int $score): string
    {
        if ($score >= 170) {
            return 'BEST MATCH';
        }

        if ($score >= 110) {
            return 'GOOD MATCH';
        }

        return 'FALLBACK';
    }

    private function roomSelectColumns(array $extra = []): array
    {
        $columns = ['id', 'room_name', 'type', 'capacity', 'specialization'];

        foreach (array_merge(['floor', 'room_type', 'allowed_departments', 'department_owner', 'is_specialized'], $extra) as $column) {
            if (Schema::hasColumn('rooms', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
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

    private function sameStudentGroup(Schedule $schedule, Subject $subject): bool
    {
        $scheduleSubject = $schedule->subject;

        $department = $scheduleSubject?->department ?? $schedule->department;
        $major = $scheduleSubject?->major ?? $schedule->major;
        $yearLevel = $scheduleSubject?->year_level ?? $schedule->year_level;
        $section = $scheduleSubject?->section ?? $schedule->section;

        return (string) $department === (string) $subject->department
            && (string) $major === (string) $subject->major
            && (int) $yearLevel === (int) $subject->year_level
            && strtoupper((string) $section) === strtoupper((string) $subject->section);
    }

    private function sameSubjectGroup(Subject $first, Subject $second): bool
    {
        return (string) $first->department === (string) $second->department
            && (string) $first->major === (string) $second->major
            && (int) $first->year_level === (int) $second->year_level
            && strtoupper((string) $first->section) === strtoupper((string) $second->section);
    }

    private function preferredSectionWindows(?string $section, array $settings): array
    {
        $dayStart = Carbon::parse($settings['start_time']);
        $dayEnd = Carbon::parse($settings['end_time']);
        $lunchStart = Carbon::parse(self::LUNCH_START);
        $lunchEnd = Carbon::parse(self::LUNCH_END);

        $morning = [
            'start' => $dayStart->copy(),
            'end' => $dayEnd->lt($lunchStart) ? $dayEnd->copy() : $lunchStart->copy(),
        ];
        $afternoon = [
            'start' => $dayStart->gt($lunchEnd) ? $dayStart->copy() : $lunchEnd->copy(),
            'end' => $dayEnd->copy(),
        ];

        $windows = match (strtoupper((string) $section)) {
            'A' => [$morning],
            'B' => [$afternoon],
            default => [$morning, $afternoon],
        };

        return array_values(array_filter($windows, fn (array $window) => $window['start']->lt($window['end'])));
    }

    private function subjectRequiresLab(Subject $subject): bool
    {
        $explicitRoomType = strtoupper(trim((string) ($subject->preferred_room_type ?? '')));

        // Explicit override from ManageSubjects checkbox is always authoritative.
        // Must be checked FIRST — before requires_lab and before keyword heuristics —
        // so a user-set "Use Lecture Room" override is never defeated by a keyword
        // match (e.g. "Systems Administration", "Database Management").
        if ($explicitRoomType === 'LECTURE') {
            return false; // user said "lecture room" → never a lab subject
        }
        if ($explicitRoomType === 'LAB') {
            return true;  // user said "lab room" → always a lab subject
        }

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

        // NETWORKING, DATABASE, SYSTEMS removed — these false-positive on purely lecture
        // subjects (e.g. "Systems Administration", "Database Management", "Networking Fundamentals").
        // requires_lab column and preferred_room_type = 'LAB' are the authoritative signals.
        // PRACTICUM and WORKSHOP added as unambiguous practical-subject keywords.
        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COMPUTER LAB')
            || str_contains($haystack, 'PROGRAMMING')
            || str_contains($haystack, 'PRACTICUM')
            || str_contains($haystack, 'WORKSHOP');
    }

    private function roomIsLab(Room $room): bool
    {
        $haystack = strtoupper(trim(implode(' ', [
            (string) ($room->type ?? ''),
            (string) ($room->room_type ?? ''),
            (string) ($room->room_name ?? ''),
            (string) ($room->specialization ?? ''),
        ])));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COM LAB')
            || str_contains($haystack, 'COMPUTER')
            || str_contains($haystack, 'WORKSHOP')
            || str_contains($haystack, 'KITCHEN')
            || str_contains($haystack, 'HOSPITALITY');
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