<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Room;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Check for any scheduling conflicts.
     * Uses strict inequality to allow classes to "touch" (e.g., one ends at 10:30, next starts at 10:30)
     */
    public function checkConflict($roomId, $facultyId, $section, $day, $startTime, $endTime, $ignoreId = null)
    {
        $query = Schedule::where('day', $day)
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
            'faculty' => $conflicts->where('user_id', $facultyId)->first(),
            'section' => $conflicts->where('section', $section)->first(),
        ];
    }

    /**
     * Calculate Room Utilization Percentage.
     * Prevents negative values and caps result at 100%.
     */
    public function getRoomLoad($roomId)
    {
        // Define your operational window (e.g., 7:30 AM to 5:30 PM = 10 hours)
        // Based on your code: 12 hours * 60 mins * 6 days = 4,320 total mins
        $totalAvailableMinutes = 12 * 60 * 6; 
        
        $scheduledMinutes = Schedule::where('room_id', $roomId)
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
}