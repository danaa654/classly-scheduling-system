<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Subject;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    /**
     * Check all schedules and their relationships
     */
    public function checkSchedules()
    {
        $schedules = Schedule::with(['subject', 'room'])->get();

        return response()->json([
            'total_schedules' => $schedules->count(),
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'subject_id' => $schedule->subject_id,
                    'subject_code' => $schedule->subject?->subject_code ?? 'NULL',
                    'subject_department' => $schedule->subject?->department ?? 'NULL',
                    'subject_year_level' => $schedule->subject?->year_level ?? 'NULL',
                    'section' => $schedule->section,
                    'day' => $schedule->day,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room_id' => $schedule->room_id,
                    'room_name' => $schedule->room?->room_name ?? 'NULL',
                    'subject_exists' => $schedule->subject ? true : false,
                ];
            }),
        ]);
    }

    /**
     * Check block schedule for specific dept/year/section
     */
    public function checkBlockSchedule(Request $request)
    {
        $dept = $request->query('dept', 'IT');
        $year = $request->query('year', '1');
        $section = $request->query('section', 'A');

        // Get all schedules for this section
        $allSchedules = Schedule::query()
            ->whereIn('status', [
                Schedule::STATUS_PARTIAL,
                Schedule::STATUS_FACULTY_ASSIGNED,
                Schedule::STATUS_FINALIZED,
            ])
            ->where('section', $section)
            ->with(['subject', 'room'])
            ->get();

        // Filter by department and year
        $filteredSchedules = $allSchedules->filter(function ($schedule) use ($dept, $year) {
            if (!$schedule->subject) {
                return false;
            }
            return (
                $schedule->subject->department === $dept
                || $schedule->subject->major === $dept
            ) && (int)$schedule->subject->year_level === (int)$year;
        });

        // Group by day
        $grouped = $filteredSchedules->groupBy('day');

        return response()->json([
            'query' => [
                'department' => $dept,
                'year_level' => $year,
                'section' => $section,
            ],
            'total_schedules_in_section' => $allSchedules->count(),
            'matching_schedules' => $filteredSchedules->count(),
            'grouped_by_day' => $grouped->keys(),
            'details' => $grouped->map(function ($daySchedules) {
                return $daySchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'subject_code' => $schedule->subject->subject_code,
                        'subject_department' => $schedule->subject->department,
                        'subject_year_level' => $schedule->subject->year_level,
                        'time' => $schedule->start_time . ' - ' . $schedule->end_time,
                        'room' => $schedule->room->room_name ?? 'N/A',
                        'status' => $schedule->status,
                        'pairing_key' => $schedule->pairing_key,
                    ];
                })->toArray();
            })->toArray(),
        ]);
    }

    /**
     * Check all subjects in a department
     */
    public function checkSubjects(Request $request)
    {
        $dept = $request->query('dept', 'IT');

        $subjects = Subject::where('department', $dept)
            ->get()
            ->map(function ($subject) {
                $scheduleCount = $subject->schedules()->count();
                return [
                    'id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'description' => $subject->description,
                    'department' => $subject->department,
                    'year_level' => $subject->year_level,
                    'major' => $subject->major,
                    'section' => $subject->section,
                    'meetings_per_week' => $subject->meetings_per_week,
                    'schedule_count' => $scheduleCount,
                    'schedules' => $subject->schedules()
                        ->with('room')
                        ->get()
                        ->map(function ($sched) {
                            return [
                                'day' => $sched->day,
                                'time' => $sched->start_time . ' - ' . $sched->end_time,
                                'section' => $sched->section,
                                'room' => $sched->room->room_name ?? 'N/A',
                            ];
                        })->toArray(),
                ];
            });

        return response()->json([
            'department' => $dept,
            'total_subjects' => $subjects->count(),
            'subjects' => $subjects->toArray(),
        ]);
    }

    /**
     * Check specific subject details
     */
    public function checkSubjectDetail($subjectId)
    {
        $subject = Subject::find($subjectId);

        if (!$subject) {
            return response()->json(['error' => 'Subject not found'], 404);
        }

        $schedules = $subject->schedules()->with('room')->get();

        return response()->json([
            'subject' => [
                'id' => $subject->id,
                'code' => $subject->subject_code,
                'description' => $subject->description,
                'department' => $subject->department,
                'year_level' => $subject->year_level,
                'major' => $subject->major,
                'section' => $subject->section,
            ],
            'schedules_count' => $schedules->count(),
            'schedules' => $schedules->map(function ($sched) {
                return [
                    'id' => $sched->id,
                    'day' => $sched->day,
                    'start_time' => $sched->start_time,
                    'end_time' => $sched->end_time,
                    'section' => $sched->section,
                    'room_id' => $sched->room_id,
                    'room_name' => $sched->room->room_name ?? 'N/A',
                ];
            })->toArray(),
        ]);
    }

    /**
     * Get quick summary of all data
     */
    public function summary()
    {
        $totalSchedules = Schedule::count();
        $totalSubjects = Subject::count();
        $departmentsWithSchedules = Schedule::with('subject')
            ->get()
            ->groupBy('subject.department')
            ->keys();

        $byDept = [];
        foreach (['IT', 'ACT', 'ED', 'HM', 'TM', 'FB', 'LD', 'QD'] as $dept) {
            $count = Schedule::whereHas('subject', function ($q) use ($dept) {
                $q->where('department', $dept);
            })->count();
            if ($count > 0) {
                $byDept[$dept] = $count;
            }
        }

        return response()->json([
            'total_schedules' => $totalSchedules,
            'total_subjects' => $totalSubjects,
            'schedules_by_department' => $byDept,
            'departments_with_schedules' => $departmentsWithSchedules->toArray(),
            'endpoints' => [
                '/diagnostic/summary' => 'This summary',
                '/diagnostic/schedules' => 'All schedules with relationships',
                '/diagnostic/block?dept=IT&year=1&section=A' => 'Filter schedules by dept/year/section',
                '/diagnostic/subjects?dept=IT' => 'All subjects in a department',
                '/diagnostic/subject/{id}' => 'Details of a specific subject',
            ],
        ]);
    }
}
