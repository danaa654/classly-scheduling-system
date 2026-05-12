<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Faculty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ScheduleConflictService
{
    public function checkRoomConflict(
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where('room_id', $roomId)
            ->with(['subject:id,subject_code', 'room:id,room_name'])
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        return [
            'status' => false,
            'type' => 'error',
            'conflict_type' => 'ROOM_CONFLICT',
            'title' => 'Room Already Occupied',
            'message' => "Room {$conflict->room?->room_name} is already booked for {$conflict->subject?->subject_code} during this time.",
            'details' => $this->details($conflict),
        ];
    }

    public function checkFacultyConflict(
        Subject|int $subject,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        $facultyId = $subject instanceof Subject ? $subject->faculty_id : $subject;

        if (!$facultyId) {
            return $this->pass();
        }

        $faculty = $subject instanceof Subject
            ? $subject->loadMissing('faculty:id,full_name')->faculty
            : \App\Models\Faculty::select('id', 'full_name')->find($facultyId);

        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->where('faculty_id', $facultyId)
            ->with(['subject:id,subject_code', 'room:id,room_name'])
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $facultyName = $faculty?->full_name ?? 'Faculty Member';
        $subjectCode = $conflict->subject?->subject_code ?? 'another subject';
        $roomName = $conflict->room?->room_name ?? 'Unknown Room';

        return [
            'status' => false,
            'type' => 'error',
            'conflict_type' => 'FACULTY_CONFLICT',
            'title' => 'Faculty Member Already Teaching',
            'message' => "Professor {$facultyName} is already teaching {$subjectCode} in Room {$roomName} during this time.",
            'details' => array_merge($this->details($conflict), [
                'faculty_name' => $facultyName,
            ]),
        ];
    }

    public function checkFacultyAvailability(
        Faculty $faculty,
        string $day,
        string $startTime,
        string $endTime
    ): array {
        $availability = $faculty->getAttribute('availability');

        if (empty($availability) || !is_array($availability)) {
            return $this->pass();
        }

        $dayWindows = $availability[$day] ?? $availability[strtolower($day)] ?? [];

        if (empty($dayWindows)) {
            return [
                'status' => false,
                'type' => 'error',
                'conflict_type' => 'FACULTY_AVAILABILITY',
                'title' => 'Faculty Not Available',
                'message' => "Professor {$faculty->full_name} is not available on {$day}.",
                'details' => ['faculty_name' => $faculty->full_name, 'day' => $day],
            ];
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

        return [
            'status' => false,
            'type' => 'error',
            'conflict_type' => 'FACULTY_AVAILABILITY',
            'title' => 'Faculty Not Available',
            'message' => "Professor {$faculty->full_name} is not available during this time.",
            'details' => [
                'faculty_name' => $faculty->full_name,
                'day' => $day,
                'requested_start' => Carbon::parse($startTime)->format('h:i A'),
                'requested_end' => Carbon::parse($endTime)->format('h:i A'),
            ],
        ];
    }

    public function checkSectionConflict(
        Subject $subject,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->whereHas('subject', function (Builder $query) use ($subject) {
                $query->where('department', $subject->department)
                    ->where('major', $subject->major)
                    ->where('year_level', $subject->year_level)
                    ->where('section', $subject->section);
            })
            ->with(['subject:id,subject_code,department,major,year_level,section', 'room:id,room_name'])
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $group = $this->studentGroup($subject);

        return [
            'status' => false,
            'type' => 'error',
            'conflict_type' => 'SECTION_CONFLICT',
            'title' => 'Student Group Already Scheduled',
            'message' => "Section conflict detected for {$group}. Students already have another scheduled subject during this time.",
            'details' => array_merge($this->details($conflict), [
                'group' => $group,
            ]),
        ];
    }

    public function checkCurriculumConflict(Subject $subject, Room $room): array
    {
        if (!$this->subjectRequiresLab($subject) || $this->roomIsLab($room)) {
            return $this->pass();
        }

        return [
            'status' => true,
            'type' => 'warning',
            'conflict_type' => 'CURRICULUM_CONFLICT',
            'title' => 'Room Specialization Warning',
            'message' => 'Warning: This subject requires a laboratory room.',
            'details' => [
                'subject_type' => $subject->type,
                'room_type' => $room->type,
                'room_name' => $room->room_name,
            ],
        ];
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
        return $this->hasTimeOverlap($startTime, $endTime, '12:00:00', '13:00:00');
    }

    public function respectsSectionSession(?string $section, string $startTime, string $endTime): bool
    {
        $section = strtoupper((string) $section);
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $lunchStart = Carbon::parse('12:00:00');
        $lunchEnd = Carbon::parse('13:00:00');

        if ($section === 'A') {
            return $start->lt($lunchStart) && $end->between($start, $lunchStart, true);
        }

        if ($section === 'B') {
            return $start->between($lunchEnd, Carbon::parse('23:59:59'), true) && $end->gt($lunchEnd);
        }

        return true;
    }

    public function baseScheduleQuery(
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): Builder {
        return Schedule::query()
            ->where('day', $day)
            ->when($ignoreScheduleId, fn (Builder $query) => $query->whereKeyNot($ignoreScheduleId))
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $this->normalizeTime($endTime))
                    ->where('end_time', '>', $this->normalizeTime($startTime));
            });
    }

    private function pass(): array
    {
        return [
            'status' => true,
            'type' => 'success',
            'message' => null,
        ];
    }

    private function details(Schedule $schedule): array
    {
        return [
            'conflicting_schedule_id' => $schedule->id,
            'conflicting_subject' => $schedule->subject?->subject_code ?? 'Unknown',
            'conflicting_room' => $schedule->room?->room_name ?? 'Unknown',
            'conflicting_start' => Carbon::parse($schedule->start_time)->format('h:i A'),
            'conflicting_end' => Carbon::parse($schedule->end_time)->format('h:i A'),
            'conflicting_day' => $schedule->day,
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

        $haystack = strtoupper(trim("{$subject->type} {$subject->subject_code} {$subject->description}"));

        return str_contains($haystack, 'LAB')
            || str_contains($haystack, 'LABORATORY')
            || str_contains($haystack, 'COMPUTER LAB');
    }

    private function roomIsLab(Room $room): bool
    {
        $roomType = strtoupper((string) $room->type);
        $specialization = strtoupper((string) $room->specialization);

        return str_contains($roomType, 'LAB')
            || str_contains($roomType, 'LABORATORY')
            || str_contains($specialization, 'LAB');
    }

    private function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
