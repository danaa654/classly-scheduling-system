<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
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
        Subject $subject,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): array {
        if (!$subject->faculty_id) {
            return $this->pass();
        }

        $subject->loadMissing('faculty:id,full_name');

        $conflict = $this->baseScheduleQuery($day, $startTime, $endTime, $ignoreScheduleId)
            ->whereHas('subject', function (Builder $query) use ($subject) {
                $query->where('faculty_id', $subject->faculty_id);
            })
            ->with(['subject:id,subject_code,faculty_id', 'room:id,room_name'])
            ->first();

        if (!$conflict) {
            return $this->pass();
        }

        $facultyName = $subject->faculty?->full_name ?? 'Faculty Member';
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
        return $this->normalizeTime($newStart) < $this->normalizeTime($existingEnd)
            && $this->normalizeTime($newEnd) > $this->normalizeTime($existingStart);
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
