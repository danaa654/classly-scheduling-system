<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleReportService
{
    /**
     * Department → Major label mapping (mirrors ManageSubjects).
     */
    private array $majorLabels = [
        'CCS'  => ['IT' => 'Information Technology', 'ACT' => 'Assistive Computer Technology'],
        'SHTM' => ['HM' => 'Hospitality Management', 'TM' => 'Tourism Management'],
        'COC'  => ['FB' => 'Forensic Biology', 'LD' => 'Lie Detection', 'QD' => 'Questioned Documents'],
        'CTE'  => ['ED' => 'Education'],
    ];

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Build the full report data array for a Preliminary Schedule.
     *
     * @param  array{
     *     department?: string,
     *     major?: string,
     *     year_level?: int|string,
     *     section?: string,
     *     subject_types?: string[],
     * } $filters
     */
    public function buildPreliminaryReport(array $filters): array
    {
        $period = Setting::getAcademicPeriod();

        $query = Subject::activeTerm($period['semester'], $period['school_year'])
            ->with([
                // Get all schedules for this subject in the active term,
                // each eagerly loading their assigned faculty and room.
                'schedules' => fn ($q) => $q
                    ->activeTerm()
                    ->with([
                        'faculty:id,full_name',
                        'room:id,room_name',
                    ])
                    ->orderByRaw("FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
                    ->orderBy('start_time'),

                // Subject-level faculty pre-assignment (Faculty Loading)
                'faculty:id,full_name',

                // Subject-level preferred room preference
                'preferredRoom:id,room_name',
            ]);

        // ── Apply filters ─────────────────────────────────────────────────
        if (! empty($filters['department'])) {
            $query->where('department', strtoupper($filters['department']));
        }

        if (! empty($filters['major'])) {
            $query->where('major', strtoupper($filters['major']));
        }

        if (! empty($filters['year_level']) && (int) $filters['year_level'] > 0) {
            $query->where('year_level', (int) $filters['year_level']);
        }

        if (! empty($filters['section'])) {
            $query->where('section', strtoupper($filters['section']));
        }

        if (! empty($filters['subject_types']) && count($filters['subject_types']) === 1) {
            $type = $filters['subject_types'][0];
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)
                  ->orWhere('type', ucfirst(strtolower($type)));
            });
        }

        $subjects = $query
            ->orderBy('department')
            ->orderBy('major')
            ->orderByRaw('CAST(year_level AS UNSIGNED)')
            ->orderBy('section')
            ->orderBy('subject_code')
            ->get();

        // ── Map to display rows ───────────────────────────────────────────
        $rows = $subjects->map(fn (Subject $subject) => $this->buildRow($subject));

        return [
            'rows'    => $rows->all(),
            'period'  => $period,
            'filters' => $filters,
            'meta'    => [
                'total'        => $rows->count(),
                'tba_count'    => $rows->filter(fn ($r) => $r['has_tba'])->count(),
                'ready_count'  => $rows->filter(fn ($r) => ! $r['has_tba'])->count(),
                'generated_at' => now()->format('F d, Y h:i A'),
                'generated_by' => auth()->user()?->name ?? 'System',
                'role_label'   => $this->roleLabel(auth()->user()?->role),
            ],
        ];
    }

    /**
     * Return distinct sections available for the given department / major / year filter.
     * Used to populate the Section dropdown reactively.
     */
    public function getAvailableSections(
        ?string $department,
        ?string $major,
        int|string|null $yearLevel
    ): Collection {
        $period = Setting::getAcademicPeriod();

        $query = Subject::activeTerm($period['semester'], $period['school_year']);

        if (! empty($department)) {
            $query->where('department', strtoupper($department));
        }

        if (! empty($major)) {
            $query->where('major', strtoupper($major));
        }

        if (! empty($yearLevel) && (int) $yearLevel > 0) {
            $query->where('year_level', (int) $yearLevel);
        }

        return $query->distinct()->pluck('section')->filter()->sort()->values();
    }

    /**
     * Return distinct majors for a given department in the active term.
     */
    public function getAvailableMajors(?string $department): Collection
    {
        if (empty($department)) {
            return collect();
        }

        $period = Setting::getAcademicPeriod();

        return Subject::activeTerm($period['semester'], $period['school_year'])
            ->where('department', strtoupper($department))
            ->distinct()
            ->pluck('major')
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Human-readable label for a major code.
     */
    public function majorLabel(string $department, string $major): string
    {
        $dept = strtoupper($department);
        $maj  = strtoupper($major);

        return $this->majorLabels[$dept][$maj] ?? $maj;
    }

    /**
     * Human-readable year-level label.
     */
    public static function yearLabel(int|string $year): string
    {
        return match ((int) $year) {
            1       => 'First Year',
            2       => 'Second Year',
            3       => 'Third Year',
            4       => 'Fourth Year',
            default => 'Year ' . $year,
        };
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function buildRow(Subject $subject): array
    {
        $schedules = $subject->schedules;

        // ── Faculty resolution: schedule → subject pre-assignment ─────────
        $scheduleFaculty = $schedules->map(fn ($s) => $s->faculty)->filter()->first();
        $faculty         = $scheduleFaculty ?? $subject->faculty ?? null;
        $facultyName     = $faculty?->full_name ?? 'TBA';

        // ── Room resolution: schedule → preferred room ────────────────────
        $scheduledRoom = $schedules
            ->filter(fn ($s) => $s->room !== null)
            ->map(fn ($s) => $s->room)
            ->first();
        $room     = $scheduledRoom ?? $subject->preferredRoom ?? null;
        $roomName = $room?->room_name ?? 'TBA';

        // ── Day / Time resolution ─────────────────────────────────────────
        $scheduledSessions = $schedules->filter(fn ($s) => ! empty($s->day));

        $dayDisplay  = 'TBA';
        $timeDisplay = 'TBA';

        if ($scheduledSessions->isNotEmpty()) {
            // Combine unique days (e.g. "Monday / Wednesday" for TTh subjects)
            $dayDisplay = $scheduledSessions
                ->pluck('day')
                ->unique()
                ->implode(' / ');

            // Show time from the first session
            $first = $scheduledSessions->first();
            $timeDisplay = $this->formatTimeRange($first->start_time, $first->end_time);
        }

        // ── Status: show the most progressed schedule status ──────────────
        $statusRaw = $schedules->sortByDesc(fn ($s) => $this->statusWeight($s->status))
            ->first()?->status ?? 'draft';

        $hasTba = $facultyName === 'TBA' || $roomName === 'TBA' || $dayDisplay === 'TBA';

        return [
            'subject_code' => $subject->subject_code ?? '—',
            'edp_code'     => $subject->edp_code ?? '—',
            'description'  => $subject->description ?? '—',
            'units'        => (int) ($subject->units ?? 0),
            'type'         => ucfirst(strtolower($subject->type ?? 'Major')),
            'department'   => strtoupper($subject->department ?? ''),
            'major'        => strtoupper($subject->major ?? ''),
            'year_level'   => (int) ($subject->year_level ?? 0),
            'section'      => strtoupper($subject->section ?? ''),
            'faculty'      => $facultyName,
            'day'          => $dayDisplay,
            'time'         => $timeDisplay,
            'room'         => $roomName,
            'status_raw'   => $statusRaw,
            'status_label' => $this->reportStatusLabel($statusRaw, $hasTba),
            'has_tba'      => $hasTba,
        ];
    }

    private function formatTimeRange(mixed $startTime, mixed $endTime): string
    {
        if (! $startTime || ! $endTime) {
            return 'TBA';
        }

        try {
            return Carbon::parse($startTime)->format('h:i A')
                . ' – '
                . Carbon::parse($endTime)->format('h:i A');
        } catch (\Exception) {
            return 'TBA';
        }
    }

    private function statusWeight(string $status): int
    {
        return match ($status) {
            'finalized'          => 6,
            'pending_generation' => 5,
            'faculty_locked'     => 4,
            'faculty_assigned'   => 3,
            'partial'            => 2,
            'draft'              => 1,
            default              => 0,
        };
    }

    private function reportStatusLabel(string $status, bool $hasTba): string
    {
        if ($hasTba) {
            return 'PRELIMINARY';
        }

        return match ($status) {
            'finalized'          => 'FINALIZED',
            'pending_generation' => 'PRELIMINARY',
            'faculty_locked'     => 'PRELIMINARY',
            'faculty_assigned'   => 'PRELIMINARY',
            default              => 'PRELIMINARY',
        };
    }

    private function roleLabel(?string $role): string
    {
        return match (strtolower($role ?? '')) {
            'admin'          => 'Administrator',
            'registrar'      => 'Registrar',
            'dean'           => 'Dean',
            'oic'            => 'OIC',
            'associate_dean' => 'Associate Dean',
            default          => 'Staff',
        };
    }
}