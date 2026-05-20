<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Room;
use App\Models\Setting;
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
            'faculty' => $conflicts->where('faculty_id', $facultyId)->first()
                ?: $conflicts->where('user_id', $facultyId)->first(),
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
        
        $scheduledMinutes = Schedule::where('room_id', $roomId)
            ->whereIn('day', $settings['active_days'])
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
