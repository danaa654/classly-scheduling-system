<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Room;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Check for any scheduling conflicts.
     */
    public function checkConflict($roomId, $facultyId, $section, $day, $startTime, $endTime, $ignoreId = null)
    {
        $query = Schedule::where('day', $day)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($inner) use ($startTime, $endTime) {
                      $inner->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                  });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $conflicts = $query->get();

        return [
            'room' => $conflicts->where('room_id', $roomId)->first(),
            'faculty' => $conflicts->where('user_id', $facultyId)->first(),
            'section' => $conflicts->where('section', $section)->first(),
        ];
    }

    /**
     * Calculate Room Utilization Percentage.
     * Assumes a standard 12-hour school day (8 AM - 8 PM).
     */
    public function getRoomLoad($roomId)
    {
        $totalAvailableMinutes = 12 * 60 * 6; // 12 hours * 60 mins * 6 days
        
        $scheduledMinutes = Schedule::where('room_id', $roomId)
            ->get()
            ->sum(function ($schedule) {
                $start = Carbon::parse($schedule->start_time);
                $end = Carbon::parse($schedule->end_time);
                return $end->diffInMinutes($start);
            });

        $percentage = ($scheduledMinutes / $totalAvailableMinutes) * 100;
        
        return round($percentage, 1);
    }
}