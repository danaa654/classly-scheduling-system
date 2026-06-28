<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Faculty;
use App\Models\Department;
use App\Services\RoomCapacityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutoScheduleService
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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
            ['Tuesday', 'Friday'],
            ['Thursday', 'Saturday'],
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

    private function activeDays(): array
    {
        return Setting::getActiveDays();
    }

    private function activeDayCount(): int
    {
        return max(1, count($this->activeDays()));
    }

    private function activeScheduleQuery()
    {
        $period = Setting::getAcademicPeriod();

        return Schedule::activeTerm($period['semester'], $period['school_year']);
    }

    private function activeSubjectQuery()
    {
        $period = Setting::getAcademicPeriod();

        return Subject::activeTerm($period['semester'], $period['school_year']);
    }

    // ================================================================================
    // MAIN ENTRY POINT: generatePartialSchedules
    // ================================================================================
    public function generatePartialSchedules(array $filters = [], ?int $userId = null, bool $persist = false): array
    {
        set_time_limit(300);

        $missingFilters = $this->missingRequiredFilters($filters);
        if ($missingFilters) {
            return $this->emptyResult('select ' . implode(', ', $missingFilters) . ' before generating schedules');
        }

        $bounds = Setting::getDayBounds();
        $period = Setting::getAcademicPeriod();
        $rooms = Room::query()
            ->select($this->roomSelectColumns())
            ->available()
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        if ($rooms->isEmpty()) {
            return $this->emptyResult('no valid room registry rows available');
        }

        $roomIds = $rooms->pluck('id');

        $existingSchedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section,faculty_id')
            ->where(function ($query) use ($filters, $roomIds) {
                $query->where(function ($query) use ($filters) {
                    $query->where('department', strtoupper($filters['department']))
                        ->where('major', strtoupper($filters['major']))
                        ->where('year_level', (int) $filters['year_level'])
                        ->where('section', strtoupper($filters['section']));
                })->orWhereIn('room_id', $roomIds);
            })
            ->get();

        $subjects = $this->subjectQuery($filters)
            ->with('faculty:id,full_name,availability')
            ->withCount(['schedules as active_schedules_count' => fn ($query) => $query
                ->activeTerm()
                ->where(function ($q) {
                    // Only count COMPLETE schedules (exclude incomplete placeholders)
                    $q->whereNotNull('day')
                        ->whereNotNull('start_time')
                        ->whereNotNull('end_time')
                        ->whereNotNull('room_id');
                })
            ])
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
            // IMPROVED: Report subjects that were skipped because all required meetings are already scheduled.
            'already_complete' => [],
            'filters' => [
                'department' => strtoupper($filters['department']),
                'major' => strtoupper($filters['major']),
                'year_level' => (int) $filters['year_level'],
                'section' => strtoupper($filters['section']),
            ],
        ];

        $preAssignedSubjects = collect();
        $unassignedSubjects = collect();

        foreach ($subjects as $groupSubjects) {
            foreach ($groupSubjects as $subject) {
                $remainingMeetings = max(0, (int) $subject->meetings_per_week - (int) $subject->active_schedules_count);

                if ($remainingMeetings <= 0) {
                    // IMPROVED: Surface complete subjects instead of silently skipping them.
                    if ((int) $subject->active_schedules_count >= (int) $subject->meetings_per_week) {
                        $result['already_complete'][] = [
                            'subject_code' => $this->cleanText($subject->subject_code),
                            'edp_code' => $this->cleanText($subject->edp_code),
                        ];
                    }
                    continue;
                }

                $placeholder = $this->findPreAssignmentPlaceholder($subject);
                
                if ($placeholder && !$this->placeholderIsFullyScheduled($placeholder)) {
                    $preAssignedSubjects->push([
                        'subject' => $subject,
                        'placeholder' => $placeholder,
                        'remainingMeetings' => $remainingMeetings,
                        'groupSubjects' => $groupSubjects,
                    ]);
                } else {
                    $unassignedSubjects->push([
                        'subject' => $subject,
                        'remainingMeetings' => $remainingMeetings,
                        'groupSubjects' => $groupSubjects,
                    ]);
                }
            }
        }

        // WAVE 1: Process pre-assigned subjects with HIGHEST PRIORITY
        foreach ($preAssignedSubjects as $item) {
            $this->processPreAssignedSubject(
                $item['subject'],
                $item['placeholder'],
                $item['remainingMeetings'],
                $rooms,
                $existingSchedules,
                $bounds,
                $period,
                $userId,
                $persist,
                $result
            );
        }

        // WAVE 2: Process remaining unassigned subjects
        foreach ($unassignedSubjects as $item) {
            $this->processUnassignedSubject(
                $item['subject'],
                $item['remainingMeetings'],
                $rooms,
                $existingSchedules,
                $bounds,
                $period,
                $userId,
                $persist,
                $result
            );
        }

        $result['failed'] = count($result['failure_reasons']);
        return $result;
    }

    // ================================================================================
    // PERSISTENCE LAYER: persistGeneratedSchedules
    // ================================================================================
    public function persistGeneratedSchedules(array $items, ?int $userId = null): array
    {
        set_time_limit(300);
        $period = Setting::getAcademicPeriod();

        $subjectIds = collect($items)->pluck('subject_id')->filter()->unique()->values();
        $roomIds = collect($items)->pluck('room_id')->filter()->unique()->values();

        $subjects = $this->activeSubjectQuery()
            ->with('faculty:id,full_name,availability')
            ->whereIn('id', $subjectIds)
            ->get()
            ->keyBy('id');

        $rooms = Room::query()
            ->whereIn('id', $roomIds)
            ->get()
            ->keyBy('id');

        $existingSchedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section,faculty_id')
            ->where(function ($query) use ($roomIds, $subjectIds) {
                $query->whereIn('room_id', $roomIds)
                    ->orWhereIn('subject_id', $subjectIds);
            })
            ->get();

        $scheduleSettings = Setting::getScheduleSettings();
        $activeDays = $scheduleSettings['active_days'];
        $boundsStart = Carbon::parse($scheduleSettings['start_time']);
        $boundsEnd = Carbon::parse($scheduleSettings['end_time']);

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
                $facultyId = array_key_exists('faculty_id', $item)
                    ? (filled($item['faculty_id']) ? (int) $item['faculty_id'] : null)
                    : $subject?->faculty_id;

                if (!$subject || !$room || !$day || !$start || !$end) {
                    $result['failure_reasons'][] = ($item['subject_code'] ?? 'Unknown subject') . ': invalid generated schedule data';
                    $groupFailed = true;
                    break;
                }

                // Hard guard: practicum/OJT subjects must never receive a room assignment.
                if ((bool) ($subject->is_practicum ?? false)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: Practicum/OJT subjects cannot be assigned to a room or time slot";
                    $groupFailed = true;
                    break;
                }

                if (!in_array($day, $activeDays, true)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: {$day} is not enabled for scheduling";
                    $groupFailed = true;
                    break;
                }

                $start = Carbon::parse($start)->format('H:i:s');
                $end = Carbon::parse($end)->format('H:i:s');
                $slotStart = Carbon::parse($start);
                $slotEnd = Carbon::parse($end);
                $validationSchedules = $existingSchedules->concat($validatedSchedules);

                if ($slotStart->lt($boundsStart) || $slotEnd->gt($boundsEnd)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: generated slot is outside the configured schedule hours";
                    $groupFailed = true;
                    break;
                }

                if (!$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: room is no longer compatible";
                    $groupFailed = true;
                    break;
                }

                // ── WEEKLY CAPACITY GUARD ───────────────────────────────────
                // Prevent saving a schedule that would push a room over its
                // maximum weekly available hours.
                if (!RoomCapacityService::canScheduleSubjectInRoom($room, $subject, $existingSchedules->concat($validatedSchedules))) {
                    $maxCap    = RoomCapacityService::getWeeklyCapacity();
                    $curLoad   = RoomCapacityService::getCurrentScheduledRoomLoad($room, $existingSchedules->concat($validatedSchedules));
                    $remaining = max(0, $maxCap - $curLoad);
                    $result['failure_reasons'][] = sprintf(
                        '%s: room %s is at capacity (%sh/wk max, %sh used, %sh remaining, subject needs %sh)',
                        $subject->subject_code,
                        $room->room_name,
                        RoomCapacityService::formatHours($maxCap),
                        RoomCapacityService::formatHours($curLoad),
                        RoomCapacityService::formatHours($remaining),
                        RoomCapacityService::formatHours((float) $subject->duration_hours)
                    );
                    $groupFailed = true;
                    break;
                }

                if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: generated slot overlaps lunch break";
                    $groupFailed = true;
                    break;
                }

                $allowSessionFallback = (bool) ($item['session_fallback'] ?? $item['fallback_session'] ?? false)
                    || $this->isSectionFallbackSlot($subject->section, $start, $end);

                if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
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

                if ($facultyId) {
                    if (!$this->facultyCanAccommodateSubjectUnits($validationSchedules, $facultyId, $subject, $day)) {
                        $result['failure_reasons'][] = "{$subject->subject_code}: faculty daily/weekly units limit exceeded";
                        $groupFailed = true;
                        break;
                    }
                }

                if (!$this->facultyAvailable($validationSchedules, $facultyId ? (int) $facultyId : null, $day, $start, $end)) {
                    $result['failure_reasons'][] = "{$subject->subject_code}: faculty conflict detected before save";
                    $groupFailed = true;
                    break;
                }

                if ($facultyId) {
                    $facultyForAvailability = (int) $subject->faculty_id === (int) $facultyId
                        ? $subject->faculty
                        : Faculty::select('id', 'full_name', 'availability')->find($facultyId);

                    $availability = $facultyForAvailability
                        ? $this->conflicts->checkFacultyAvailability($facultyForAvailability, $day, $start, $end)
                        : $this->conflicts->checkFacultyConflict((int) $facultyId, $day, $start, $end);

                    if (($availability['status'] ?? true) === false) {
                        $result['failure_reasons'][] = "{$subject->subject_code}: faculty is not available before save";
                        $groupFailed = true;
                        break;
                    }
                }

                $payload = [
                    'subject_id' => $subject->id,
                    'room_id' => $room->id,
                    'faculty_id' => $facultyId,
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
                    'edp_code' => $subject->edp_code,
                    'semester' => $period['semester'],
                    'school_year' => $period['school_year'],
                    'academic_year' => $period['school_year'],
                    'workspace_key' => $period['workspace_key'],
                    'is_archived' => false,
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
                $placeholder = $this->findPlaceholderForSchedule($subject->id, $subject->section);
                
                if ($placeholder && !$this->placeholderIsFullyScheduled($placeholder)) {
                    $this->convertPlaceholderToSchedule($placeholder, $payload);
                    $schedule = $placeholder;
                } else {
                    $schedule = Schedule::updateOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'section' => $subject->section,
                            'pairing_key' => $payload['pairing_key'],
                            'day' => $payload['day'],
                            'start_time' => $payload['start_time'],
                            'end_time' => $payload['end_time'],
                        ],
                        $payload
                    );
                }

                $schedule->setRelation('subject', $subject);
                $existingSchedules->push($schedule);

                $result['saved']++;
                $result['saved_items'][] = $item;
            }
        }

        $result['failed'] = count($result['failure_reasons']);
        return $result;
    }

    // ================================================================================
    // WAVE 1: Pre-Assigned Subject Handler
    // ================================================================================
    private function processPreAssignedSubject(
        Subject $subject,
        Schedule $placeholder,
        int $remainingMeetings,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        array $period,
        ?int $userId,
        bool $persist,
        array &$result
    ): void {
        $assignedFacultyId = (int) $placeholder->faculty_id;

        $linkedPattern = $this->generateLinkedMeetingPatternForFaculty(
            $subject,
            $rooms,
            $existingSchedules,
            $assignedFacultyId,
            $bounds,
            $remainingMeetings
        );

        if (!$linkedPattern) {
            $faculty = Faculty::find($assignedFacultyId);
            $facultyName = $faculty?->full_name ?? "Faculty ID: {$assignedFacultyId}";
            
            $reason = $this->compatibleRooms($rooms, $subject)->isEmpty()
                ? "pre-assigned faculty '{$facultyName}': no compatible room"
                : "pre-assigned faculty '{$facultyName}': no linked room/time group where faculty is available";

            $this->recordFailure($result, $subject, $reason);
            return;
        }

        $pairingKey = $linkedPattern['pairing_key'];
        $dayPair = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

        foreach ($linkedPattern['placements'] as $placement) {
            $durationHours = round(
                Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60,
                2
            );

            $scheduleData = [
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'faculty_id' => $assignedFacultyId,
                'user_id' => $userId,
                'department' => $subject->department,
                'major' => $subject->major,
                'year_level' => $subject->year_level,
                'section' => $subject->section,
                'day' => $placement['day'],
                'start_time' => $placement['start'],
                'end_time' => $placement['end'],
                'duration_hours' => $durationHours,
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
                'status' => Schedule::STATUS_PARTIAL,
                'edp_code' => $subject->edp_code,
                'semester' => $period['semester'],
                'school_year' => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived' => false,
            ];

            if ($persist) {
                $this->convertPlaceholderToSchedule($placeholder, $scheduleData);
            } else {
                $schedule = new Schedule($scheduleData);
                $schedule->setRelation('subject', $subject);
                $existingSchedules->push($schedule);
            }

            $result['scheduled']++;
            $result['scheduled_items'][] = [
                'subject_code' => $this->cleanText($subject->subject_code),
                'subject_name' => $this->cleanText($subject->description),
                'edp_code' => $this->cleanText($subject->edp_code),
                'room' => $this->cleanText($placement['room']->room_name),
                'day_pair' => $dayPair,
                'day' => $placement['day'],
                'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                'faculty_id' => $assignedFacultyId,
                'instructor' => $this->cleanText(Faculty::find($assignedFacultyId)?->full_name ?? 'Unassigned'),
                'raw_start_time' => $placement['start'],
                'raw_end_time' => $placement['end'],
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'duration_hours' => $durationHours,
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
                'session_fallback' => (bool) ($placement['session_fallback'] ?? false),
                'wave' => 'WAVE 1 (Pre-Assigned)',
            ];

            if ($placement['fallback'] ?? false) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used fallback room {$placement['room']->room_name}."
                );
            }

            if (!empty($placement['session_fallback'])) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used afternoon fallback because preferred morning slots were unavailable."
                );
            }
        }
    }

    // ================================================================================
    // WAVE 2: Unassigned Subject Handler
    // ================================================================================
    private function processUnassignedSubject(
        Subject $subject,
        int $remainingMeetings,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        array $period,
        ?int $userId,
        bool $persist,
        array &$result
    ): void {
        $linkedPattern = $this->generateLinkedMeetingPattern(
            $subject,
            $rooms,
            $existingSchedules,
            $bounds,
            $remainingMeetings
        );

        if (!$linkedPattern) {
            $reason = $this->compatibleRooms($rooms, $subject)->isEmpty()
                ? 'no compatible room'
                : 'no linked room/time group';

            $this->recordFailure($result, $subject, $reason);
            return;
        }

        $pairingKey = $linkedPattern['pairing_key'];
        $dayPair = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

        foreach ($linkedPattern['placements'] as $placement) {
            $durationHours = round(
                Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60,
                2
            );
            
            $scheduleData = [
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'faculty_id' => $subject->faculty_id,
                'user_id' => $userId,
                'department' => $subject->department,
                'major' => $subject->major,
                'year_level' => $subject->year_level,
                'section' => $subject->section,
                'day' => $placement['day'],
                'start_time' => $placement['start'],
                'end_time' => $placement['end'],
                'duration_hours' => $durationHours,
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
                'status' => Schedule::STATUS_PARTIAL,
                'edp_code' => $subject->edp_code,
                'semester' => $period['semester'],
                'school_year' => $period['school_year'],
                'academic_year' => $period['school_year'],
                'workspace_key' => $period['workspace_key'],
                'is_archived' => false,
            ];

            if ($persist) {
                Schedule::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'section' => $subject->section,
                        'pairing_key' => $pairingKey,
                        'day' => $scheduleData['day'],
                        'start_time' => $scheduleData['start_time'],
                        'end_time' => $scheduleData['end_time'],
                    ],
                    $scheduleData
                );
            } else {
                $schedule = new Schedule($scheduleData);
                $schedule->setRelation('subject', $subject);
                $existingSchedules->push($schedule);
            }

            $result['scheduled']++;
            $result['scheduled_items'][] = [
                'subject_code' => $this->cleanText($subject->subject_code),
                'subject_name' => $this->cleanText($subject->description),
                'edp_code' => $this->cleanText($subject->edp_code),
                'room' => $this->cleanText($placement['room']->room_name),
                'day_pair' => $dayPair,
                'day' => $placement['day'],
                'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                'faculty_id' => $subject->faculty_id,
                'instructor' => $this->cleanText($subject->faculty?->full_name ?? 'Unassigned'),
                'raw_start_time' => $placement['start'],
                'raw_end_time' => $placement['end'],
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'duration_hours' => $durationHours,
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
                'session_fallback' => (bool) ($placement['session_fallback'] ?? false),
                'wave' => 'WAVE 2 (Unassigned)',
            ];

            if ($placement['fallback'] ?? false) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used fallback room {$placement['room']->room_name}."
                );
            }

            if (!empty($placement['session_fallback'])) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used afternoon fallback because preferred morning slots were unavailable."
                );
            }
        }
    }

    // ================================================================================
    // HELPER METHODS: Placeholder Detection & Management
    // ================================================================================

    private function findPreAssignmentPlaceholder(Subject $subject): ?Schedule
    {
        return $this->activeScheduleQuery()
            ->where('subject_id', $subject->id)
            ->where('section', $subject->section)
            ->whereNotNull('faculty_id')
            ->where(function ($query) {
                $query->whereNull('day')
                    ->orWhereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhereNull('room_id');
            })
            ->first();
    }

    private function findPlaceholderForSchedule(int $subjectId, string $section): ?Schedule
    {
        return $this->activeScheduleQuery()
            ->where('subject_id', $subjectId)
            ->where('section', $section)
            ->where(function ($query) {
                $query->whereNull('day')
                    ->orWhereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhereNull('room_id');
            })
            ->first();
    }

    private function placeholderIsFullyScheduled(Schedule $placeholder): bool
    {
        return filled($placeholder->day)
            && filled($placeholder->start_time)
            && filled($placeholder->end_time)
            && filled($placeholder->room_id);
    }

    private function convertPlaceholderToSchedule(Schedule $placeholder, array $scheduleData): void
    {
        if (!isset($scheduleData['faculty_id']) && filled($placeholder->faculty_id)) {
            $scheduleData['faculty_id'] = $placeholder->faculty_id;
        }
        
        $placeholder->update($scheduleData);
    }

    // ================================================================================
    // RETRY & PREVIEW METHODS
    // ================================================================================

    public function previewSubjectSchedule(int $subjectId, array $filters, array $overrides = [], array $pendingItems = [], ?int $userId = null): array
    {
        set_time_limit(300);

        $subject = $this->activeSubjectQuery()
            ->select($this->subjectSelectColumns())
            ->with('faculty:id,full_name,availability')
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

        if (array_key_exists('faculty_id', $overrides)) {
            $subject->faculty_id = filled($overrides['faculty_id']) ? (int) $overrides['faculty_id'] : null;
            $subject->unsetRelation('faculty');
            if ($subject->faculty_id) {
                $subject->load('faculty:id,full_name,availability');
            }
        }

        $rooms = Room::query()
            ->select($this->roomSelectColumns())
            ->available()
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        $roomIds = $rooms->pluck('id');

        $existingSchedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with(['subject:id,subject_code,department,major,year_level,section,faculty_id', 'room:id,room_name'])
            ->where(function ($query) use ($subject, $roomIds) {
                $query->where(function ($query) use ($subject) {
                    $query->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', (int) $subject->year_level)
                        ->where('section', $subject->section);
                })->orWhereIn('room_id', $roomIds);
            })
            ->get();

        foreach ($pendingItems as $item) {
            if (($item['subject_id'] ?? null) === $subject->id) {
                continue;
            }

            $existingSchedules->push(new Schedule([
                'subject_id' => $item['subject_id'] ?? null,
                'room_id' => $item['room_id'] ?? null,
                'faculty_id' => $item['faculty_id'] ?? null,
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

        $preferredDays = $this->normalizeDayList($overrides['preferred_days'] ?? []);
        $meetingsNeeded = max(1, min($this->activeDayCount(), max((int) $subject->meetings_per_week, count($preferredDays) ?: 1)));
        $subject->meetings_per_week = $meetingsNeeded;

        $preferredStart = $this->normalizePreferredStart($overrides['preferred_start_time'] ?? $overrides['preferred_time_start'] ?? null);
        $preferredEnd = $this->normalizePreferredStart($overrides['preferred_time_end'] ?? null);
        $preferredRoomHint = trim((string) ($overrides['preferred_room_type'] ?? ''));
        $linkedPattern = ($preferredStart || $preferredEnd || $preferredDays || $preferredRoomHint !== '')
            ? $this->findPatternWithPreferences($subject, $rooms, $existingSchedules, Setting::getDayBounds(), $meetingsNeeded, $preferredStart, $preferredDays, null, $preferredRoomHint, $preferredEnd)
            : null;

        $linkedPattern ??= $this->generateLinkedMeetingPattern($subject, $rooms, $existingSchedules, Setting::getDayBounds(), $meetingsNeeded);

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
                'faculty_id' => $subject->faculty_id,
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
                'subject_code' => $this->cleanText($subject->subject_code),
                'subject_name' => $this->cleanText($subject->description),
                'edp_code' => $this->cleanText($subject->edp_code),
                'room' => $this->cleanText($placement['room']->room_name),
                'day_pair' => $dayPair,
                'day' => $placement['day'],
                'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                'faculty_id' => $subject->faculty_id,
                'instructor' => $this->cleanText($subject->faculty?->full_name ?? 'Unassigned'),
                'raw_start_time' => $placement['start'],
                'raw_end_time' => $placement['end'],
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'duration_hours' => round(Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60, 2),
                'meetings_per_week' => $subject->meetings_per_week,
                'pairing_key' => $pairingKey,
                'session_fallback' => (bool) ($placement['session_fallback'] ?? false),
            ];

            if ($placement['fallback']) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText("{$subject->subject_code} used fallback room {$placement['room']->room_name}.");
            }

            if (!empty($placement['session_fallback'])) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText("{$subject->subject_code} used afternoon fallback because preferred morning slots were unavailable.");
            }
        }

        $result['failed'] = count($result['failed_items']);

        return $result;
    }

    public function retryFailedSubject(int $subjectId, array $filters, array $overrides = [], array $pendingItems = [], ?int $userId = null): array
    {
        set_time_limit(300);

        \Illuminate\Support\Facades\Log::info('[RetryGeneration] Retry started', [
            'subject_id' => $subjectId,
            'overrides'  => $overrides,
        ]);

        $subject = $this->activeSubjectQuery()
            ->select($this->subjectSelectColumns())
            ->with('faculty:id,full_name,availability')
            ->find($subjectId);

        if (!$subject) {
            \Illuminate\Support\Facades\Log::warning('[RetryGeneration] Subject not found', ['subject_id' => $subjectId]);
            return $this->emptyResult('subject not found');
        }

        if (isset($overrides['meetings_per_week']) && is_numeric($overrides['meetings_per_week'])) {
            $newMpw = max(1, (int) $overrides['meetings_per_week']);
            \Illuminate\Support\Facades\Log::info('[RetryGeneration] Meetings per week updated', [
                'subject_code' => $subject->subject_code,
                'old_mpw'      => $subject->meetings_per_week,
                'new_mpw'      => $newMpw,
            ]);
            $subject->meetings_per_week = $newMpw;
        }

        $meetingsNeeded = max(1, min($this->activeDayCount(), (int) $subject->meetings_per_week));
        $subject->meetings_per_week = $meetingsNeeded;

        \Illuminate\Support\Facades\Log::info('[RetryGeneration] Generating fresh time slots and room candidates', [
            'subject_code'        => $subject->subject_code,
            'meetings_needed'     => $meetingsNeeded,
            'minutes_per_meeting' => $this->minutesPerMeeting($subject),
        ]);

        $rooms = Room::query()
            ->select($this->roomSelectColumns())
            ->available()
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

        $result = [
            'scheduled'         => 0,
            'failed'            => 0,
            'warnings'          => 0,
            'failure_reasons'   => [],
            'failed_items'      => [],
            'fallback_warnings' => [],
            'scheduled_items'   => [],
            'searched'          => $this->retrySearchScope($subject),
            'recommendations'   => [],
            'filters'           => [
                'department' => strtoupper((string) ($filters['department'] ?? $subject->department)),
                'major'      => strtoupper((string) ($filters['major']      ?? $subject->major)),
                'year_level' => (int)              ($filters['year_level']  ?? $subject->year_level),
                'section'    => strtoupper((string) ($filters['section']    ?? $subject->section)),
            ],
        ];

        if ($rooms->isEmpty()) {
            \Illuminate\Support\Facades\Log::warning('[RetryGeneration] No valid rooms available', ['subject_code' => $subject->subject_code]);
            $this->recordFailure($result, $subject, 'No valid rooms found in the room registry');
            $result['failed'] = count($result['failed_items']);
            $result['success'] = false;
            $result['message'] = 'No valid rooms found in the room registry';
            $result['recommendations'] = [];
            return $result;
        }

        $compatibleRoomCount = $this->compatibleRooms($rooms, $subject)->count();
        \Illuminate\Support\Facades\Log::info('[RetryGeneration] Finding compatible rooms', [
            'subject_code'          => $subject->subject_code,
            'total_rooms'           => $rooms->count(),
            'compatible_room_count' => $compatibleRoomCount,
        ]);

        if ($compatibleRoomCount === 0) {
            $requiresLab = $this->subjectRequiresLab($subject);
            $reason = $requiresLab
                ? "No compatible laboratory room available for {$subject->subject_code}"
                : "No compatible lecture room available for {$subject->subject_code}";
            \Illuminate\Support\Facades\Log::warning('[RetryGeneration] No compatible rooms', ['subject_code' => $subject->subject_code, 'requires_lab' => $requiresLab]);
            $this->recordFailure($result, $subject, $reason);
            $result['failed'] = count($result['failed_items']);
            $result['success'] = false;
            $result['message'] = $reason;
            $result['recommendations'] = [];
            return $result;
        }

        $roomIds = $rooms->pluck('id');

        $existingSchedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with(['subject:id,subject_code,department,major,year_level,section,faculty_id', 'room:id,room_name'])
            ->where(function ($query) use ($subject, $roomIds) {
                $query->where(function ($query) use ($subject) {
                    $query->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', (int) $subject->year_level)
                        ->where('section', $subject->section);
                })->orWhereIn('room_id', $roomIds);

                if ($subject->faculty_id) {
                    $query->orWhere('faculty_id', $subject->faculty_id);
                }
            })
            ->where('subject_id', '!=', $subject->id)
            ->get();

        foreach ($pendingItems as $item) {
            if (($item['subject_id'] ?? null) === $subject->id) {
                continue;
            }
            $existingSchedules->push(new Schedule([
                'subject_id'  => $item['subject_id'] ?? null,
                'room_id'     => $item['room_id'] ?? null,
                'faculty_id'  => $item['faculty_id'] ?? null,
                'department'  => $filters['department'] ?? null,
                'major'       => $filters['major'] ?? null,
                'year_level'  => $filters['year_level'] ?? null,
                'section'     => $filters['section'] ?? null,
                'day'         => $item['day'] ?? null,
                'start_time'  => $item['raw_start_time'] ?? null,
                'end_time'    => $item['raw_end_time'] ?? null,
                'pairing_key' => $item['pairing_key'] ?? null,
                'status'      => Schedule::STATUS_PARTIAL,
            ]));
        }

        if ($subject->faculty_id && $subject->faculty) {
            \Illuminate\Support\Facades\Log::info('[RetryGeneration] Finding available faculty', [
                'subject_code' => $subject->subject_code,
                'faculty_id'   => $subject->faculty_id,
                'faculty_name' => $subject->faculty->full_name,
            ]);
        }

        $bounds = Setting::getDayBounds();

        \Illuminate\Support\Facades\Log::info('[RetryGeneration] Conflict validation started', [
            'subject_code'         => $subject->subject_code,
            'existing_schedule_ct' => $existingSchedules->count(),
        ]);

        $linkedPattern = null;
        $scheduledSubject = $subject;

        foreach ($this->retryFacultyCandidates($subject) as $candidateFaculty) {
            $candidateSubject = clone $subject;

            if ($candidateFaculty) {
                $candidateSubject->faculty_id = $candidateFaculty->id;
                $candidateSubject->setRelation('faculty', $candidateFaculty);
            } elseif (!$subject->faculty_id) {
                $candidateSubject->faculty_id = null;
                $candidateSubject->unsetRelation('faculty');
            }

            $linkedPattern = $this->findFreshLinkedMeetingPattern($candidateSubject, $rooms, $existingSchedules, $bounds, $meetingsNeeded);

            if ($linkedPattern) {
                $scheduledSubject = $candidateSubject;
                break;
            }
        }

        if (!$linkedPattern) {
            $reason = $this->buildRichFailureReason($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded);
            \Illuminate\Support\Facades\Log::warning('[RetryGeneration] Retry failed', [
                'subject_code' => $subject->subject_code,
                'reason'       => $reason,
            ]);
            $this->recordFailure($result, $subject, $reason);
            $result['failed'] = count($result['failed_items']);
            $result['success'] = false;
            $result['message'] = $reason;
            // IMPROVED: pass $existingSchedules and $bounds through — both are already
            // computed above — so recommendations reflect real room/faculty/slot data.
            $result['recommendations'] = $this->buildSmartRecommendations($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded);
            return $result;
        }

        $pairingKey = $linkedPattern['pairing_key'];
        $dayPair    = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

        foreach ($linkedPattern['placements'] as $placement) {
            $durationHours = round(
                Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60,
                2
            );

            $result['scheduled']++;
            $result['scheduled_items'][] = [
                'subject_code'      => $this->cleanText($subject->subject_code),
                'subject_name'      => $this->cleanText($subject->description),
                'edp_code'          => $this->cleanText($subject->edp_code),
                'room'              => $this->cleanText($placement['room']->room_name),
                'day_pair'          => $dayPair,
                'day'               => $placement['day'],
                'start_time'        => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time'          => Carbon::parse($placement['end'])->format('h:i A'),
                'faculty_id'        => $scheduledSubject->faculty_id,
                'instructor'        => $this->cleanText($scheduledSubject->faculty?->full_name ?? 'Unassigned'),
                'raw_start_time'    => $placement['start'],
                'raw_end_time'      => $placement['end'],
                'subject_id'        => $subject->id,
                'room_id'           => $placement['room']->id,
                'duration_hours'    => $durationHours,
                'meetings_per_week' => $scheduledSubject->meetings_per_week,
                'pairing_key'       => $pairingKey,
                'session_fallback'  => (bool) ($placement['session_fallback'] ?? false),
            ];

            if ($placement['fallback']) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used fallback room {$placement['room']->room_name}."
                );
            }

            if (!empty($placement['session_fallback'])) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText(
                    "{$subject->subject_code} used afternoon fallback because preferred morning slots were unavailable."
                );
            }
        }

        $result['failed'] = count($result['failed_items']);
        $result['success'] = true;
        $result['message'] = 'Retry generated a compatible schedule.';

        \Illuminate\Support\Facades\Log::info('[RetryGeneration] Retry success', [
            'subject_code' => $subject->subject_code,
            'day_pair'     => $dayPair,
            'room'         => $linkedPattern['placements'][0]['room']->room_name ?? 'unknown',
        ]);

        return $result;
    }

    public function previewManualScheduleEdit(int $subjectId, array $filters, array $overrides = [], array $pendingItems = [], ?int $userId = null): array
    {
        set_time_limit(300);

        $subject = $this->activeSubjectQuery()
            ->select($this->subjectSelectColumns())
            ->with('faculty:id,full_name,availability')
            ->find($subjectId);

        if (!$subject) {
            return $this->emptyResult('subject not found');
        }

        $days = $this->normalizeDayList($overrides['days'] ?? $overrides['preferred_days'] ?? []);
        $requestedMeetings = filled($overrides['meetings_per_week'] ?? null)
            ? (int) $overrides['meetings_per_week']
            : (count($days) ?: (int) $subject->meetings_per_week);
        $meetingsNeeded = max(1, min($this->activeDayCount(), $requestedMeetings));
        $subject->meetings_per_week = $meetingsNeeded;

        if (array_key_exists('faculty_id', $overrides)) {
            $subject->faculty_id = filled($overrides['faculty_id']) ? (int) $overrides['faculty_id'] : null;
            $subject->unsetRelation('faculty');
            if ($subject->faculty_id) {
                $subject->load('faculty:id,full_name,availability');
            }
        }

        $roomId = filled($overrides['room_id'] ?? null) ? (int) $overrides['room_id'] : null;
        $rooms = Room::query()
            ->select($this->roomSelectColumns())
            ->available()
            ->when($roomId, fn ($query) => $query->whereKey($roomId))
            ->get()
            ->filter(fn (Room $room) => $this->roomRegistryRowIsValid($room))
            ->values();

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

        if ($rooms->isEmpty()) {
            $this->recordFailure($result, $subject, $roomId ? 'Selected room is unavailable or invalid' : 'No valid room available');
            $result['failed'] = count($result['failed_items']);

            return $result;
        }

        $roomIds = $rooms->pluck('id');

        $existingSchedules = $this->activeScheduleQuery()
            ->select('id', 'subject_id', 'room_id', 'faculty_id', 'department', 'major', 'year_level', 'section', 'day', 'start_time', 'end_time', 'duration_hours', 'meetings_per_week', 'pairing_key', 'status')
            ->with('subject:id,subject_code,department,major,year_level,section,faculty_id')
            ->where(function ($query) use ($subject, $roomIds) {
                $query->where(function ($query) use ($subject) {
                    $query->where('department', $subject->department)
                        ->where('major', $subject->major)
                        ->where('year_level', (int) $subject->year_level)
                        ->where('section', $subject->section);
                })->orWhereIn('room_id', $roomIds);

                if ($subject->faculty_id) {
                    $query->orWhere('faculty_id', $subject->faculty_id);
                }
            })
            ->get();

        foreach ($pendingItems as $item) {
            $existingSchedules->push(new Schedule([
                'subject_id' => $item['subject_id'] ?? null,
                'room_id' => $item['room_id'] ?? null,
                'faculty_id' => $item['faculty_id'] ?? null,
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

        $preferredStart = $this->normalizePreferredStart($overrides['start_time'] ?? $overrides['preferred_start_time'] ?? null);
        $room = $rooms->first();

        if (!$preferredStart || !$room || empty($days)) {
            $this->recordFailure($result, $subject, 'Choose a compatible room, start time, and at least one meeting day');
            $result['failed'] = count($result['failed_items']);

            return $result;
        }

        $start = Carbon::parse($preferredStart)->format('H:i:s');
        $end = Carbon::parse($preferredStart)->addMinutes($this->minutesPerMeeting($subject))->format('H:i:s');
        $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);
        $allowSessionFallback = (bool) ($overrides['session_fallback'] ?? $overrides['allow_session_fallback'] ?? false);

        if ($sessionFallback && !$allowSessionFallback) {
            $allowSessionFallback = $this->sectionFallbackShouldBeAllowed(
                $subject,
                $rooms,
                $existingSchedules,
                Setting::getDayBounds(),
                $meetingsNeeded
            );
        }

        $conflict = $this->validateManualPlacementPattern(
            $subject,
            $room,
            $existingSchedules,
            $days,
            $start,
            $end,
            Setting::getDayBounds(),
            $allowSessionFallback
        );

        if ($conflict) {
            $this->recordFailure($result, $subject, $conflict['message'] ?? 'Schedule conflict detected');
            $result['failed'] = count($result['failed_items']);
            $result['conflict'] = $conflict;

            return $result;
        }

        $placements = $this->buildPlacementsForDays(
            $existingSchedules,
            $subject,
            $room,
            $days,
            $start,
            $end,
            $this->compatibilityScore($room, $subject),
            false,
            $sessionFallback
        );

        $linkedPattern = $placements ? [
            'pairing_key' => $this->makePairingKey($subject),
            'placements' => $placements,
        ] : null;

        if (!$linkedPattern) {
            $this->recordFailure($result, $subject, 'Selected edit conflicts with room, faculty, section, lunch, or paired-day rules');
            $result['failed'] = count($result['failed_items']);

            return $result;
        }

        $pairingKey = $linkedPattern['pairing_key'];
        $dayPair = collect($linkedPattern['placements'])->pluck('day')->implode(' / ');

        foreach ($linkedPattern['placements'] as $placement) {
            $durationHours = round(Carbon::parse($placement['start'])->diffInMinutes(Carbon::parse($placement['end'])) / 60, 2);

            $result['scheduled']++;
            $result['scheduled_items'][] = [
                'subject_code' => $this->cleanText($subject->subject_code),
                'subject_name' => $this->cleanText($subject->description),
                'edp_code' => $this->cleanText($subject->edp_code),
                'room' => $this->cleanText($placement['room']->room_name),
                'day_pair' => $dayPair,
                'day' => $placement['day'],
                'start_time' => Carbon::parse($placement['start'])->format('h:i A'),
                'end_time' => Carbon::parse($placement['end'])->format('h:i A'),
                'faculty_id' => $subject->faculty_id,
                'instructor' => $this->cleanText($subject->faculty?->full_name ?? 'Unassigned'),
                'raw_start_time' => $placement['start'],
                'raw_end_time' => $placement['end'],
                'subject_id' => $subject->id,
                'room_id' => $placement['room']->id,
                'duration_hours' => $durationHours,
                'meetings_per_week' => $meetingsNeeded,
                'pairing_key' => $pairingKey,
                'session_fallback' => (bool) ($placement['session_fallback'] ?? false),
            ];

            if ($placement['fallback']) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText("{$subject->subject_code} used fallback room {$placement['room']->room_name}.");
            }

            if (!empty($placement['session_fallback'])) {
                $result['warnings']++;
                $result['fallback_warnings'][] = $this->cleanText("{$subject->subject_code} used afternoon fallback because preferred morning slots were unavailable.");
            }
        }

        return $result;
    }

    // ================================================================================
    // PATTERN/SEARCH GENERATORS
    // ================================================================================

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

    public function generateLinkedMeetingPatternForFaculty(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        int $preAssignedFacultyId,
        ?array $bounds = null,
        ?int $meetingsNeeded = null
    ): ?array {
        return $this->findConsistentRoomAndTimeForFaculty(
            $subject,
            $rooms,
            $existingSchedules,
            $preAssignedFacultyId,
            $bounds ?? Setting::getDayBounds(),
            $meetingsNeeded
        );
    }

    private function findConsistentRoomAndTimeForFaculty(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        int $preAssignedFacultyId,
        array $bounds,
        ?int $meetingsNeeded = null,
        int $meetingIndex = 0,
        bool $allowSessionFallback = true
    ): ?array {
        $meetingsNeeded = max(1, min($this->activeDayCount(), $meetingsNeeded ?? (int) $subject->meetings_per_week));

        $anchoredPattern = $this->findAnchoredLinkedPattern($subject, $rooms, $existingSchedules, $meetingsNeeded);
        if ($anchoredPattern) {
            return $anchoredPattern;
        }

        $minutes = $this->minutesPerMeeting($subject);
        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules, $meetingIndex);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        foreach ($this->sectionWindowPasses($subject->section, $bounds, $allowSessionFallback) as $windows) {
            foreach ($compatibleRooms as $candidate) {
                $room = $candidate['room'];

                foreach ($this->meetingDayPatterns($meetingsNeeded) as $days) {
                    foreach ($windows as $window) {
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

                            $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

                            if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
                                continue;
                            }

                            $facultyConflictFound = false;
                            foreach ($days as $day) {
                                if (!$this->facultyAvailableForDuration(
                                    $existingSchedules,
                                    $preAssignedFacultyId,
                                    $day,
                                    $start,
                                    $end
                                )) {
                                    $facultyConflictFound = true;
                                    break;
                                }
                            }

                            if ($facultyConflictFound) {
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
                                $candidate['fallback'],
                                $sessionFallback
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
        }

        return null;
    }

    public function findConsistentRoomAndTime(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        ?int $meetingsNeeded = null,
        int $meetingIndex = 0,
        bool $allowSessionFallback = true
    ): ?array {
        $meetingsNeeded = max(1, min($this->activeDayCount(), $meetingsNeeded ?? (int) $subject->meetings_per_week));

        $anchoredPattern = $this->findAnchoredLinkedPattern($subject, $rooms, $existingSchedules, $meetingsNeeded);
        if ($anchoredPattern) {
            return $anchoredPattern;
        }

        $minutes = $this->minutesPerMeeting($subject);
        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules, $meetingIndex);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        // IMPROVED: instead of returning the very first valid slot found (which packs every
        // subject into the earliest possible time of day), gather up to one candidate per
        // time-of-day bucket and keep whichever has the least nearby existing load. Falls back
        // to whichever was found first if every candidate scores the same.
        $slotCandidates = [];
        $seenBuckets = [];

        foreach ($this->sectionWindowPasses($subject->section, $bounds, $allowSessionFallback) as $windows) {
            foreach ($compatibleRooms as $candidate) {
                $room = $candidate['room'];

                // IMPROVED: pass $existingSchedules/$subject through so day patterns can be
                // ranked by current load instead of always trying Mon/Wed first.
                foreach ($this->meetingDayPatterns($meetingsNeeded, [], $existingSchedules, $subject) as $days) {
                    foreach ($windows as $window) {
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

                            $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

                            if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
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
                                $candidate['fallback'],
                                $sessionFallback
                            );

                            if ($placements) {
                                $bucket = $this->slotTimeBucket($start);

                                if (!isset($seenBuckets[$bucket])) {
                                    $seenBuckets[$bucket] = true;
                                    $slotCandidates[] = [
                                        'pairing_key' => $this->makePairingKey($subject),
                                        'placements' => $placements,
                                        'days' => $days,
                                    ];

                                    if (count($slotCandidates) >= 3) {
                                        break 5;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($slotCandidates)) {
            return $this->pickBestSlotCandidate($slotCandidates, $existingSchedules);
        }

        return null;
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

    public function findFreshLinkedMeetingPattern(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        ?array $bounds = null,
        ?int $meetingsNeeded = null,
        bool $allowSessionFallback = true
    ): ?array {
        $bounds = $bounds ?? Setting::getDayBounds();
        $meetingsNeeded = max(1, min($this->activeDayCount(), $meetingsNeeded ?? (int) $subject->meetings_per_week));
        $minutes = $this->minutesPerMeeting($subject);

        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        // IMPROVED: instead of returning the very first valid slot found (which packs every
        // subject into the earliest possible time of day), gather up to one candidate per
        // time-of-day bucket and keep whichever has the least nearby existing load. Falls back
        // to whichever was found first if every candidate scores the same.
        $slotCandidates = [];
        $seenBuckets = [];

        foreach ($this->sectionWindowPasses($subject->section, $bounds, $allowSessionFallback) as $windows) {
            foreach ($compatibleRooms as $candidate) {
                $room = $candidate['room'];

                // IMPROVED: pass $existingSchedules/$subject through so day patterns can be
                // ranked by current load instead of always trying Mon/Wed first.
                foreach ($this->meetingDayPatterns($meetingsNeeded, [], $existingSchedules, $subject) as $days) {
                    foreach ($windows as $window) {
                        if ($window['start']->copy()->addMinutes($minutes)->gt($window['end'])) {
                            continue;
                        }

                        $period = \Carbon\CarbonPeriod::create(
                            $window['start'],
                            self::SLOT_MINUTES . ' minutes',
                            $window['end']->copy()->subMinutes($minutes)
                        );

                        foreach ($period as $slotStart) {
                            $slotEnd = $slotStart->copy()->addMinutes($minutes);
                            $start   = $slotStart->format('H:i:s');
                            $end     = $slotEnd->format('H:i:s');

                            if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                                continue;
                            }

                            $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

                            if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
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
                                $candidate['fallback'],
                                $sessionFallback
                            );

                            if ($placements) {
                                $bucket = $this->slotTimeBucket($start);

                                if (!isset($seenBuckets[$bucket])) {
                                    $seenBuckets[$bucket] = true;
                                    $slotCandidates[] = [
                                        'pairing_key' => $this->makePairingKey($subject),
                                        'placements'  => $placements,
                                        'days' => $days,
                                    ];

                                    if (count($slotCandidates) >= 3) {
                                        break 5;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($slotCandidates)) {
            return $this->pickBestSlotCandidate($slotCandidates, $existingSchedules);
        }

        return null;
    }

    private function findPatternWithPreferences(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        int $meetingsNeeded,
        ?string $preferredStart = null,
        array $preferredDays = [],
        ?int $preferredRoomId = null,
        ?string $preferredRoomHint = null,
        ?string $preferredEnd = null
    ): ?array {
        $meetingsNeeded = max(1, min($this->activeDayCount(), max($meetingsNeeded, count($preferredDays))));
        $minutes = $this->minutesPerMeeting($subject);
        $compatibleRooms = $this->compatibleRooms($rooms, $subject, $existingSchedules);

        if ($preferredRoomId) {
            $compatibleRooms = $compatibleRooms
                ->sortBy(fn (array $candidate) => (int) $candidate['room']->id === $preferredRoomId ? 0 : 1)
                ->values();
        }

        $compatibleRooms = $this->applyRoomPreference($compatibleRooms, $preferredRoomHint);

        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        foreach ($compatibleRooms as $candidate) {
            $room = $candidate['room'];

            foreach ($this->meetingDayPatterns($meetingsNeeded, $preferredDays) as $days) {
                if ($preferredDays && !empty(array_diff($preferredDays, $days))) {
                    continue;
                }

                if ($preferredStart && !$preferredEnd) {
                    $slotStart = Carbon::parse($preferredStart);
                    $slotEnd = $slotStart->copy()->addMinutes($minutes);
                    $start = $slotStart->format('H:i:s');
                    $end = $slotEnd->format('H:i:s');

                    $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

                    if (!$this->timeFitsAnySectionWindow($subject, $bounds, $start, $end, true)) {
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
                        $candidate['fallback'],
                        $sessionFallback
                    );

                    if ($placements) {
                        return [
                            'pairing_key' => $this->makePairingKey($subject),
                            'placements' => $placements,
                        ];
                    }

                    continue;
                }

                foreach ($this->sectionWindowPasses($subject->section, $bounds, true) as $windows) {
                    foreach ($windows as $window) {
                        $window = $this->limitWindowToPreferredRange($window, $preferredStart, $preferredEnd);

                        if (!$window) {
                            continue;
                        }

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
                            $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

                            if ($this->conflicts->overlapsLunchBreak($start, $end)
                                || !$this->conflicts->respectsSectionSession($subject->section, $start, $end, true)) {
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
                                $candidate['fallback'],
                                $sessionFallback
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
        }

        return null;
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

        $sessionFallback = $this->isSectionFallbackSlot($subject->section, $start, $end);

        if ($this->conflicts->overlapsLunchBreak($start, $end)
            || !$this->conflicts->respectsSectionSession($subject->section, $start, $end, true)) {
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
                    $candidate['fallback'],
                    $sessionFallback
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

    public function findAvailableSlot(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingIndex = 0): ?array
    {
        $pattern = $this->findConsistentRoomAndTime($subject, $rooms, $existingSchedules, Setting::getDayBounds(), 1, $meetingIndex);

        return $pattern['placements'][0] ?? null;
    }

    // ================================================================================
    // CONFLICTS, RULES & AVAILABILITY
    // ================================================================================

    private function facultyAvailableForDuration(
        Collection $schedules,
        ?int $facultyId,
        string $day,
        string $startTime,
        string $endTime
    ): bool {
        if (!$facultyId) {
            return true;
        }

        $requestedStart = Carbon::parse($startTime);
        $requestedEnd = Carbon::parse($endTime);

        return $schedules
            ->where('faculty_id', $facultyId)
            ->where('day', $day)
            ->whereNotNull('day')
            ->filter(function (Schedule $existing) use ($requestedStart, $requestedEnd) {
                $existingStart = Carbon::parse($existing->start_time);
                $existingEnd = Carbon::parse($existing->end_time);
                return $requestedStart->lt($existingEnd) && $requestedEnd->gt($existingStart);
            })
            ->isEmpty();
    }

    private function facultyCanAccommodateSubjectUnits(
        Collection $schedules,
        int $facultyId,
        Subject $subject,
        string $day
    ): bool {
        $faculty = Faculty::find($facultyId);
        if (!$faculty) {
            return true;
        }

        $subjectUnits = (int) ($subject->units ?? 0);
        $maxUnits = (int) ($faculty->max_units ?? 21);

        $assignedUnits = $schedules
            ->where('faculty_id', $facultyId)
            ->whereNotNull('subject_id')
            ->pluck('subject_id')
            ->unique()
            ->sum(fn ($id) => Subject::find($id)?->units ?? 0);

        return ($assignedUnits + $subjectUnits) <= $maxUnits;
    }

    public function hasRoomConflict(Collection $schedules, int $roomId, string $day, string $start, string $end): bool
    {
        return !$this->roomAvailable($schedules, $roomId, $day, $start, $end);
    }

    private function roomAvailable(Collection $schedules, int $roomId, string $day, string $start, string $end): bool
    {
        return !$schedules->contains(fn (Schedule $schedule) => (int) $schedule->room_id === $roomId
            && $schedule->day === $day
            && $this->conflicts->hasTimeOverlap($start, $end, $schedule->start_time, $schedule->end_time));
    }

    public function hasSectionConflict(Collection $schedules, Subject $subject, string $day, string $start, string $end): bool
    {
        return !$this->sectionAvailable($schedules, $subject, $day, $start, $end);
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

    private function facultyAvailable(Collection $schedules, ?int $facultyId, string $day, string $start, string $end): bool
    {
        if (!$facultyId) {
            return true;
        }

        return !$schedules->contains(fn (Schedule $schedule) => (int) $schedule->faculty_id === $facultyId
            && $schedule->day === $day
            && $this->conflicts->hasTimeOverlap($start, $end, $schedule->start_time, $schedule->end_time));
    }

    private function validateManualPlacementPattern(
        Subject $subject,
        Room $room,
        Collection $existingSchedules,
        array $days,
        string $start,
        string $end,
        array $bounds,
        bool $allowSessionFallback = false
    ): ?array {
        if (!$this->isRoomCompatible($room, $subject, allowMinorLabFallback: true)) {
            return $this->manualConflict(
                'ROOM_COMPATIBILITY',
                'Room Not Compatible',
                "{$room->room_name} is not compatible with {$subject->subject_code}.",
                $subject,
                $days[0] ?? 'Monday',
                $start,
                $end,
                null,
                ['conflicting_room' => $room->room_name]
            );
        }

        $boundsStart = Carbon::parse($bounds['start']);
        $boundsEnd = Carbon::parse($bounds['end']);
        $slotStart = Carbon::parse($start);
        $slotEnd = Carbon::parse($end);

        foreach ($days as $day) {
            if (!in_array($day, $this->activeDays(), true)) {
                return $this->manualConflict(
                    'BLOCKED_DAY',
                    'Blocked Schedule Day',
                    "{$day} is not available for scheduling.",
                    $subject,
                    $day,
                    $start,
                    $end
                );
            }

            if ($slotStart->lt($boundsStart) || $slotEnd->gt($boundsEnd)) {
                return $this->manualConflict(
                    'BLOCKED_TIME',
                    'Schedule Outside Grid Hours',
                    "This schedule is outside the allowed grid hours.",
                    $subject,
                    $day,
                    $start,
                    $end
                );
            }

            if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                return $this->manualConflict(
                    'LUNCH_BREAK_CONFLICT',
                    'Lunch Break Conflict',
                    "This schedule overlaps the lunch break from 12:00 PM to 1:00 PM.",
                    $subject,
                    $day,
                    $start,
                    $end
                );
            }

            if (!$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
                return $this->manualConflict(
                    'BLOCKED_TIME',
                    'Section Time Conflict',
                    "Section {$subject->section} cannot be scheduled during this time unless morning options are unavailable.",
                    $subject,
                    $day,
                    $start,
                    $end
                );
            }

            $duplicate = $this->firstScheduleConflict(
                $existingSchedules,
                fn (Schedule $schedule) => (int) $schedule->subject_id === (int) $subject->id,
                $day,
                $start,
                $end
            );

            if ($duplicate) {
                return $this->manualConflict(
                    'DUPLICATE_SCHEDULE',
                    'Duplicate Schedule Detected',
                    "{$subject->subject_code} already has a generated or saved schedule during this time.",
                    $subject,
                    $day,
                    $start,
                    $end,
                    $duplicate,
                    ['suggestion' => 'Choose another day or remove the existing temporary block first.']
                );
            }

            $roomConflict = $this->firstScheduleConflict(
                $existingSchedules,
                fn (Schedule $schedule) => (int) $schedule->room_id === (int) $room->id,
                $day,
                $start,
                $end
            );

            if ($roomConflict) {
                return $this->manualConflict(
                    'ROOM_CONFLICT',
                    'Schedule Conflict Detected',
                    "{$room->room_name} is already occupied on {$day} from "
                        . Carbon::parse($roomConflict->start_time)->format('h:i A')
                        . ' to '
                        . Carbon::parse($roomConflict->end_time)->format('h:i A')
                        . '.',
                    $subject,
                    $day,
                    $start,
                    $end,
                    $roomConflict,
                    ['conflicting_room' => $room->room_name]
                );
            }

            $sectionConflict = $this->firstScheduleConflict(
                $existingSchedules,
                fn (Schedule $schedule) => $this->sameStudentGroup($schedule, $subject),
                $day,
                $start,
                $end
            );

            if ($sectionConflict) {
                return $this->manualConflict(
                    'SECTION_CONFLICT',
                    'Schedule Conflict Detected',
                    "Section {$subject->section} already has an assigned class during this time.",
                    $subject,
                    $day,
                    $start,
                    $end,
                    $sectionConflict,
                    ['group' => $this->studentGroup($subject)]
                );
            }

            if ($subject->faculty_id) {
                $facultyConflict = $this->firstScheduleConflict(
                    $existingSchedules,
                    fn (Schedule $schedule) => (int) $schedule->faculty_id === (int) $subject->faculty_id,
                    $day,
                    $start,
                    $end
                );

                if ($facultyConflict) {
                    return $this->manualConflict(
                        'FACULTY_CONFLICT',
                        'Schedule Conflict Detected',
                        'Instructor already has an assigned class during this time.',
                        $subject,
                        $day,
                        $start,
                        $end,
                        $facultyConflict,
                        ['faculty_name' => $subject->faculty?->full_name ?? 'Assigned instructor']
                    );
                }

                if ($subject->faculty) {
                    $availability = $this->conflicts->checkFacultyAvailability($subject->faculty, $day, $start, $end);

                    if (($availability['status'] ?? true) === false) {
                        return $this->manualConflict(
                            'FACULTY_AVAILABILITY',
                            $availability['title'] ?? 'Faculty Not Available',
                            $availability['message'] ?? 'Instructor is not available during this time.',
                            $subject,
                            $day,
                            $start,
                            $end,
                            null,
                            ['faculty_name' => $subject->faculty->full_name]
                        );
                    }
                }
            }
        }

        return null;
    }

    private function firstScheduleConflict(Collection $schedules, callable $predicate, string $day, string $start, string $end): ?Schedule
    {
        return $schedules->first(fn (Schedule $schedule) => $predicate($schedule)
            && $schedule->day === $day
            && filled($schedule->start_time)
            && filled($schedule->end_time)
            && $this->conflicts->hasTimeOverlap($start, $end, $schedule->start_time, $schedule->end_time));
    }

    private function manualConflict(
        string $type,
        string $title,
        string $message,
        Subject $subject,
        string $day,
        string $start,
        string $end,
        ?Schedule $conflictingSchedule = null,
        array $extraDetails = []
    ): array {
        $details = [
            'conflict_type' => $type,
            'requested_subject' => $this->cleanText($subject->subject_code),
            'requested_start' => Carbon::parse($start)->format('h:i A'),
            'requested_end' => Carbon::parse($end)->format('h:i A'),
            'requested_day' => $day,
            'suggestion' => 'Choose a different room, day, or time to keep the generated schedule conflict-free.',
        ];

        if ($conflictingSchedule) {
            $details = array_merge($details, [
                'conflicting_schedule_id' => $conflictingSchedule->id,
                'conflicting_subject' => $this->cleanText($conflictingSchedule->subject?->subject_code ?: 'Generated schedule'),
                'conflicting_room' => $this->cleanText($conflictingSchedule->room?->room_name ?: 'Selected room'),
                'conflicting_start' => Carbon::parse($conflictingSchedule->start_time)->format('h:i A'),
                'conflicting_end' => Carbon::parse($conflictingSchedule->end_time)->format('h:i A'),
                'conflicting_day' => $conflictingSchedule->day,
            ]);
        }

        return [
            'status' => false,
            'type' => $type,
            'toast_type' => 'error',
            'title' => $title,
            'message' => $this->cleanText($message),
            'details' => array_merge($details, $extraDetails),
        ];
    }

    private function sameStudentGroup(Schedule $schedule, Subject $subject): bool
    {
        return strtoupper((string) ($schedule->department ?? $schedule->subject?->department)) === strtoupper((string) $subject->department)
            && strtoupper((string) ($schedule->major ?? $schedule->subject?->major)) === strtoupper((string) $subject->major)
            && (int) ($schedule->year_level ?? $schedule->subject?->year_level) === (int) $subject->year_level
            && strtoupper((string) ($schedule->section ?? $schedule->subject?->section)) === strtoupper((string) $subject->section);
    }

    private function studentGroup(Subject $subject): string
    {
        return "{$subject->department}-{$subject->major}-{$subject->year_level}{$subject->section}";
    }

    private function buildPlacementsForDays(
        Collection $existingSchedules,
        Subject $subject,
        Room $room,
        array $days,
        string $start,
        string $end,
        int $score,
        bool $fallback,
        bool $sessionFallback = false
    ): ?array {
        if (count($days) !== count(array_unique($days))) {
            return null;
        }

        $placements = [];

        foreach ($days as $day) {
            if (!in_array($day, $this->activeDays(), true)) {
                return null;
            }

            if (!$this->roomAvailable($existingSchedules, $room->id, $day, $start, $end)) {
                return null;
            }

            if (!$this->sectionAvailable($existingSchedules, $subject, $day, $start, $end)) {
                return null;
            }

            if (!$this->facultyAvailable($existingSchedules, $subject->faculty_id ? (int) $subject->faculty_id : null, $day, $start, $end)) {
                return null;
            }

            if ($subject->faculty_id && $subject->relationLoaded('faculty') && $subject->faculty) {
                $availability = $this->conflicts->checkFacultyAvailability($subject->faculty, $day, $start, $end, null, null);

                if (($availability['status'] ?? true) === false) {
                    return null;
                }
            }

            $placements[] = [
                'room' => $room,
                'day' => $day,
                'start' => $start,
                'end' => $end,
                'score' => $score,
                'fallback' => $fallback,
                'session_fallback' => $sessionFallback,
            ];
        }

        if (count($placements) !== count($days)) {
            return null;
        }

        return $placements;
    }

    // IMPROVED: buckets a start time into a coarse part-of-day so the slot search below can
    // spread candidates across the day instead of always keeping the very first match (which
    // is what caused every subject to pile up at the day's opening slot).
    private function slotTimeBucket(string $start): string
    {
        $hour = (int) Carbon::parse($start)->format('H');

        if ($hour < 10) {
            return 'early_morning';
        }

        if ($hour < 13) {
            return 'mid_morning';
        }

        return 'afternoon';
    }

    // IMPROVED: counts how many existing schedules already sit within a ±90-minute window of
    // the given slot on any of the given days. Used to rank same-priority slot candidates by
    // how congested that part of the day/room/section already is.
    private function scoreSlotLoad(Collection $existingSchedules, array $days, string $start, string $end): int
    {
        $windowStart = Carbon::parse($start)->subMinutes(90);
        $windowEnd = Carbon::parse($end)->addMinutes(90);

        return $existingSchedules
            ->filter(function (Schedule $schedule) use ($days, $windowStart, $windowEnd) {
                if (!in_array($schedule->day, $days, true)) {
                    return false;
                }

                $scheduleStart = Carbon::parse($schedule->start_time);
                $scheduleEnd = Carbon::parse($schedule->end_time);

                return $scheduleStart->lt($windowEnd) && $scheduleEnd->gt($windowStart);
            })
            ->count();
    }

    // IMPROVED: picks the lowest-load candidate out of the (up to 3) slot options gathered
    // across the day. Ties — including the common case of only one candidate found — fall
    // back to whichever candidate was discovered first.
    private function pickBestSlotCandidate(array $candidates, Collection $existingSchedules): array
    {
        $best = collect($candidates)
            ->map(function (array $candidate) use ($existingSchedules) {
                $first = $candidate['placements'][0];
                $candidate['load'] = $this->scoreSlotLoad($existingSchedules, $candidate['days'], $first['start'], $first['end']);

                return $candidate;
            })
            ->sortBy('load')
            ->first();

        return [
            'pairing_key' => $best['pairing_key'],
            'placements' => $best['placements'],
        ];
    }

    // IMPROVED: counts how many existing schedules for this subject's exact student group
    // (department + major + year_level + section — the same grouping sectionAvailable() uses)
    // already land on any of the given days. Used by meetingDayPatterns() to break ties so
    // sections don't all default to the same Mon/Wed (or whichever) pairing.
    private function dayPatternLoad(Collection $existingSchedules, Subject $subject, array $days): int
    {
        return $existingSchedules
            ->filter(function (Schedule $schedule) use ($subject, $days) {
                $sameGroup = strtoupper((string) ($schedule->department ?? $schedule->subject?->department)) === strtoupper((string) $subject->department)
                    && strtoupper((string) ($schedule->major ?? $schedule->subject?->major)) === strtoupper((string) $subject->major)
                    && (int) ($schedule->year_level ?? $schedule->subject?->year_level) === (int) $subject->year_level
                    && strtoupper((string) ($schedule->section ?? $schedule->subject?->section)) === strtoupper((string) $subject->section);

                return $sameGroup && in_array($schedule->day, $days, true);
            })
            ->count();
    }

    private function buildRichFailureReason(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        int $meetingsNeeded
    ): string {
        $compatibleRooms = $this->compatibleRooms($rooms, $subject);

        if ($compatibleRooms->isEmpty()) {
            $requiresLab = $this->subjectRequiresLab($subject);
            return $requiresLab
                ? "No compatible laboratory room available for {$subject->subject_code}"
                : "No compatible lecture room available for {$subject->subject_code}";
        }

        if (strtoupper((string) $subject->section) === 'A'
            && !$this->findConsistentRoomAndTime($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded, 0, false)) {
            return 'No compatible morning schedules available. The AI also searched afternoon fallback slots, alternative paired days, compatible rooms, and eligible faculty, but every option still conflicts.';
        }

        if (strtoupper((string) $subject->section) === 'B'
            && !$this->findConsistentRoomAndTime($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded, 0, false)) {
            return 'No compatible afternoon schedules available. The AI also searched morning fallback slots, alternative paired days, compatible rooms, and eligible faculty, but every option still conflicts.';
        }

        if ($subject->faculty_id && $subject->faculty) {
            $faculty = $subject->faculty;
            $availability = $faculty->getAttribute('availability');

            if (!empty($availability) && is_array($availability)) {
                $activeDays = $this->activeDays();
                $hasAnyWindow = collect($activeDays)->contains(function (string $day) use ($availability) {
                    return !empty($availability[$day] ?? $availability[strtolower($day)] ?? []);
                });

                if (!$hasAnyWindow) {
                    return "No faculty available: {$faculty->full_name} has no availability set for active scheduling days";
                }
            }
        }

        $minutes = $this->minutesPerMeeting($subject);
        $activeDays = $this->activeDays();
        $blockedDays = 0;

        foreach ($activeDays as $day) {
            $dayFull = true;

            foreach ($this->sectionWindows($subject->section, $bounds, true) as $window) {
                if ($window['start']->copy()->addMinutes($minutes)->gt($window['end'])) {
                    continue;
                }

                $period = \Carbon\CarbonPeriod::create(
                    $window['start'],
                    self::SLOT_MINUTES . ' minutes',
                    $window['end']->copy()->subMinutes($minutes)
                );

                foreach ($period as $slotStart) {
                    $slotEnd = $slotStart->copy()->addMinutes($minutes);
                    $start   = $slotStart->format('H:i:s');
                    $end     = $slotEnd->format('H:i:s');

                    if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                        continue;
                    }

                    if ($this->sectionAvailable($existingSchedules, $subject, $day, $start, $end)) {
                        $dayFull = false;
                        break 2;
                    }
                }
            }

            if ($dayFull) {
                $blockedDays++;
            }
        }

        if ($blockedDays >= count($activeDays)) {
            return "Section {$subject->section} has no remaining free schedule slots across all active days. The AI searched morning slots, afternoon slots, paired days, compatible rooms, and available faculty.";
        }

        if ($blockedDays >= $meetingsNeeded) {
            return "No valid {$meetingsNeeded}-day combination found for {$subject->subject_code}. Section {$subject->section} is too congested across available morning and afternoon windows.";
        }

        if ($subject->faculty_id) {
            return "No valid paired-day combinations found. Faculty may be unavailable or fully loaded for the required {$meetingsNeeded} meeting(s) per week.";
        }

        // IMPROVED: before falling back to a purely generic message, check whether a single
        // conflict-free slot exists at all. If one does, tell the user exactly where instead of
        // just listing generic actions to try.
        $hint = $this->findFreshLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, 1, true);

        if ($hint && !empty($hint['placements'])) {
            $p = $hint['placements'][0];

            return "No {$meetingsNeeded}-meeting pattern fits. However, a single slot is free on {$p['day']} at "
                . Carbon::parse($p['start'])->format('h:i A')
                . " in {$p['room']->room_name}. Reduce meetings per week to 1 or free up more room/faculty time.";
        }

        return "No valid {$meetingsNeeded}-meeting/week combination found for {$subject->subject_code}. Try reducing meetings per week, assigning another compatible room, opening faculty availability, or allowing evening schedules.";
    }

    // ================================================================================
    // ROOM & SUBJECT COMPATIBILITY SCORE
    // ================================================================================

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
        $ownerMatches = $this->roomOwnerMatchesSubject($room, $subject);
        $allowedByDepartment = $this->roomAllowsSubjectDepartment($room, $subject);

        if (!$allowedByDepartment) {
            return 0;
        }

        if ($ownerMatches) {
            $score += 160;
        }

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

        if ($this->roomIsSpecialized($room)) {
            $score += $requiresLab || $subjectType === 'MAJOR' ? 35 : -20;
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
            ->select($this->roomSelectColumns())
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

        if (!$this->roomAllowsSubjectDepartment($room, $subject)) {
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

        // ── EXPLICIT ROOM OVERRIDE (set via ManageSubjects checkbox) ──────────────────────
        // When the user has explicitly overridden the room type for this subject,
        // honour it unconditionally — before any type-based routing logic below.
        //   Major + checkbox checked → preferred_room_type = 'LECTURE' → lecture rooms only
        //   Minor + checkbox checked → preferred_room_type = 'LAB'     → lab rooms only
        $explicitRoomType = strtoupper(trim((string) ($subject->preferred_room_type ?? '')));

        if ($explicitRoomType === 'LECTURE') {
            // User explicitly said "use lecture room" for this subject.
            // Accept any room that is NOT a lab (lecture, classroom, or general-purpose).
            return $this->roomIsLecture($room);
        }

        if ($explicitRoomType === 'LAB') {
            // User explicitly said "use lab room" for this subject.
            // Must be a real lab; specialization must not conflict.
            if (!$isLab) {
                return false;
            }
            if ($this->roomConflictsWithSubjectDomain($room, $subjectSpecializations)) {
                return false;
            }
            return $hasSpecializationMatch || $this->isAcceptableGeneralLab($room, $subjectSpecializations);
        }
        // ── END EXPLICIT OVERRIDE ─────────────────────────────────────────────────────────

        if ($this->isTechnologyMajor($subject)) {
            return $this->roomIsTechnologyLab($room)
                && !$this->roomConflictsWithSubjectDomain($room, $subjectSpecializations);
        }

        if ($this->isEducationSubject($subject) && !$requiresLab) {
            if (!$this->roomIsLecture($room)) {
                return false;
            }

            // Plain, unflagged general-purpose lecture rooms are always fine.
            if (!$this->roomIsSpecialized($room)) {
                return true;
            }

            // Specialized lecture rooms (is_specialized = true, or tagged with
            // a domain keyword) can still host Education classes when the
            // specialization column is multi-valued and explicitly includes
            // ED/CTE, e.g. "General,ED". Explode on the same separators used
            // throughout this class instead of treating the column as one
            // opaque string, so "ED" is recognized inside "General,ED".
            $roomSpecs = $this->splitSpecialization((string) $room->specialization);

            return $isGeneral
                || in_array('ED', $roomSpecs, true)
                || in_array('CTE', $roomSpecs, true);
        }

        if ($this->isHospitalityPracticalSubject($subject)) {
            return $this->roomIsHospitalityLab($room);
        }

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

   private function compatibleRooms(Collection $rooms, Subject $subject, ?Collection $existingSchedules = null, int $meetingIndex = 0): Collection
    {
        // preferred_room_id is set by ManageRooms when an admin pre-assigns a subject to a room.
        // The scheduler MUST try that room first before falling back to general scoring.
        $preferredRoomId = filled($subject->preferred_room_id ?? null)
            ? (int) $subject->preferred_room_id
            : null;

        // ── Weekly capacity: maximum hours per room per semester week ─────────
        // We filter BEFORE scoring so over-capacity rooms never appear in the
        // candidate list at all — not even as a last-resort fallback.
        $maxWeeklyCapacity = RoomCapacityService::getWeeklyCapacity();
        $subjectWeeklyHours = (float) $subject->duration_hours;

        $strictRooms = $rooms
            ->filter(fn (Room $room) => $this->isRoomCompatible($room, $subject, allowMinorLabFallback: false))
            // ── CAPACITY GUARD ──────────────────────────────────────────────
            // Skip any room whose current scheduled load + this subject's weekly
            // hours would exceed the semester's maximum.
            // Uses the in-memory $existingSchedules when available (fast path);
            // falls back to a DB query when called outside the batch generator.
            ->filter(function (Room $room) use ($existingSchedules, $maxWeeklyCapacity, $subjectWeeklyHours) {
                $currentLoad = RoomCapacityService::getCurrentScheduledRoomLoad($room, $existingSchedules);
                return ($currentLoad + $subjectWeeklyHours) <= $maxWeeklyCapacity;
            })
            ->map(fn (Room $room) => [
                'room' => $room,
                'score' => $this->compatibilityScore($room, $subject),
                'fallback' => false,
                'load' => $this->roomLoad($existingSchedules, $room->id),
            ])
            ->filter(fn (array $candidate) => $candidate['score'] > 0);

        $candidateRooms = $strictRooms;

        // 👇 NEW CTE & GENERAL FALLBACK LOGIC
        // If strict room checks failed and this is a CTE/Education subject, fall back to "General" Lecture rooms
        $subjectDept = strtoupper($subject->department ?? '');
        if ($candidateRooms->isEmpty() && ($subjectDept === 'CTE' || $subjectDept === 'EDUCATION')) {
            $candidateRooms = $rooms
                ->filter(function (Room $room) {
                    // Make sure it's a general purpose Lecture room, never a specialized lab
                    $isLecture = strtoupper($room->room_type ?? '') === 'LECTURE';
                    $roomSpecs = array_map('trim', explode(',', strtoupper($room->specialization ?? '')));
                    $isGeneral = in_array('GENERAL', $roomSpecs) || in_array('COMMON', $roomSpecs);
                    
                    return $isLecture && $isGeneral;
                })
                // Capacity guard on fallback CTE rooms too
                ->filter(function (Room $room) use ($existingSchedules, $maxWeeklyCapacity, $subjectWeeklyHours) {
                    $currentLoad = RoomCapacityService::getCurrentScheduledRoomLoad($room, $existingSchedules);
                    return ($currentLoad + $subjectWeeklyHours) <= $maxWeeklyCapacity;
                })
                ->map(fn (Room $room) => [
                    'room' => $room,
                    'score' => 50, // Give it a safe baseline compatibility score
                    'fallback' => true,
                    'load' => $this->roomLoad($existingSchedules, $room->id),
                ]);
        }

        // Original fallback block for general minor subjects
        if ($candidateRooms->isEmpty() && strtoupper((string) $subject->type) === 'MINOR') {
            $candidateRooms = $rooms
                ->filter(fn (Room $room) => $this->isRoomCompatible($room, $subject, allowMinorLabFallback: true))
                // Capacity guard on minor fallback rooms too
                ->filter(function (Room $room) use ($existingSchedules, $maxWeeklyCapacity, $subjectWeeklyHours) {
                    $currentLoad = RoomCapacityService::getCurrentScheduledRoomLoad($room, $existingSchedules);
                    return ($currentLoad + $subjectWeeklyHours) <= $maxWeeklyCapacity;
                })
                ->map(fn (Room $room) => [
                    'room' => $room,
                    'score' => max(1, $this->compatibilityScore($room, $subject) - 100),
                    'fallback' => true,
                    'load' => $this->roomLoad($existingSchedules, $room->id),
                ]);
        }

        // Keep the internal structural array mapping elements if they are still mapped objects
        // Convert the structural mapped arrays back to standard Eloquent Room collections if your scheduler expects raw models
        $isReturningArrayFormat = $candidateRooms->isNotEmpty() && is_array($candidateRooms->first());
        
        $sorted = $candidateRooms
            ->sort(function (array $a, array $b) use ($preferredRoomId) {
                // Pre-assigned room from ManageRooms always wins, regardless of score
                if ($preferredRoomId) {
                    $aIsPinned = (int) $a['room']->id === $preferredRoomId;
                    $bIsPinned = (int) $b['room']->id === $preferredRoomId;
                    if ($aIsPinned !== $bIsPinned) {
                        return $aIsPinned ? -1 : 1;
                    }
                }

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
            // If the rest of your file loop expects raw Room instances rather than the scoped array wrapping, 
            // you can pluck them out using: return $sorted->pluck('room');
            return $sorted;
        }

        // When a preferred room is pinned at position 0, skip the meetingIndex rotation
        // so it is always tried first — rotation would defeat the purpose of pinning.
        if ($preferredRoomId && (int) ($sorted->first()['room']->id ?? 0) === $preferredRoomId) {
            return $sorted;
        }

        $offset = $meetingIndex % $sorted->count();
        $rotated = $sorted->slice($offset)->concat($sorted->slice(0, $offset))->values();

        return $rotated;
    }
    
    private function applyRoomPreference(Collection $candidateRooms, ?string $preferredRoomHint): Collection
    {
        $hint = strtoupper(trim((string) $preferredRoomHint));

        if ($hint === '' || $candidateRooms->count() <= 1) {
            return $candidateRooms->values();
        }

        return $candidateRooms
            ->sort(function (array $a, array $b) use ($hint) {
                $aScore = $this->roomPreferenceScore($a['room'], $hint);
                $bScore = $this->roomPreferenceScore($b['room'], $hint);

                if ($aScore !== $bScore) {
                    return $bScore <=> $aScore;
                }

                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];
                }

                return ($a['load'] ?? 0) <=> ($b['load'] ?? 0);
            })
            ->values();
    }

    private function roomPreferenceScore(Room $room, string $hint): int
    {
        $roomName = strtoupper((string) $room->room_name);
        $roomType = strtoupper((string) $room->type);
        $specialization = strtoupper((string) $room->specialization);
        $haystack = trim("{$roomName} {$roomType} {$specialization}");

        if ($roomName === $hint) {
            return 100;
        }

        if ($roomType === $hint) {
            return 85;
        }

        if ($specialization === $hint) {
            return 75;
        }

        if (str_contains($haystack, $hint)) {
            return 60;
        }

        $tokens = collect(preg_split('/\s+/', $hint) ?: [])->filter()->values();

        if ($tokens->isNotEmpty() && $tokens->every(fn (string $token) => str_contains($haystack, $token))) {
            return 45;
        }

        return 0;
    }

    private function roomTypeMatches(Room $room, Subject $subject): bool
    {
        $preferred = strtoupper((string) ($subject->preferred_room_type ?? ''));

        if ($preferred !== '') {
            if (str_contains($preferred, 'LECTURE')) {
                return $this->roomIsLecture($room);
            }

            if (str_contains($preferred, 'LAB')) {
                return $this->roomIsLab($room);
            }
        }

        return $this->subjectRequiresLab($subject) === $this->roomIsLab($room);
    }

    private function subjectRequiresLab(Subject $subject): bool
    {
        $explicitRoomType = strtoupper(trim((string) ($subject->preferred_room_type ?? '')));

        // Explicit override from ManageSubjects checkbox is always authoritative.
        if ($explicitRoomType === 'LECTURE') {
            return false; // user said "lecture room" → never a lab subject
        }
        if ($explicitRoomType === 'LAB') {
            return true;  // user said "lab room" → always a lab subject
        }

        if ((bool) ($subject->requires_lab ?? false)) {
            return true;
        }

        $haystack = strtoupper(trim(implode(' ', [
            (string) $subject->type,
            (string) ($subject->subject_type ?? ''),
            (string) $subject->subject_code,
            (string) $subject->description,
            (string) ($subject->specialization ?? ''),
        ])));

        // IMPROVED: removed SYSTEMS / NETWORKING / DATABASE — these false-positive on purely
        // lecture subjects (e.g. "Systems Integration And Architecture", "Database Management"),
        // causing them to be rejected by departments that only have lecture rooms. The
        // requires_lab column and preferred_room_type = 'LAB' remain authoritative for those
        // subjects. PRACTICUM and WORKSHOP added as unambiguous practical-subject keywords.
        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COMPUTER LAB')
            || str_contains($haystack, 'PROGRAMMING')
            || str_contains($haystack, 'PRACTICUM')
            || str_contains($haystack, 'WORKSHOP');
    }

    private function subjectSpecialization(Subject $subject): string
    {
        return $this->subjectSpecializations($subject)[0] ?? '';
    }

    private function roomSpecializations(Room $room): array
    {
        $specialization = strtoupper(trim(implode(',', [
            (string) $room->specialization,
            (string) ($room->department_owner ?? ''),
            (string) ($room->room_type ?? ''),
        ])));

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
            return ! $this->roomIsSpecialized($room);
        }

        return (bool) collect($specializations)->first(fn (string $value) => in_array($value, self::GENERAL_ROOM_SPECIALIZATIONS, true));
    }

    private function roomIsLab(Room $room): bool
    {
        $haystack = $this->roomText($room);

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COM LAB')
            || str_contains($haystack, 'COMPUTER')
            || str_contains($haystack, 'WORKSHOP')
            || str_contains($haystack, 'KITCHEN')
            || str_contains($haystack, 'HOSPITALITY');
    }

    private function roomIsLecture(Room $room): bool
    {
        $haystack = $this->roomText($room);

        return str_contains($haystack, 'LECTURE')
            || str_contains($haystack, 'CLASSROOM')
            || (!$this->roomIsLab($room) && !$this->roomIsSpecialized($room));
    }

    private function roomIsTechnologyLab(Room $room): bool
    {
        $haystack = $this->roomText($room);

        return $this->roomIsLab($room)
            && $this->containsAny($haystack, ['ICT', 'COMPUTER', 'COM LAB', 'WORKSHOP', 'CCS', 'IT', 'ACT']);
    }

    private function roomIsHospitalityLab(Room $room): bool
    {
        $haystack = $this->roomText($room);

        return $this->roomIsLab($room)
            && $this->containsAny($haystack, ['HRM', 'HM', 'TM', 'SHTM', 'KITCHEN', 'HOSPITALITY', 'CULINARY']);
    }

    private function roomIsSpecialized(Room $room): bool
    {
        if ((bool) ($room->is_specialized ?? false)) {
            return true;
        }

        return $this->containsAny($this->roomText($room), [
            'LAB',
            'LABORATORY',
            'COMPUTER',
            'ICT',
            'WORKSHOP',
            'KITCHEN',
            'HOSPITALITY',
            'FORENSIC',
            'BALLISTICS',
        ]);
    }

    private function roomText(Room $room): string
    {
        return strtoupper(trim(implode(' ', [
            (string) ($room->room_name ?? ''),
            (string) ($room->type ?? ''),
            (string) ($room->room_type ?? ''),
            (string) ($room->specialization ?? ''),
            (string) ($room->department_owner ?? ''),
        ])));
    }

    private function subjectText(Subject $subject): string
    {
        return strtoupper(trim(implode(' ', [
            (string) ($subject->type ?? ''),
            (string) ($subject->subject_type ?? ''),
            (string) ($subject->subject_code ?? ''),
            (string) ($subject->description ?? ''),
            (string) ($subject->specialization ?? ''),
            (string) ($subject->major ?? ''),
            (string) ($subject->department ?? ''),
            (string) ($subject->preferred_room_type ?? ''),
        ])));
    }

    private function isTechnologyMajor(Subject $subject): bool
    {
        return strtoupper((string) $subject->type) === 'MAJOR'
            && count(array_intersect($this->subjectDepartmentAliases($subject), ['CCS', 'IT', 'ACT'])) > 0;
    }

    private function isEducationSubject(Subject $subject): bool
    {
        return count(array_intersect($this->subjectDepartmentAliases($subject), ['CTE', 'ED'])) > 0;
    }

    private function isHospitalityPracticalSubject(Subject $subject): bool
    {
        if (count(array_intersect($this->subjectDepartmentAliases($subject), ['SHTM', 'HM', 'TM'])) === 0) {
            return false;
        }

        return $this->subjectRequiresLab($subject)
            || $this->containsAny($this->subjectText($subject), ['PRACTICAL', 'KITCHEN', 'CULINARY', 'LAB', 'HOSPITALITY']);
    }

    private function subjectDepartmentAliases(Subject $subject): array
    {
        return collect([
            Department::normalizeCode((string) $subject->department),
            Department::normalizeCode((string) $subject->major),
            ...Department::aliasesFor((string) $subject->department),
            ...Department::aliasesFor((string) $subject->major),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function roomAllowsSubjectDepartment(Room $room, Subject $subject): bool
    {
        $allowed = $this->roomAllowedDepartments($room);

        if (!$allowed) {
            return true;
        }

        return count(array_intersect($allowed, $this->subjectDepartmentAliases($subject))) > 0;
    }

    private function roomOwnerMatchesSubject(Room $room, Subject $subject): bool
    {
        $owner = Department::normalizeCode((string) ($room->department_owner ?? ''));

        return $owner !== null
            && count(array_intersect(Department::aliasesFor($owner), $this->subjectDepartmentAliases($subject))) > 0;
    }

    private function roomAllowedDepartments(Room $room): array
    {
        $departments = $room->allowed_departments ?? [];

        if (is_string($departments)) {
            $decoded = json_decode($departments, true);
            $departments = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : preg_split('/[,|\/;]+/', $departments);
        }

        return collect($departments ?? [])
            ->map(fn ($code) => Department::normalizeCode((string) $code))
            ->filter()
            ->flatMap(fn (string $code) => Department::aliasesFor($code) ?: [$code])
            ->unique()
            ->values()
            ->all();
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
            && is_numeric($room->capacity)
            && (int) $room->capacity > 0;
    }

    // ================================================================================
    // TIME MANAGEMENT & WINDOWING
    // ================================================================================

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

    public function findAvailableMorningSlots(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingsNeeded = 1): array
    {
        $bounds = Setting::getDayBounds();
        $bounds['end'] = min($bounds['end'], self::LUNCH_START);

        $pattern = $this->findFreshLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded, true);

        return $pattern['placements'] ?? [];
    }

    public function findAvailableAfternoonSlots(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingsNeeded = 1): array
    {
        $bounds = Setting::getDayBounds();
        $bounds['start'] = max($bounds['start'], self::LUNCH_END);

        $pattern = $this->findFreshLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded, true);

        return $pattern['placements'] ?? [];
    }

    public function generateFallbackSchedules(Subject $subject, Collection $rooms, Collection $existingSchedules, int $meetingsNeeded = 1): array
    {
        return match (strtoupper((string) $subject->section)) {
            'A' => $this->findAvailableAfternoonSlots($subject, $rooms, $existingSchedules, $meetingsNeeded),
            'B' => $this->findAvailableMorningSlots($subject, $rooms, $existingSchedules, $meetingsNeeded),
            default => array_merge(
                $this->findAvailableMorningSlots($subject, $rooms, $existingSchedules, $meetingsNeeded),
                $this->findAvailableAfternoonSlots($subject, $rooms, $existingSchedules, $meetingsNeeded)
            ),
        };
    }

    private function minutesPerMeeting(Subject $subject): int
    {
        return max(
            self::SLOT_MINUTES,
            (int) ceil(((float) $subject->duration_hours * 60) / max(1, (int) $subject->meetings_per_week))
        );
    }

    private function sectionWindowPasses(?string $section, array $bounds, bool $allowSessionFallback = true): array
    {
        $strictWindows = $this->sectionWindows($section, $bounds, false);

        if (!$allowSessionFallback || !in_array(strtoupper((string) $section), ['A', 'B'], true)) {
            return $strictWindows ? [$strictWindows] : [];
        }

        $section = strtoupper((string) $section);
        $fallbackWindows = array_values(array_filter(
            $this->sectionWindows($section, $bounds, true),
            fn (array $window) => $section === 'A'
                ? $window['start']->gte(Carbon::parse(self::LUNCH_END))
                : $window['end']->lte(Carbon::parse(self::LUNCH_START))
        ));

        return array_values(array_filter([$strictWindows, $fallbackWindows]));
    }

    private function sectionWindows(?string $section, array $bounds, bool $allowSessionFallback = false): array
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
            'A' => $allowSessionFallback ? [$morning, $afternoon] : [$morning],
            'B' => $allowSessionFallback ? [$afternoon, $morning] : [$afternoon],
            default => [$morning, $afternoon],
        };

        return array_values(array_filter($windows, fn (array $window) => $window['start']->lt($window['end'])));
    }

    private function isSectionFallbackSlot(?string $section, string $start, string $end): bool
    {
        $section = strtoupper((string) $section);
        $slotStart = Carbon::parse($start);
        $slotEnd = Carbon::parse($end);

        return ($section === 'A'
                && $slotStart->gte(Carbon::parse(self::LUNCH_END))
                && $slotEnd->gt(Carbon::parse(self::LUNCH_END)))
            || ($section === 'B'
                && $slotStart->lt(Carbon::parse(self::LUNCH_START))
                && $slotEnd->lte(Carbon::parse(self::LUNCH_START)));
    }

    private function sectionFallbackShouldBeAllowed(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        int $meetingsNeeded
    ): bool {
        if (!in_array(strtoupper((string) $subject->section), ['A', 'B'], true)) {
            return false;
        }

        return !$this->findConsistentRoomAndTime(
            $subject,
            $rooms,
            $existingSchedules,
            $bounds,
            $meetingsNeeded,
            0,
            false
        );
    }

    private function timeFitsAnySectionWindow(Subject $subject, array $bounds, string $start, string $end, bool $allowSessionFallback = false): bool
    {
        if ($this->conflicts->overlapsLunchBreak($start, $end)
            || !$this->conflicts->respectsSectionSession($subject->section, $start, $end, $allowSessionFallback)) {
            return false;
        }

        $slotStart = Carbon::parse($start);
        $slotEnd = Carbon::parse($end);

        foreach ($this->sectionWindows($subject->section, $bounds, $allowSessionFallback) as $window) {
            if ($slotStart->gte($window['start']) && $slotEnd->lte($window['end'])) {
                return true;
            }
        }

        return false;
    }

    private function limitWindowToPreferredRange(array $window, ?string $preferredStart, ?string $preferredEnd): ?array
    {
        if (!$preferredStart && !$preferredEnd) {
            return $window;
        }

        $start = $window['start']->copy();
        $end = $window['end']->copy();

        if ($preferredStart) {
            $start = $start->max(Carbon::parse($preferredStart));
        }

        if ($preferredEnd) {
            $end = $end->min(Carbon::parse($preferredEnd));
        }

        return $start->lt($end)
            ? ['start' => $start, 'end' => $end]
            : null;
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

    private function normalizeDayList(array|string|null $days): array
    {
        if (is_string($days)) {
            $days = preg_split('/[,|\/]+/', $days) ?: [];
        }

        return collect($days ?? [])
            ->map(fn ($day) => Setting::normalizeDayName((string) $day))
            ->filter(fn (?string $day) => $day !== null && in_array($day, $this->activeDays(), true))
            ->unique()
            ->values()
            ->all();
    }

    // IMPROVED: optional $existingSchedules/$subject params let callers rank day patterns by
    // how loaded each day already is for this student group, instead of always trying the
    // same fixed Mon/Wed-first order from DAY_PAIRINGS.
    private function meetingDayPatterns(int $meetings, array $mustIncludeDays = [], ?Collection $existingSchedules = null, ?Subject $subject = null): array
    {
        $activeDays = $this->activeDays();
        $meetings = max(1, min(count($activeDays), $meetings));
        $patterns = array_merge(
            $this->preferredDayPatterns($meetings, $activeDays),
            $this->fallbackDayPatterns($meetings, $activeDays),
            $this->dayCombinations($meetings, $activeDays)
        );

        $unique = [];
        foreach ($patterns as $pattern) {
            $pattern = array_values(array_intersect($activeDays, $pattern));

            if (count($pattern) !== $meetings) {
                continue;
            }

            $unique[implode('|', $pattern)] = $pattern;
        }

        $patterns = array_values($unique);

        // IMPROVED: a single comparator handles both the original mustIncludeDays priority and
        // the new load-based tiebreak, so the load sort can never disturb the mustIncludeDays
        // ordering — it only decides between patterns that already tie on that criterion.
        if ($mustIncludeDays || ($existingSchedules && $subject)) {
            usort($patterns, function (array $a, array $b) use ($mustIncludeDays, $existingSchedules, $subject) {
                if ($mustIncludeDays) {
                    $aContainsAll = empty(array_diff($mustIncludeDays, $a));
                    $bContainsAll = empty(array_diff($mustIncludeDays, $b));

                    if ($aContainsAll !== $bContainsAll) {
                        return $aContainsAll ? -1 : 1;
                    }

                    $aMatches = count(array_intersect($mustIncludeDays, $a));
                    $bMatches = count(array_intersect($mustIncludeDays, $b));

                    if ($aMatches !== $bMatches) {
                        return $bMatches <=> $aMatches;
                    }
                }

                if ($existingSchedules && $subject) {
                    $aLoad = $this->dayPatternLoad($existingSchedules, $subject, $a);
                    $bLoad = $this->dayPatternLoad($existingSchedules, $subject, $b);

                    return $aLoad <=> $bLoad;
                }

                return 0;
            });
        }

        return $patterns;
    }

    private function preferredDayPatterns(int $meetings, array $days): array
    {
        $patterns = self::DAY_PAIRINGS[$meetings] ?? [];

        if ($meetings === 1) {
            $patterns = array_merge($patterns, array_map(fn (string $day) => [$day], $days));
        }

        if ($meetings === 2 && count($days) >= 4) {
            $patterns[] = [$days[0], $days[2]];
            $patterns[] = [$days[1], $days[3]];
        }

        if ($meetings === 3 && count($days) >= 5) {
            $patterns[] = [$days[0], $days[2], $days[4]];
        }

        if ($meetings === 3 && count($days) >= 6) {
            $patterns[] = [$days[1], $days[3], $days[5]];
        }

        return $patterns;
    }

    private function fallbackDayPatterns(int $meetings, array $days): array
    {
        $defaults = match ($meetings) {
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

        if ($meetings === count($days)) {
            $defaults[] = $days;
        }

        return $defaults;
    }

    private function dayCombinations(int $meetings, array $days): array
    {
        $result = [];

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

    private function prioritizedDays(Subject $subject, Collection $existingSchedules, int $meetingIndex): array
    {
        $usedDays = $existingSchedules
            ->where('subject_id', $subject->id)
            ->pluck('day')
            ->all();

        $activeDays = $this->activeDays();
        $unused = array_values(array_diff($activeDays, $usedDays));
        $ordered = array_merge($unused, array_values(array_intersect($activeDays, $usedDays)));
        $offset = $meetingIndex % max(1, count($ordered));

        return array_merge(array_slice($ordered, $offset), array_slice($ordered, 0, $offset));
    }

    // ================================================================================
    // GENERAL UTILITIES
    // ================================================================================

    private function makePairingKey(Subject $subject): string
    {
        return implode('-', [
            'pair',
            $subject->id,
            Str::orderedUuid()->toString(),
        ]);
    }

    private function roomSelectColumns(): array
    {
        $columns = ['id', 'room_name', 'type', 'capacity', 'specialization', 'floor'];

        foreach (['room_type', 'allowed_departments', 'department_owner', 'is_specialized'] as $column) {
            if (Schema::hasColumn('rooms', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function subjectSelectColumns(): array
    {
        $columns = ['id', 'edp_code', 'subject_code', 'description', 'section', 'major', 'year_level', 'department', 'units', 'duration_hours', 'type', 'subject_type', 'specialization', 'meetings_per_week', 'faculty_id'];

        foreach (['requires_lab', 'preferred_room_type', 'preferred_room_id'] as $column) {
            if (Schema::hasColumn('subjects', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function subjectQuery(array $filters)
    {
        return $this->activeSubjectQuery()
            ->select($this->subjectSelectColumns())
            ->where('meetings_per_week', '>', 0)
            // Practicum/OJT subjects are off-campus and must never be room-scheduled.
            ->where(function ($query) {
                $query->where('is_practicum', false)
                      ->orWhereNull('is_practicum');
            })
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

    public function missingRequiredFilters(array $filters): array
    {
        return collect(['department', 'major', 'year_level', 'section'])
            ->filter(fn (string $key) => blank($filters[$key] ?? null))
            ->values()
            ->all();
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

    private function recordFailure(array &$result, Subject|string $subject, string $reason): void
    {
        $subjectCode = $this->cleanText($subject instanceof Subject ? $subject->subject_code : $subject);
        $reason = $this->cleanText($reason);
        $result['failure_reasons'][] = "{$subjectCode}: {$reason}";

        if ($subject instanceof Subject) {
            $result['failed_items'][] = [
                'subject_id' => $subject->id,
                'subject_code' => $this->cleanText($subject->subject_code),
                'subject_name' => $this->cleanText($subject->description),
                'edp_code' => $this->cleanText($subject->edp_code),
                'duration_hours' => $subject->duration_hours,
                'meetings_per_week' => $subject->meetings_per_week,
                'preferred_room_type' => '',
                'preferred_start_time' => '',
                'preferred_days' => [],
                'reason' => $reason,
            ];
        }
    }

    private function cleanText(mixed $value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00C2}", "\u{00A0}", "\xC2\xA0"], ' ', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
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

    private function retrySearchScope(Subject $subject): array
    {
        return [
            'all morning slots',
            'all afternoon slots',
            'alternative paired days',
            'all compatible rooms',
            $subject->faculty_id ? 'current and alternative available faculty' : 'available faculty options',
        ];
    }

    // IMPROVED: replaces the old static retryFailureRecommendations(), which returned the same
    // four boilerplate suggestions regardless of what actually went wrong. This version checks
    // real room, faculty, and slot data so the suggestions point at something the user can act
    // on. match_label is kept so existing UI badges keep working; type/room_id/day/start_time/
    // end_time are new fields that can be wired into the UI later but are safe to ignore for now.
    private function buildSmartRecommendations(
        Subject $subject,
        Collection $rooms,
        Collection $existingSchedules,
        array $bounds,
        int $meetingsNeeded
    ): array {
        $recommendations = [];
        $minutes = $this->minutesPerMeeting($subject);

        // Check 1 — Room: does a compatible room exist at all, and if so, is it actually free anywhere?
        $compatibleRooms = $this->compatibleRooms($rooms, $subject);

        if ($compatibleRooms->isEmpty()) {
            $requiresLab = $this->subjectRequiresLab($subject);
            $recommendations[] = [
                'type' => 'add_room',
                'label' => 'No compatible room found',
                'detail' => 'Subject needs ' . ($requiresLab ? 'LAB' : 'LECTURE') . " ({$subject->major} specialization). Add a compatible room in Manage Rooms.",
                'match_label' => 'BEST MATCH',
            ];
        } elseif ($compatibleRooms->every(fn (array $candidate) => !$this->roomHasFreeSlot($candidate['room'], $existingSchedules, $bounds, $minutes))) {
            $recommendations[] = [
                'type' => 'change_time',
                'label' => 'All compatible rooms are occupied',
                'detail' => 'Rooms available: ' . $compatibleRooms->pluck('room.room_name')->implode(', ') . '. Try a different time slot.',
                'match_label' => 'GOOD MATCH',
            ];
        }

        // Check 2 — Faculty: do they have any availability set, and is any of it actually free?
        if ($subject->faculty_id && $subject->faculty) {
            $faculty = $subject->faculty;
            $availability = $faculty->getAttribute('availability') ?? [];

            $availableDays = collect($this->activeDays())
                ->filter(fn (string $day) => !empty($availability[$day] ?? $availability[strtolower($day)] ?? []))
                ->values();

            if ($availableDays->isEmpty()) {
                $recommendations[] = [
                    'type' => 'set_faculty_availability',
                    'label' => 'Faculty has no availability',
                    'detail' => "{$faculty->full_name} has no availability set for any active scheduling day. Edit their availability in Manage Faculty.",
                    'match_label' => 'GOOD MATCH',
                ];
            } else {
                $freeDays = $availableDays->filter(
                    fn (string $day) => $this->facultyHasFreeSlotOnDay($faculty, $existingSchedules, $bounds, $minutes, $day)
                );

                $recommendations[] = $freeDays->isEmpty()
                    ? [
                        'type' => 'change_time',
                        'label' => 'Faculty availability is fully booked',
                        'detail' => "{$faculty->full_name} has availability on {$availableDays->implode(', ')}, but all of it is already scheduled. Free up time on one of those days.",
                        'match_label' => 'GOOD MATCH',
                    ]
                    : [
                        'type' => 'use_faculty_day',
                        'label' => 'Faculty still has open time',
                        'detail' => "{$faculty->full_name} has free time on: {$freeDays->implode(', ')}.",
                        'match_label' => 'GOOD MATCH',
                    ];
            }
        }

        // Check 3 — the most important one: does a conflict-free slot exist at all right now?
        $hintPattern = $this->findFreshLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, $meetingsNeeded, true);

        if ($hintPattern && !empty($hintPattern['placements'])) {
            $first = $hintPattern['placements'][0];

            $recommendations[] = [
                'type' => 'use_slot',
                'label' => 'A conflict-free slot was found',
                'detail' => 'Try: ' . collect($hintPattern['placements'])->pluck('day')->implode(' / ')
                    . ' at ' . Carbon::parse($first['start'])->format('h:i A')
                    . ' in ' . $first['room']->room_name,
                'match_label' => 'BEST MATCH',
                'room_id' => $first['room']->id,
                'day' => $first['day'],
                'start_time' => $first['start'],
                'end_time' => $first['end'],
            ];
        }

        // Check 4 — would dropping to a single meeting per week unblock it?
        if ($meetingsNeeded > 1) {
            $singleMeeting = $this->findFreshLinkedMeetingPattern($subject, $rooms, $existingSchedules, $bounds, 1, true);

            if ($singleMeeting && !empty($singleMeeting['placements'])) {
                $first = $singleMeeting['placements'][0];

                $recommendations[] = [
                    'type' => 'reduce_meetings',
                    'label' => 'Reduce to 1 meeting per week',
                    'detail' => "No {$meetingsNeeded}-day pair is fully free. A single-meeting slot is available on {$first['day']} at "
                        . Carbon::parse($first['start'])->format('h:i A')
                        . " in {$first['room']->room_name}.",
                    'match_label' => 'FALLBACK',
                ];
            }
        }

        return $recommendations;
    }

    // IMPROVED: does this room have at least one free slot of the needed duration, on any
    // active day within $bounds? Lets buildSmartRecommendations() tell "no compatible room
    // exists" apart from "compatible rooms exist but every one of them is already booked".
    private function roomHasFreeSlot(Room $room, Collection $existingSchedules, array $bounds, int $minutes): bool
    {
        $periodEnd = Carbon::parse($bounds['end'])->subMinutes($minutes);

        if ($periodEnd->lte(Carbon::parse($bounds['start']))) {
            return false;
        }

        foreach ($this->activeDays() as $day) {
            $period = CarbonPeriod::create(Carbon::parse($bounds['start']), self::SLOT_MINUTES . ' minutes', $periodEnd);

            foreach ($period as $slotStart) {
                $slotEnd = $slotStart->copy()->addMinutes($minutes);
                $start = $slotStart->format('H:i:s');
                $end = $slotEnd->format('H:i:s');

                if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                    continue;
                }

                if ($this->roomAvailable($existingSchedules, $room->id, $day, $start, $end)) {
                    return true;
                }
            }
        }

        return false;
    }

    // IMPROVED: does this faculty member have at least one free slot of the needed duration
    // on a specific day within $bounds?
    private function facultyHasFreeSlotOnDay(Faculty $faculty, Collection $existingSchedules, array $bounds, int $minutes, string $day): bool
    {
        $periodEnd = Carbon::parse($bounds['end'])->subMinutes($minutes);

        if ($periodEnd->lte(Carbon::parse($bounds['start']))) {
            return false;
        }

        $period = CarbonPeriod::create(Carbon::parse($bounds['start']), self::SLOT_MINUTES . ' minutes', $periodEnd);

        foreach ($period as $slotStart) {
            $slotEnd = $slotStart->copy()->addMinutes($minutes);
            $start = $slotStart->format('H:i:s');
            $end = $slotEnd->format('H:i:s');

            if ($this->conflicts->overlapsLunchBreak($start, $end)) {
                continue;
            }

            if ($this->facultyAvailable($existingSchedules, (int) $faculty->id, $day, $start, $end)) {
                return true;
            }
        }

        return false;
    }

    private function retryFacultyCandidates(Subject $subject): Collection
    {
        $candidates = collect();

        if ($subject->faculty_id && $subject->faculty) {
            $candidates->push($subject->faculty);
        } elseif (!$subject->faculty_id) {
            $candidates->push(null);
        }

        Faculty::query()
            ->approved()
            ->select('id', 'full_name', 'department', 'faculty_scope', 'max_units', 'availability', 'can_teach_minor')
            ->orderBy('full_name')
            ->get()
            ->filter(fn (Faculty $faculty) => !$subject->faculty_id || (int) $faculty->id !== (int) $subject->faculty_id)
            ->filter(fn (Faculty $faculty) => $faculty->isEligibleForSubject($subject))
            ->each(fn (Faculty $faculty) => $candidates->push($faculty));

        return $candidates->unique(fn ($faculty) => $faculty?->id ?? 'unassigned')->values();
    }
}