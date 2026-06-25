<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\ScheduleRevisionRequest;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    /**
     * Check for any scheduling conflicts.
     * Uses strict inequality to allow classes to "touch" (e.g., one ends at 10:30, next starts at 10:30)
     */
    public function checkConflict($roomId, $facultyId, $section, $day, $startTime, $endTime, $ignoreId = null)
    {
        $query = Schedule::activeTerm()
            ->where('day', $day)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($inner) use ($startTime, $endTime) {
                    // Logic: A conflict exists if (StartA < EndB) AND (EndA > StartB)
                    $inner->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $conflicts = $query->get();

        return [
            'room'    => $conflicts->where('room_id', $roomId)->first(),
            'faculty' => (!is_null($facultyId)) ? $conflicts->where('faculty_id', $facultyId)->first() : null,
            'section' => $conflicts->where('section', $section)->first(),
        ];
    }

    /**
     * Calculate Room Utilization Percentage.
     * Prevents negative values and caps result at 100%.
     */
    public function getRoomLoad($roomId)
    {
        $settings = Setting::getScheduleSettings();
        $minutesPerDay = Carbon::parse($settings['start_time'])
            ->diffInMinutes(Carbon::parse($settings['end_time']));
        $totalAvailableMinutes = $minutesPerDay * max(1, count($settings['active_days']));
        
        $scheduledMinutes = Schedule::activeTerm()
            ->where('room_id', $roomId)
            ->whereIn('day', $settings['active_days'])
            // Practicum/OJT subjects have no physical room; exclude them from utilization.
            ->whereHas('subject', function ($query) {
                $query->where(function ($q) {
                    $q->where('is_practicum', false)
                      ->orWhereNull('is_practicum');
                });
            })
            ->get()
            ->sum(function ($schedule) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);
                
                // use absolute value to prevent negative durations
                return abs($end->diffInMinutes($start));
            });

        if ($totalAvailableMinutes <= 0) {
            return 0;
        }

        $percentage = ($scheduledMinutes / $totalAvailableMinutes) * 100;
        
        // Ensure result is mathematically sound (between 0 and 100)
        return max(0, min(100, round($percentage, 1)));
    }

    public function generateRevisionRequest(
        Subject $subject,
        ?Faculty $currentFaculty,
        Faculty $requestedFaculty,
        array $scheduleIds,
        User $requester,
        string $reason
    ): ScheduleRevisionRequest {
        $period = Setting::getAcademicPeriod();

        return ScheduleRevisionRequest::create([
            'subject_id' => $subject->id,
            'requested_by' => $requester->id,
            'current_faculty_id' => $currentFaculty?->id,
            'requested_faculty_id' => $requestedFaculty->id,
            'schedule_ids' => array_values(array_unique(array_map('intval', $scheduleIds))),
            'reason' => trim($reason),
            'status' => ScheduleRevisionRequest::STATUS_PENDING,
            'semester' => $period['semester'],
            'school_year' => $period['school_year'],
            'workspace_key' => $period['workspace_key'],
        ]);
    }

    public function approveRevisionRequest(ScheduleRevisionRequest $request, User $reviewer): void
    {
        DB::transaction(function () use ($request, $reviewer) {
            if ($request->status !== ScheduleRevisionRequest::STATUS_PENDING) {
                return;
            }

            $scheduleIds = collect($request->schedule_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            Schedule::activeTerm($request->semester, $request->school_year)
                ->when($scheduleIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $scheduleIds))
                ->when($scheduleIds->isEmpty(), fn ($query) => $query->where('subject_id', $request->subject_id))
                ->where('status', Schedule::STATUS_FINALIZED)
                ->update(['faculty_id' => $request->requested_faculty_id]);

            $request->update([
                'status' => ScheduleRevisionRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function rejectRevisionRequest(ScheduleRevisionRequest $request, User $reviewer, ?string $note = null): void
    {
        if ($request->status !== ScheduleRevisionRequest::STATUS_PENDING) {
            return;
        }

        $request->update([
            'status' => ScheduleRevisionRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);
    }
}