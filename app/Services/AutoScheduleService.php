<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AutoScheduleService
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    private const SLOT_MINUTES = 30;
    private const LUNCH_START = '12:00:00';
    private const LUNCH_END = '13:00:00';

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
            ->get();

        if ($rooms->isEmpty()) {
            return $this->emptyResult('no compatible room');
        }

        $existingSchedules = Schedule::query()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'status')
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
            ->groupBy(fn (Subject $subject) => $this->groupKey($subject));

        $result = [
            'scheduled' => 0,
            'failed' => 0,
            'warnings' => 0,
            'failure_reasons' => [],
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

                for ($meetingIndex = 0; $meetingIndex < $remainingMeetings; $meetingIndex++) {
                    $placement = $this->findPlacement($subject, $rooms, $existingSchedules, $bounds, $meetingIndex);

                    if (!$placement) {
                        $reason = $this->compatibleRooms($rooms, $subject)->isEmpty()
                            ? 'no compatible room'
                            : 'no valid time slot';

                        $this->recordFailure($result, $subject->subject_code, $reason);
                        break;
                    }

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
                        'edp_code' => $subject->edp_code,
                        'room' => $placement['room']->room_name,
                        'day' => $placement['day'],
                        'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                        'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                        'raw_start_time' => $placement['start'],
                        'raw_end_time' => $placement['end'],
                        'subject_id' => $subject->id,
                        'room_id' => $placement['room']->id,
                        'duration_hours' => $scheduleData['duration_hours'],
                        'meetings_per_week' => $subject->meetings_per_week,
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
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'status')
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

        foreach ($items as $item) {
            $subject = $subjects->get($item['subject_id'] ?? null);
            $room = $rooms->get($item['room_id'] ?? null);
            $day = (string) ($item['day'] ?? '');
            $start = (string) ($item['raw_start_time'] ?? '');
            $end = (string) ($item['raw_end_time'] ?? '');

            if (!$subject || !$room || !$day || !$start || !$end) {
                $result['failure_reasons'][] = ($item['subject_code'] ?? 'Unknown subject') . ': invalid generated schedule data';
                continue;
            }

            if (!$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
                $result['failure_reasons'][] = "{$subject->subject_code}: room is no longer compatible";
                continue;
            }

            if ($this->hasRoomConflict($existingSchedules, $room->id, $day, $start, $end)) {
                $result['failure_reasons'][] = "{$subject->subject_code}: room conflict detected before save";
                continue;
            }

            if ($this->hasSectionConflict($existingSchedules, $subject, $day, $start, $end)) {
                $result['failure_reasons'][] = "{$subject->subject_code}: section conflict detected before save";
                continue;
            }

            $schedule = Schedule::create([
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
                'status' => Schedule::STATUS_PARTIAL,
            ]);

            $schedule->setRelation('subject', $subject);
            $existingSchedules->push($schedule);

            $result['saved']++;
            $result['saved_items'][] = $item;
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
        $subjectMajor = strtoupper((string) $subject->major);
        $subjectDepartment = strtoupper((string) $subject->department);
        $subjectType = strtoupper((string) $subject->type);
        $roomType = strtoupper((string) $room->type);
        $specializations = $this->roomSpecializations($room);
        $roomName = strtoupper((string) $room->room_name);
        $exactSpecialization = in_array($subjectMajor, $specializations, true);

        if ($exactSpecialization) {
            $score += 80;
        }

        if ($this->roomTypeMatches($room, $subject)) {
            $score += 30;
        }

        if ($subjectDepartment && (in_array($subjectDepartment, $specializations, true) || str_contains($roomName, $subjectDepartment))) {
            $score += 20;
        }

        if ($this->capacityFits($room, $subject)) {
            $score += 10;
        }

        if ($subjectType === 'MINOR') {
            if ($this->isGeneralRoom($room) && !str_contains($roomType, 'LAB')) {
                $score += 70;
            } elseif (!str_contains($roomType, 'LAB')) {
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
            ->get();

        return $this->compatibleRooms($rooms, $subject);
    }

    public function isRoomCompatible(Room $room, Subject $subject, bool $allowMinorLabFallback = false): bool
    {
        if (!$this->capacityFits($room, $subject)) {
            return false;
        }

        $subjectType = strtoupper((string) $subject->type);
        $subjectMajor = $this->subjectSpecialization($subject);
        $roomType = strtoupper((string) $room->type);
        $isLab = str_contains($roomType, 'LAB') || str_contains($roomType, 'LABORATORY');
        $requiresLab = $this->subjectRequiresLab($subject);
        $specializations = $this->roomSpecializations($room);

        if ($requiresLab && !$isLab) {
            return false;
        }

        if ($subjectType === 'MAJOR') {
            return $subjectMajor !== '' && in_array($subjectMajor, $specializations, true);
        }

        if ($requiresLab) {
            return $isLab && ($this->isGeneralRoom($room) || in_array($subjectMajor, $specializations, true));
        }

        if (!$allowMinorLabFallback && $isLab && !$this->isGeneralRoom($room)) {
            return false;
        }

        return $this->isGeneralRoom($room) || !$isLab || $allowMinorLabFallback;
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
        return $this->findPlacement($subject, $rooms, $existingSchedules, Setting::getDayBounds(), $meetingIndex);
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
            ->select('id', 'edp_code', 'subject_code', 'description', 'section', 'major', 'year_level', 'department', 'units', 'duration_hours', 'type', 'meetings_per_week')
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
            ->orderBy('subject_code');
    }

    private function findPlacement(Subject $subject, Collection $rooms, Collection $existingSchedules, array $bounds, int $meetingIndex): ?array
    {
        $minutes = max(self::SLOT_MINUTES, (int) ceil(((float) $subject->duration_hours * 60) / max(1, (int) $subject->meetings_per_week)));
        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules, $meetingIndex);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        foreach ($this->prioritizedDays($subject, $existingSchedules, $meetingIndex) as $day) {
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

                    foreach ($compatibleRooms as $candidate) {
                        $room = $candidate['room'];

                        if (!$this->roomAvailable($existingSchedules, $room->id, $day, $start, $end)) {
                            continue;
                        }

                        if (!$this->sectionAvailable($existingSchedules, $subject, $day, $start, $end)) {
                            continue;
                        }

                        return [
                            'room' => $room,
                            'day' => $day,
                            'start' => $start,
                            'end' => $end,
                            'score' => $candidate['score'],
                            'fallback' => $candidate['fallback'],
                        ];
                    }
                }
            }
        }

        return null;
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
        $roomType = strtoupper((string) $room->type);
        $roomType = $roomType === 'LABORATORY' ? 'LAB' : $roomType;
        $subjectText = strtoupper(trim("{$subject->type} {$subject->subject_code} {$subject->description}"));
        $requiresLab = str_contains($subjectText, 'LAB') || str_contains($subjectText, 'LABORATORY');

        if ($requiresLab) {
            return str_contains($roomType, 'LAB');
        }

        return !str_contains($roomType, 'LAB');
    }

    private function subjectRequiresLab(Subject $subject): bool
    {
        $haystack = strtoupper(trim("{$subject->subject_code} {$subject->description}"));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COMPUTER');
    }

    private function subjectSpecialization(Subject $subject): string
    {
        $major = strtoupper(trim((string) $subject->major));

        if ($major !== '') {
            return $major;
        }

        $edpPrefix = strtoupper(strtok((string) $subject->edp_code, '-'));

        return $edpPrefix ?: strtoupper((string) $subject->department);
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

    private function isGeneralRoom(Room $room): bool
    {
        $specializations = $this->roomSpecializations($room);

        if (!$specializations) {
            return true;
        }

        return (bool) collect($specializations)->first(fn (string $value) => in_array($value, [
            'GENERAL',
            'GEN',
            'LECTURE',
            'CLASSROOM',
            'MINOR',
            'ALL',
        ], true));
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

    private function groupKey(Subject $subject): string
    {
        return implode('|', [
            strtoupper((string) $subject->department),
            strtoupper((string) $subject->major),
            (int) $subject->year_level,
            strtoupper((string) $subject->section),
        ]);
    }

    private function recordFailure(array &$result, string $subjectCode, string $reason): void
    {
        $result['failure_reasons'][] = "{$subjectCode}: {$reason}";
    }

    private function emptyResult(string $reason): array
    {
        return [
            'scheduled' => 0,
            'failed' => 1,
            'warnings' => 0,
            'failure_reasons' => [$reason],
            'fallback_warnings' => [],
        ];
    }
}
