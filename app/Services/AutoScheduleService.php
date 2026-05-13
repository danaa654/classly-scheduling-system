<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AutoScheduleService
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    private const SLOT_MINUTES = 30;
    private const LUNCH_START = '12:00:00';
    private const LUNCH_END = '13:00:00';
    private const GENERAL_ROOM_SPECIALIZATIONS = [
        'GENERAL',
        'GEN',
        'LECTURE',
        'CLASSROOM',
        'MINOR',
        'ALL',
        'COMMON',
    ];
    private const SPECIALIZATION_GROUPS = [
        'IT' => ['IT', 'ACT', 'CCS'],
        'ACT' => ['ACT', 'IT', 'CCS'],
        'CCS' => ['CCS', 'IT', 'ACT'],
        'HM' => ['HM', 'TM', 'SHTM'],
        'TM' => ['TM', 'HM', 'SHTM'],
        'SHTM' => ['SHTM', 'HM', 'TM'],
        'FB' => ['FB', 'LD', 'QD', 'COC'],
        'LD' => ['LD', 'FB', 'QD', 'COC'],
        'QD' => ['QD', 'FB', 'LD', 'COC'],
        'COC' => ['COC', 'FB', 'LD', 'QD'],
        'ED' => ['ED', 'CTE'],
        'CTE' => ['CTE', 'ED'],
    ];
    private const DAY_PAIRINGS = [
        1 => [
            ['Monday'],
            ['Tuesday'],
            ['Wednesday'],
            ['Thursday'],
            ['Friday'],
            ['Saturday'],
        ],
        2 => [
            ['Monday', 'Wednesday'],
            ['Tuesday', 'Thursday'],
            ['Wednesday', 'Friday'],
        ],
        3 => [
            ['Monday', 'Wednesday', 'Friday'],
            ['Tuesday', 'Thursday', 'Saturday'],
        ],
    ];

    public function __construct(private ScheduleConflictService $conflicts)
    {
    }

    public function generatePartialSchedules(array $filters = [], ?int $userId = null, bool $persist = false): array
    {
        set_time_limit(300);

        $missingFilters = $this->missingRequiredFilters($filters);
        if ($missingFilters) {
            return $this->emptyResult('select ' . implode(', ', $missingFilters) . ' before generating schedules');
        }

        $bounds = Setting::getDayBounds();
        $rooms = Room::query()
            ->select('id', 'room_name', 'type', 'capacity', 'specialization', 'floor')
            ->available()
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        if ($rooms->isEmpty()) {
            return $this->emptyResult('no valid room registry rows available');
        }

        $existingSchedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->where(function ($query) use ($filters) {
                $query->where('department', strtoupper($filters['department']))
                    ->where('major', strtoupper($filters['major']))
                    ->where('year_level', (int) $filters['year_level'])
                    ->where('section', strtoupper($filters['section']));
            })
            ->orWhereIn('room_id', $rooms->pluck('id'))
            ->get();

        $subjects = $this->subjectQuery($filters)
            ->withCount('schedules')
            ->get()
            ->sortByDesc(fn (Subject $subject) => $this->subjectDifficultyScore($subject))
            ->groupBy(fn (Subject $subject) => $this->groupKey($subject));

        $result = [
            'scheduled' => 0,
            'failed' => 0,
            'warnings' => 0,
            'failure_reasons' => [],
            'failed_items' => [],
            'fallback_warnings' => [],
            'scheduled_items' => [],
            'filters' => [
                'department' => strtoupper($filters['department']),
                'major' => strtoupper($filters['major']),
                'year_level' => (int) $filters['year_level'],
                'section' => strtoupper($filters['section']),
            ],
        ];

        foreach ($subjects as $groupSubjects) {
            foreach ($groupSubjects as $subject) {
                $remainingMeetings = max(0, (int) $subject->meetings_per_week - (int) $subject->schedules_count);

                if ($remainingMeetings <= 0) {
                    continue;
                }

                $linkedPattern = $this->generateLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, $remainingMeetings);

                if (!$linkedPattern) {
                    $reason = $this->compatibleRooms($rooms, $subject)->isEmpty()
                        ? 'no compatible room'
                        : 'no linked room/time group';

                    $this->recordFailure($result, $subject, $reason);
                    continue;
                }

                $pairingKey = $linkedPattern['pairing_key'];
                $dayPair = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

                foreach ($linkedPattern['placements'] as $placement) {
                    $scheduleData = [
                        'subject_id' => $subject->id,
                        'room_id' => $placement['room']->id,
                        'user_id' => $userId,
                        'department' => $subject->department,
                        'major' => $subject->major,
                        'year_level' => $subject->year_level,
                        'section' => $subject->section,
                        'day' => $placement['day'],
                        'start_time' => $placement['start'],
                        'end_time' => $placement['end'],
                        'duration_hours' => round(Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60, 2),
                        'meetings_per_week' => $subject->meetings_per_week,
                        'pairing_key' => $pairingKey,
                        'status' => Schedule::STATUS_PARTIAL,
                    ];

                    $schedule = $persist
                        ? Schedule::create($scheduleData)
                        : new Schedule($scheduleData);

                    $schedule->setRelation('subject', $subject);
                    $existingSchedules->push($schedule);

                    $result['scheduled']++;
                    $result['scheduled_items'][] = [
                        'subject_code' => $subject->subject_code,
                        'subject_name' => $subject->description,
                        'edp_code' => $subject->edp_code,
                        'room' => $placement['room']->room_name,
                        'day_pair' => $dayPair,
                        'day' => $placement['day'],
                        'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                        'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                        'instructor' => 'Unassigned',
                        'raw_start_time' => $placement['start'],
                        'raw_end_time' => $placement['end'],
                        'subject_id' => $subject->id,
                        'room_id' => $placement['room']->id,
                        'duration_hours' => $scheduleData['duration_hours'],
                        'meetings_per_week' => $subject->meetings_per_week,
                        'pairing_key' => $pairingKey,
                    ];

                    if ($placement['fallback']) {
                        $result['warnings']++;
                        $result['fallback_warnings'][] = "{$subject->subject_code} used fallback room {$placement['room']->room_name}.";
                    }
                }
            }
        }

        $result['failed'] = count($result['failure_reasons']);

        return $result;
    }

    public function persistGeneratedSchedules(array $items, ?int $userId = null): array
    {
        set_time_limit(300);

        $subjectIds = collect($items)->pluck('subject_id')->filter()->unique()->values();
        $roomIds = collect($items)->pluck('room_id')->filter()->unique()->values();

        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get()
            ->keyBy('id');

        $rooms = Room::query()
            ->whereIn('id', $roomIds)
            ->get()
            ->keyBy('id');

        $existingSchedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->whereIn('room_id', $roomIds)
            ->orWhereIn('subject_id', $subjectIds)
            ->get();

        $result = [
            'saved' => 0,
            'failed' => 0,
            'saved_items' => [],
            'failure_reasons' => [],
        ];

        $groups = collect($items)
            ->values()
            ->groupBy(fn (array $item, int $index) => filled($item['pairing_key'] ?? null)
                ? 'pairing:' . $item['pairing_key']
                : 'single:' . $index);

        foreach ($groups as $groupItems) {
            $validatedSchedules = collect();
            $schedulePayloads = [];
            $groupFailed = false;

            foreach ($groupItems as $item) {
                $subject = $subjects->get($item['subject_id'] ?? null);
                $room = $rooms->get($item['room_id'] ?? null);
                $day = (string) ($item['day'] ?? '');
                $start = (string) ($item['raw_start_time'] ?? '');
                $end = (string) ($item['raw_end_time'] ?? '');
                $pairingKey = $item['pairing_key'] ?? null;

                if (!$subject || !$room || !$day || !$start || !$end) {
                    $result['failure_reasons'][] = ($item['subject_code'] ?? 'Unknown subject') . ': invalid generated schedule data';
                    $groupFailed = true;
                    break;
                }

                if ($day === 'Sunday' || !in_array($day, self::DAYS, true)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: Sunday schedules are not allowed";
                    $groupFailed = true;
                    break;
                }

                $start = Carbon::parse($start)->format('H:i:s');
                $end = Carbon::parse($end)->format('H:i:s');
                $validationSchedules = $existingSchedules->concat($validatedSchedules);

                if (!$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: room is no longer compatible";
                    $groupFailed = true;
                    break;
                }

                if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: generated slot overlaps lunch break";
                    $groupFailed = true;
                    break;
                }

                if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: generated slot violates section session rules";
                    $groupFailed = true;
                    break;
                }

                if ($this->hasRoomConflict($validationSchedules, $room->id, $day, $start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: room conflict detected before save";
                    $groupFailed = true;
                    break;
                }

                if ($this->hasSectionConflict($validationSchedules, $subject, $day, $start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: section conflict detected before save";
                    $groupFailed = true;
                    break;
                }

                $payload = [
                    'subject_id' => $subject->id,
                    'room_id' => $room->id,
                    'user_id' => $userId,
                    'department' => $subject->department,
                    'major' => $subject->major,
                    'year_level' => $subject->year_level,
                    'section' => $subject->section,
                    'day' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_hours' => $item['duration_hours'] ?? round(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)) / 60, 2),
                    'meetings_per_week' => $item['meetings_per_week'] ?? $subject->meetings_per_week,
                    'pairing_key' => $pairingKey,
                    'status' => Schedule::STATUS_PARTIAL,
                ];

                $previewSchedule = new Schedule($payload);
                $previewSchedule->setRelation('subject', $subject);
                $validatedSchedules->push($previewSchedule);
                $schedulePayloads[] = [$payload, $item, $subject];
            }

            if ($groupFailed) {
                continue;
            }

            foreach ($schedulePayloads as [$payload, $item, $subject]) {
                $schedule = Schedule::create($payload);
                $schedule->setRelation('subject', $subject);
                $existingSchedules->push($schedule);

                $result['saved']++;
                $result['saved_items'][] = $item;
            }
        }

        $result['failed'] = count($result['failure_reasons']);

        return $result;
    }

    public function missingRequiredFilters(array $filters): array
    {
        return collect(['department', 'major', 'year_level', 'section'])
            ->filter(fn (string $key) => blank($filters[$key] ?? null))
            ->values()
            ->all();
    }

    public function compatibilityScore(Room $room, Subject $subject): int
    {
        if (!$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
            return 0;
        }

        $score = 0;
        $subjectSpecializations = $this->subjectSpecializations($subject);
        $subjectType = strtoupper((string) $subject->type);
        $requiresLab = $this->subjectRequiresLab($subject);
        $isLabRoom = $this->roomIsLab($room);
        $specializations = $this->roomSpecializations($room);

        if ($this->hasExactSpecializationMatch($subjectSpecializations, $specializations)) {
            $score += 120;
        } elseif ($this->hasCompatibleSpecializationMatch($subjectSpecializations, $specializations)) {
            $score += 85;
        } elseif ($this->isGeneralRoom($room)) {
            $score += 35;
        }

        if ($this->roomTypeMatches($room, $subject)) {
            $score += 40;
        }

        if ($requiresLab && $isLabRoom) {
            $score += 45;
        }

        if ($this->capacityFits($room, $subject)) {
            $score += 10;
        }

        if ($subjectType === 'MAJOR') {
            $score += 20;
        }

        if ($subjectType === 'MINOR') {
            if ($this->isGeneralRoom($room) && !$isLabRoom) {
                $score += 70;
            } elseif (!$isLabRoom) {
                $score += 40;
            }
        }

        return $score;
    }

    public function findCompatibleRooms(Subject $subject, ?Collection $rooms = null): Collection
    {
        $rooms ??= Room::query()
            ->select('id', 'room_name', 'type', 'capacity', 'specialization', 'floor')
            ->available()
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        return $this->compatibleRooms($rooms, $subject);
    }

    public function isRoomCompatible(Room $room, Subject $subject, bool $allowMinorLabFallback = false): bool
    {
        if (!$this->capacityFits($room, $subject)) {
            return false;
        }

        $subjectType = strtoupper((string) $subject->type);
        $subjectSpecializations = $this->subjectSpecializations($subject);
        $isLab = $this->roomIsLab($room);
        $requiresLab = $this->subjectRequiresLab($subject);
        $specializations = $this->roomSpecializations($room);
        $hasExactMatch = $this->hasExactSpecializationMatch($subjectSpecializations, $specializations);
        $hasCompatibleMatch = $this->hasCompatibleSpecializationMatch($subjectSpecializations, $specializations);
        $hasSpecializationMatch = $hasExactMatch || $hasCompatibleMatch;
        $isGeneral = $this->isGeneralRoom($room);

        if ($requiresLab && !$isLab) {
            return false;
        }

        if ($requiresLab) {
            if ($this->roomConflictsWithSubjectDomain($room, $subjectSpecializations)) {
                return false;
            }

            return $hasSpecializationMatch || $this->isAcceptableGeneralLab($room, $subjectSpecializations);
        }

        if ($subjectType === 'MAJOR') {
            if ($isLab && !$isGeneral) {
                return $hasSpecializationMatch;
            }

            return $hasSpecializationMatch || $isGeneral || !$isLab;
        }

        if ($isLab && !$isGeneral) {
            return false;
        }

        return $isGeneral || !$isLab || $allowMinorLabFallback;
    }

    public function hasRoomConflict(Collection $schedules, int $roomId, string $day, string $start, string $end): bool
    {
        return !$this->roomAvailable($schedules, $roomId, $day, $start, $end);
    }

    public function hasSectionConflict(Collection $schedules, Subject $subject, string $day, string $start, string $end): bool
    {
        return !$this->sectionAvailable($schedules, $subject, $day, $start, $end);
    }

    public function findAvailableSlot(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingIndex = 0): ?array
    {
        $pattern = $this->findConsistentRoomAndTime($subject, $rooms, $existingSchedules, Setting::getDayBounds(), 1, $meetingIndex);

        return $pattern['placements'][0] ?? null;
    }

    public function generateTimeSlots(?string $section = null): array
    {
        $slots = [];

        foreach ($this->sectionWindows($section, Setting::getDayBounds()) as $window) {
            $period = CarbonPeriod::create($window['start'], self::SLOT_MINUTES . ' minutes', $window['end']);

            foreach ($period as $slotStart) {
                $slotEnd = $slotStart->copy()->addMinutes(self::SLOT_MINUTES);

                if ($slotEnd->gt($window['end']) || $this->conflicts->overlapsLunchBreak($slotStart->format('H:i:s'), $slotEnd->format('H:i:s'))) {
                    continue;
                }

                $slots[] = [
                    'start' => $slotStart->format('H:i:s'),
                    'end' => $slotEnd->format('H:i:s'),
                ];
            }
        }

        return $slots;
    }

    private function subjectQuery(array $filters)
    {
        return Subject::query()
            ->select('id', 'edp_code', 'subject_code', 'description', 'section', 'major', 'year_level', 'department', 'units', 'duration_hours', 'type', 'subject_type', 'specialization', 'meetings_per_week')
            ->where('meetings_per_week', '>', 0)
            ->when(!empty($filters['department']), fn ($query) => $query->where('department', strtoupper($filters['department'])))
            ->when(!empty($filters['major']), fn ($query) => $query->where('major', strtoupper($filters['major'])))
            ->when(!empty($filters['year_level']), fn ($query) => $query->where('year_level', (int) $filters['year_level']))
            ->when(!empty($filters['section']), fn ($query) => $query->where('section', strtoupper($filters['section'])))
            ->orderBy('department')
            ->orderBy('major')
            ->orderBy('year_level')
            ->orderBy('section')
            ->orderBy('type')
            ->orderByDesc('duration_hours')
            ->orderBy('subject_code');
    }

    public function previewSubjectSchedule(int $subjectId, array $filters, array $overrides = [], array $pendingItems = [], ?int $userId = null): array
    {
        set_time_limit(300);

        $subject = Subject::query()
            ->select('id', 'edp_code', 'subject_code', 'description', 'section', 'major', 'year_level', 'department', 'units', 'duration_hours', 'type', 'subject_type', 'specialization', 'meetings_per_week')
            ->find($subjectId);

        if (!$subject) {
            return $this->emptyResult('subject not found');
        }

        if (isset($overrides['duration_hours']) && is_numeric($overrides['duration_hours'])) {
            $subject->duration_hours = max(0.5, (float) $overrides['duration_hours']);
        }

        if (isset($overrides['meetings_per_week']) && is_numeric($overrides['meetings_per_week'])) {
            $subject->meetings_per_week = max(1, (int) $overrides['meetings_per_week']);
        }

        $rooms = Room::query()
            ->select('id', 'room_name', 'type', 'capacity', 'specialization', 'floor')
            ->available()
            ->when(!empty($overrides['preferred_room_type']), function ($query) use ($overrides) {
                $preference = trim((string) $overrides['preferred_room_type']);
                $normalizedType = strtoupper($preference);

                $query->where(function ($roomQuery) use ($preference, $normalizedType) {
                    $roomQuery->where('type', $normalizedType)
                        ->orWhere('room_name', 'like', "%{$preference}%")
                        ->orWhere('specialization', 'like', "%{$preference}%");
                });
            })
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        $existingSchedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section')
            ->where(function ($query) use ($subject) {
                $query->where('department', $subject->department)
                    ->where('major', $subject->major)
                    ->where('year_level', (int) $subject->year_level)
                    ->where('section', $subject->section);
            })
            ->orWhereIn('room_id', $rooms->pluck('id'))
            ->get();

        foreach ($pendingItems as $item) {
            if (($item['subject_id'] ?? null) === $subject->id) {
                continue;
            }

            $existingSchedules->push(new Schedule([
                'subject_id' => $item['subject_id'] ?? null,
                'room_id' => $item['room_id'] ?? null,
                'department' => $filters['department'] ?? null,
                'major' => $filters['major'] ?? null,
                'year_level' => $filters['year_level'] ?? null,
                'section' => $filters['section'] ?? null,
                'day' => $item['day'] ?? null,
                'start_time' => $item['raw_start_time'] ?? null,
                'end_time' => $item['raw_end_time'] ?? null,
                'pairing_key' => $item['pairing_key'] ?? null,
                'status' => Schedule::STATUS_PARTIAL,
            ]));
        }

        $result = [
            'scheduled' => 0,
            'failed' => 0,
            'warnings' => 0,
            'failure_reasons' => [],
            'failed_items' => [],
            'fallback_warnings' => [],
            'scheduled_items' => [],
            'filters' => [
                'department' => strtoupper((string) ($filters['department'] ?? $subject->department)),
                'major' => strtoupper((string) ($filters['major'] ?? $subject->major)),
                'year_level' => (int) ($filters['year_level'] ?? $subject->year_level),
                'section' => strtoupper((string) ($filters['section'] ?? $subject->section)),
            ],
        ];

        $preferredStart = $this->normalizePreferredStart($overrides['preferred_start_time'] ?? null);
        $linkedPattern = $preferredStart
            ? $this->findPatternAtPreferredStart($subject, $rooms, $existingSchedules, Setting::getDayBounds(), (int) $subject->meetings_per_week, $preferredStart)
            : null;

        $linkedPattern ??= $this->generateLinkedMeetingPattern($subject, $rooms, $existingSchedules, Setting::getDayBounds(), (int) $subject->meetings_per_week);

        if (!$linkedPattern) {
            $reason = $this->compatibleRooms($rooms, $subject)->isEmpty()
                ? 'No compatible room available'
                : 'No linked room/time group';

            $this->recordFailure($result, $subject, $reason);
            $result['failed'] = count($result['failed_items']);

            return $result;
        }

        foreach ($linkedPattern['placements'] as $placement) {
            $pairingKey = $linkedPattern['pairing_key'];
            $dayPair = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

            $schedule = new Schedule([
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'department' => $subject->department,
                'major' => $subject->major,
                'year_level' => $subject->year_level,
                'section' => $subject->section,
                'day' => $placement['day'],
                'start_time' => $placement['start'],
                'end_time' => $placement['end'],
                'pairing_key' => $pairingKey,
                'status' => Schedule::STATUS_PARTIAL,
            ]);

            $schedule->setRelation('subject', $subject);
            $existingSchedules->push($schedule);
            $result['scheduled']++;
            $result['scheduled_items'][] = [
                'subject_code' => $subject->subject_code,
                'subject_name' => $subject->description,
                'edp_code' => $subject->edp_code,
                'room' => $placement['room']->room_name,
                'day_pair' => $dayPair,
                'day' => $placement['day'],
                'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                'instructor' => 'Unassigned',
                'raw_start_time' => $placement['start'],
                'raw_end_time' => $placement['end'],
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'duration_hours' => round(Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60, 2),
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
            ];

            if ($placement['fallback']) {
                $result['warnings']++;
                $result['fallback_warnings'][] = "{$subject->subject_code} used fallback room {$placement['room']->room_name}.";
            }
        }

        $result['failed'] = count($result['failed_items']);

        return $result;
    }

    public function generateLinkedMeetingPattern(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        ?array $bounds = null,
        ?int $meetingsNeeded = null
    ): ?array {
        return $this->findConsistentRoomAndTime(
            $subject,
            $rooms,
            $existingSchedules,
            $bounds ?? Setting::getDayBounds(),
            $meetingsNeeded
        );
    }

    public function findConsistentRoomAndTime(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        ?int $meetingsNeeded = null,
        int $meetingIndex = 0
    ): ?array {
        $meetingsNeeded = max(1, min(count(self::DAYS), $meetingsNeeded ?? (int) $subject->meetings_per_week));

        $anchoredPattern = $this->findAnchoredLinkedPattern($subject, $rooms, $existingSchedules, $meetingsNeeded);
        if ($anchoredPattern) {
            return $anchoredPattern;
        }

        $minutes = $this->minutesPerMeeting($subject);
        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules, $meetingIndex);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        foreach ($compatibleRooms as $candidate) {
            $room = $candidate['room'];

            foreach ($this->meetingDayPatterns($meetingsNeeded) as $days) {
                foreach ($this->sectionWindows($subject->section, $bounds) as $window) {
                    if ($window['start']->copy()->addMinutes($minutes)->gt($window['end'])) {
                        continue;
                    }

                    $period = CarbonPeriod::create(
                        $window['start'],
                        self::SLOT_MINUTES . ' minutes',
                        $window['end']->copy()->subMinutes($minutes)
                    );

                    foreach ($period as $slotStart) {
                        $slotEnd = $slotStart->copy()->addMinutes($minutes);
                        $start = $slotStart->format('H:i:s');
                        $end = $slotEnd->format('H:i:s');

                        if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                            continue;
                        }

                        if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end)) {
                            continue;
                        }

                        $placements = $this->buildPlacementsForDays(
                            $existingSchedules,
                            $subject,
                            $room,
                            $days,
                            $start,
                            $end,
                            $candidate['score'],
                            $candidate['fallback']
                        );

                        if ($placements) {
                            return [
                                'pairing_key' => $this->makePairingKey($subject),
                                'placements' => $placements,
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    private function findPlacement(Subject $subject, Collection $rooms, Collection $existingSchedules, array $bounds, int $meetingIndex): ?array
    {
        $pattern = $this->findConsistentRoomAndTime($subject, $rooms, $existingSchedules, $bounds, 1, $meetingIndex);

        return $pattern['placements'][0] ?? null;
    }

    private function findAnchoredLinkedPattern(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingsNeeded): ?array
    {
        $existingForSubject = $existingSchedules
            ->filter(fn (Schedule $schedule) => (int) $schedule->subject_id === (int) $subject->id)
            ->filter(fn (Schedule $schedule) => filled($schedule->room_id) && filled($schedule->day) && filled($schedule->start_time) && filled($schedule->end_time))
            ->values();

        if ($existingForSubject->isEmpty()) {
            return null;
        }

        $anchor = $existingForSubject->first();
        $room = $rooms->first(fn (Room $room) => (int) $room->id === (int) $anchor->room_id);

        if (!$room || !$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
            return null;
        }

        $usedDays = $existingForSubject
            ->pluck('day')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $totalMeetings = max(count($usedDays) + $meetingsNeeded, (int) $subject->meetings_per_week);
        $start = Carbon::parse($anchor->start_time)->format('H:i:s');
        $end = Carbon::parse($anchor->end_time)->format('H:i:s');
        $score = $this->compatibilityScore($room, $subject);

        foreach ($this->meetingDayPatterns($totalMeetings, $usedDays) as $pattern) {
            if (array_diff($usedDays, $pattern)) {
                continue;
            }

            $days = array_values(array_diff($pattern, $usedDays));

            if (count($days) < $meetingsNeeded) {
                continue;
            }

            $placements = $this->buildPlacementsForDays(
                $existingSchedules,
                $subject,
                $room,
                array_slice($days, 0, $meetingsNeeded),
                $start,
                $end,
                $score,
                false
            );

            if ($placements) {
                return [
                    'pairing_key' => $anchor->pairing_key ?: $this->makePairingKey($subject),
                    'placements' => $placements,
                ];
            }
        }

        return null;
    }

    private function buildPlacementsForDays(
        Collection $existingSchedules,
        Subject $subject,
        Room $room,
        array $days,
        string $start,
        string $end,
        int $score,
        bool $fallback
    ): ?array {
        if (count($days) !== count(array_unique($days))) {
            return null;
        }

        $placements = [];

        foreach ($days as $day) {
            if ($day === 'Sunday') {
                continue;
            }

            if (!$this->roomAvailable($existingSchedules, $room->id, $day, $start, $end)) {
                return null;
            }

            if (!$this->sectionAvailable($existingSchedules, $subject, $day, $start, $end)) {
                return null;
            }

            $placements[] = [
                'room' => $room,
                'day' => $day,
                'start' => $start,
                'end' => $end,
                'score' => $score,
                'fallback' => $fallback,
            ];
        }

        if (count($placements) !== count($days)) {
            return null;
        }

        return $placements;
    }

    private function minutesPerMeeting(Subject $subject): int
    {
        return max(
            self::SLOT_MINUTES,
            (int) ceil(((float) $subject->duration_hours * 60) / max(1, (int) $subject->meetings_per_week))
        );
    }

    private function meetingDayPatterns(int $meetings, array $mustIncludeDays = []): array
    {
        $meetings = max(1, min(count(self::DAYS), $meetings));
        $patterns = array_merge(
            self::DAY_PAIRINGS[$meetings] ?? [],
            $this->fallbackDayPatterns($meetings),
            $this->dayCombinations($meetings)
        );

        $unique = [];
        foreach ($patterns as $pattern) {
            $pattern = array_values(array_intersect(self::DAYS, $pattern));

            if (count($pattern) !== $meetings) {
                continue;
            }

            $unique[implode('|', $pattern)] = $pattern;
        }

        $patterns = array_values($unique);

        if ($mustIncludeDays) {
            usort($patterns, function (array $a, array $b) use ($mustIncludeDays) {
                $aContainsAll = empty(array_diff($mustIncludeDays, $a));
                $bContainsAll = empty(array_diff($mustIncludeDays, $b));

                if ($aContainsAll !== $bContainsAll) {
                    return $aContainsAll ? -1 : 1;
                }

                $aMatches = count(array_intersect($mustIncludeDays, $a));
                $bMatches = count(array_intersect($mustIncludeDays, $b));

                return $bMatches <=> $aMatches;
            });
        }

        return $patterns;
    }

    private function fallbackDayPatterns(int $meetings): array
    {
        return match ($meetings) {
            4 => [
                ['Monday', 'Tuesday', 'Thursday', 'Friday'],
                ['Monday', 'Wednesday', 'Thursday', 'Saturday'],
                ['Tuesday', 'Wednesday', 'Friday', 'Saturday'],
            ],
            5 => [
                ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                ['Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            ],
            6 => [self::DAYS],
            default => [],
        };
    }

    private function dayCombinations(int $meetings): array
    {
        $result = [];
        $days = self::DAYS;

        $build = function (int $start, array $current) use (&$build, &$result, $days, $meetings) {
            if (count($current) === $meetings) {
                $result[] = $current;
                return;
            }

            $needed = $meetings - count($current);
            $limit = count($days) - $needed;

            for ($index = $start; $index <= $limit; $index++) {
                $build($index + 1, array_merge($current, [$days[$index]]));
            }
        };

        $build(0, []);

        return $result;
    }

    private function makePairingKey(Subject $subject): string
    {
        return implode('-', [
            'pair',
            $subject->id,
            Str::orderedUuid()->toString(),
        ]);
    }

    private function compatibleRooms(Collection $rooms, Subject $subject, ?Collection $existingSchedules = null, int $meetingIndex = 0): Collection
    {
        $strictRooms = $rooms
            ->filter(fn (Room $room) => $this->isRoomCompatible($room, $subject, allowMinorLabFallback: false))
            ->map(fn (Room $room) => [
                'room' => $room,
                'score' => $this->compatibilityScore($room, $subject),
                'fallback' => false,
                'load' => $this->roomLoad($existingSchedules, $room->id),
            ])
            ->filter(fn (array $candidate) => $candidate['score'] > 0);

        $candidateRooms = $strictRooms;

        if ($candidateRooms->isEmpty() && strtoupper((string) $subject->type) === 'MINOR') {
            $candidateRooms = $rooms
                ->filter(fn (Room $room) => $this->isRoomCompatible($room, $subject, allowMinorLabFallback: true))
                ->map(fn (Room $room) => [
                    'room' => $room,
                    'score' => max(1, $this->compatibilityScore($room, $subject) - 100),
                    'fallback' => true,
                    'load' => $this->roomLoad($existingSchedules, $room->id),
                ]);
        }

        $sorted = $candidateRooms
            ->sort(function (array $a, array $b) {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                if ($a['load'] !== $b['load']) {
                    return $a['load'] <=> $b['load'];
                }

                return strcmp($a['room']->room_name, $b['room']->room_name);
            })
            ->values();

        if ($sorted->count() <= 1) {
            return $sorted;
        }

        $offset = $meetingIndex % $sorted->count();

        return $sorted->slice($offset)->concat($sorted->slice(0, $offset))->values();
    }

    private function prioritizedDays(Subject $subject, Collection $existingSchedules, int $meetingIndex): array
    {
        $usedDays = $existingSchedules
            ->where('subject_id', $subject->id)
            ->pluck('day')
            ->all();

        $unused = array_values(array_diff(self::DAYS, $usedDays));
        $ordered = array_merge($unused, array_values(array_intersect(self::DAYS, $usedDays)));
        $offset = $meetingIndex % max(1, count($ordered));

        return array_merge(array_slice($ordered, $offset), array_slice($ordered, 0, $offset));
    }

    private function sectionWindows(?string $section, array $bounds): array
    {
        $dayStart = Carbon::parse($bounds['start']);
        $dayEnd = Carbon::parse($bounds['end']);
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

    private function roomAvailable(Collection $schedules, int $roomId, string $day, string $start, string $end): bool
    {
        return !$schedules->contains(fn (Schedule $schedule) => (int) $schedule->room_id === $roomId
            && $schedule->day === $day
            && $this->conflicts->hasTimeOverlap($start, $end, $schedule->start_time, $schedule->end_time));
    }

    private function sectionAvailable(Collection $schedules, Subject $subject, string $day, string $start, string $end): bool
    {
        return !$schedules->contains(function (Schedule $schedule) use ($subject, $day, $start, $end) {
            $sameGroup = strtoupper((string) ($schedule->department ?? $schedule->subject?->department)) === strtoupper((string) $subject->department)
                && strtoupper((string) ($schedule->major ?? $schedule->subject?->major)) === strtoupper((string) $subject->major)
                && (int) ($schedule->year_level ?? $schedule->subject?->year_level) === (int) $subject->year_level
                && strtoupper((string) ($schedule->section ?? $schedule->subject?->section)) === strtoupper((string) $subject->section);

            return $sameGroup
                && $schedule->day === $day
                && $this->conflicts->hasTimeOverlap($start, $end, $schedule->start_time, $schedule->end_time);
        });
    }

    private function roomTypeMatches(Room $room, Subject $subject): bool
    {
        return $this->subjectRequiresLab($subject) === $this->roomIsLab($room);
    }

    private function subjectRequiresLab(Subject $subject): bool
    {
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

    private function subjectSpecialization(Subject $subject): string
    {
        return $this->subjectSpecializations($subject)[0] ?? '';
    }

    private function roomSpecializations(Room $room): array
    {
        $specialization = strtoupper((string) $room->specialization);

        return collect(preg_split('/[,|\/;]+/', $specialization) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function subjectSpecializations(Subject $subject): array
    {
        $values = array_merge(
            $this->splitSpecialization((string) ($subject->specialization ?? '')),
            [
                strtoupper(trim((string) $subject->major)),
                strtoupper(trim((string) strtok((string) $subject->edp_code, '-'))),
                strtoupper(trim((string) $subject->department)),
            ]
        );

        return collect($values)
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function splitSpecialization(string $specialization): array
    {
        return collect(preg_split('/[,|\/;]+/', strtoupper($specialization)) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function hasExactSpecializationMatch(array $subjectSpecializations, array $roomSpecializations): bool
    {
        return !empty(array_intersect($subjectSpecializations, $roomSpecializations));
    }

    private function hasCompatibleSpecializationMatch(array $subjectSpecializations, array $roomSpecializations): bool
    {
        foreach ($subjectSpecializations as $subjectSpecialization) {
            $compatible = self::SPECIALIZATION_GROUPS[$subjectSpecialization] ?? [$subjectSpecialization];

            if (array_intersect($compatible, $roomSpecializations)) {
                return true;
            }
        }

        return false;
    }

    private function isGeneralRoom(Room $room): bool
    {
        $specializations = $this->roomSpecializations($room);

        if (!$specializations) {
            return true;
        }

        return (bool) collect($specializations)->first(fn (string $value) => in_array($value, self::GENERAL_ROOM_SPECIALIZATIONS, true));
    }

    private function roomIsLab(Room $room): bool
    {
        $haystack = strtoupper(trim("{$room->type} {$room->room_name} {$room->specialization}"));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COM LAB')
            || str_contains($haystack, 'COMPUTER');
    }

    private function isAcceptableGeneralLab(Room $room, array $subjectSpecializations): bool
    {
        if (!$this->roomIsLab($room) || $this->roomConflictsWithSubjectDomain($room, $subjectSpecializations)) {
            return false;
        }

        $roomName = strtoupper((string) $room->room_name);

        if (array_intersect($subjectSpecializations, ['IT', 'ACT', 'CCS'])) {
            return str_contains($roomName, 'COM')
                || str_contains($roomName, 'COMPUTER')
                || str_contains($roomName, 'LAB');
        }

        return $this->isGeneralRoom($room);
    }

    private function roomConflictsWithSubjectDomain(Room $room, array $subjectSpecializations): bool
    {
        $roomText = strtoupper(trim("{$room->room_name} {$room->type} {$room->specialization}"));

        $domainConflicts = [
            'technology' => ['KITCHEN', 'HOSPITALITY', 'HM', 'SHTM', 'BALLISTICS', 'FORENSIC', 'FORENSICS', 'QD', 'LD', 'FB'],
            'hospitality' => ['COMPUTER', 'COM LAB', 'IT', 'ACT', 'BALLISTICS', 'FORENSIC', 'FORENSICS', 'QD', 'LD', 'FB'],
            'criminology' => ['KITCHEN', 'HOSPITALITY', 'HM', 'SHTM', 'COMPUTER', 'COM LAB', 'IT', 'ACT'],
        ];

        if (array_intersect($subjectSpecializations, ['IT', 'ACT', 'CCS'])) {
            return $this->containsAny($roomText, $domainConflicts['technology']);
        }

        if (array_intersect($subjectSpecializations, ['HM', 'TM', 'SHTM'])) {
            return $this->containsAny($roomText, $domainConflicts['hospitality']);
        }

        if (array_intersect($subjectSpecializations, ['FB', 'LD', 'QD', 'COC'])) {
            return $this->containsAny($roomText, $domainConflicts['criminology']);
        }

        return false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (strlen($needle) <= 3 && preg_match('/(^|[^A-Z0-9])' . preg_quote($needle, '/') . '([^A-Z0-9]|$)/', $haystack)) {
                return true;
            }

            if (strlen($needle) <= 3) {
                continue;
            }

            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function roomLoad(?Collection $schedules, int $roomId): int
    {
        if (!$schedules) {
            return 0;
        }

        return $schedules->where('room_id', $roomId)->count();
    }

    private function capacityFits(Room $room, Subject $subject): bool
    {
        $expectedSize = $subject->student_count ?? $subject->enrollment ?? $subject->class_size ?? null;

        return !$expectedSize || !$room->capacity || (int) $room->capacity >= (int) $expectedSize;
    }

    private function roomRegistryRowIsValid(Room $room): bool
    {
        return trim((string) $room->room_name) !== ''
            && trim((string) $room->type) !== ''
            && trim((string) $room->specialization) !== ''
            && is_numeric($room->capacity)
            && (int) $room->capacity > 0;
    }

    private function groupKey(Subject $subject): string
    {
        return implode('|', [
            strtoupper((string) $subject->department),
            strtoupper((string) $subject->major),
            (int) $subject->year_level,
            strtoupper((string) $subject->section),
        ]);
    }

    private function subjectDifficultyScore(Subject $subject): int
    {
        $score = 0;

        if ($this->subjectRequiresLab($subject)) {
            $score += 1000;
        }

        if (strtoupper((string) $subject->type) === 'MAJOR') {
            $score += 350;
        }

        $score += (int) ($subject->units ?? 0) * 40;
        $score += (int) ($subject->meetings_per_week ?? 1) * 25;
        $score += (int) round(((float) ($subject->duration_hours ?? 0)) * 20);

        $expectedSize = $subject->student_count ?? $subject->enrollment ?? $subject->class_size ?? null;
        if ($expectedSize) {
            $score += min(250, (int) $expectedSize);
        }

        return $score;
    }

    private function normalizePreferredStart(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function findPatternAtPreferredStart(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        int $meetingsNeeded,
        string $preferredStart
    ): ?array {
        $minutes = $this->minutesPerMeeting($subject);
        $slotStart = Carbon::parse($preferredStart);
        $slotEnd = $slotStart->copy()->addMinutes($minutes);
        $start = $slotStart->format('H:i:s');
        $end = $slotEnd->format('H:i:s');
        $boundsStart = Carbon::parse($bounds['start']);
        $boundsEnd = Carbon::parse($bounds['end']);

        if ($slotStart->lt($boundsStart) || $slotEnd->gt($boundsEnd)) {
            return null;
        }

        if ($this->conflicts->overlapsLunchBreak($start, $end)
            || !$this->conflicts->respectsSectionSession($subject->section, $start, $end)) {
            return null;
        }

        foreach ($this->compatibleRooms($rooms, $subject, $existingSchedules) as $candidate) {
            foreach ($this->meetingDayPatterns($meetingsNeeded) as $days) {
                $placements = $this->buildPlacementsForDays(
                    $existingSchedules,
                    $subject,
                    $candidate['room'],
                    $days,
                    $start,
                    $end,
                    $candidate['score'],
                    $candidate['fallback']
                );

                if ($placements) {
                    return [
                        'pairing_key' => $this->makePairingKey($subject),
                        'placements' => $placements,
                    ];
                }
            }
        }

        return null;
    }

    private function recordFailure(array &$result, Subject|string $subject, string $reason): void
    {
        $subjectCode = $subject instanceof Subject ? $subject->subject_code : $subject;
        $result['failure_reasons'][] = "{$subjectCode}: {$reason}";

        if ($subject instanceof Subject) {
            $result['failed_items'][] = [
                'subject_id' => $subject->id,
                'subject_code' => $subject->subject_code,
                'subject_name' => $subject->description,
                'edp_code' => $subject->edp_code,
                'duration_hours' => $subject->duration_hours,
                'meetings_per_week' => $subject->meetings_per_week,
                'preferred_room_type' => '',
                'preferred_start_time' => '',
                'reason' => $reason,
            ];
        }
    }

    private function emptyResult(string $reason): array
    {
        return [
            'scheduled' => 0,
            'failed' => 1,
            'warnings' => 0,
            'failure_reasons' => [$reason],
            'failed_items' => [],
            'fallback_warnings' => [],
            'scheduled_items' => [],
        ];
    }
}
