<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const SEMESTER_FIRST = '1st';

    public const SEMESTER_SECOND = '2nd';

    public const SEMESTER_SUMMER = 'Summer';

    public const SEMESTERS = [
        self::SEMESTER_FIRST,
        self::SEMESTER_SECOND,
        self::SEMESTER_SUMMER,
    ];

    public const ALL_SCHEDULE_DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    public const DEFAULT_ACTIVE_DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    protected $fillable = [
        'key',
        'value',
        'config_locked',
        'last_updated_by',
        'last_updated_at',
    ];

    protected $casts = [
        'value' => 'string',
        'config_locked' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key with default fallback
     */
    public static function getValue(string $key, $default = null)
    {
        return self::where('key', $key)->first()?->value ?? $default;
    }

    /**
     * Persist a key/value setting while keeping the key/value table as the source of truth.
     */
    public static function setValue(string $key, mixed $value, ?int $userId = null): self
    {
        return self::updateOrCreate(['key' => $key], [
            'value' => is_array($value) ? json_encode(array_values($value)) : (string) $value,
            'last_updated_by' => $userId,
            'last_updated_at' => now(),
        ]);
    }

    public static function getBoolean(string $key, bool $default = false): bool
    {
        $value = self::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Check if configuration is locked
     */
    public static function isConfigLocked(): bool
    {
        return self::getBoolean('config_locked', true);
    }

    /**
     * Get hard-coded lunch break times
     */
    public static function getLunchBreakTimes(): array
    {
        return [
            'start' => '12:00',
            'end' => '13:00',
        ];
    }

    /**
     * Get master day bounds
     */
    public static function getDayBounds(): array
    {
        return [
            'start' => self::normalizeTime(self::getValue('day_start', '07:00'), '07:00'),
            'end' => self::normalizeTime(self::getValue('day_end', '21:00'), '21:00'),
        ];
    }

    public static function getActiveDays(): array
    {
        $value = self::getValue('active_days');

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $days = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : preg_split('/[,|\/]+/', $value);

            return self::normalizeActiveDays(is_array($days) ? $days : []);
        }

        return self::DEFAULT_ACTIVE_DAYS;
    }

    public static function getActiveDayLabels(): array
    {
        return collect(self::getActiveDays())
            ->mapWithKeys(fn (string $day) => [$day => strtoupper(substr($day, 0, 3))])
            ->all();
    }

    public static function getSlotDurationMinutes(): int
    {
        $value = (int) self::getValue('default_slot_duration', 30);

        return $value > 0 ? $value : 30;
    }

    public static function getScheduleSettings(): array
    {
        $bounds = self::getDayBounds();

        return [
            'active_days' => self::getActiveDays(),
            'start_time' => $bounds['start'],
            'end_time' => $bounds['end'],
            'slot_duration_minutes' => self::getSlotDurationMinutes(),
        ];
    }

    /**
     * Generate a list of valid scheduling time slots from master-grid settings.
     *
     * Slots are produced every `slot_duration_minutes` from `day_start` to
     * `day_end`, with the lunch-break window (12:00-13:00) excluded so no
     * slot starts or ends inside that window.
     *
     * Returns an array of ['value' => 'HH:MM', 'label' => 'g:i A'] maps
     * ready for <select> options in the Edit Workspace dropdowns.
     *
     * @param  bool  $includeLunch  Pass true to keep lunch-break slots.
     * @return array<int, array{value: string, label: string}>
     */
    public static function getTimeSlots(bool $includeLunch = false): array
    {
        $bounds      = self::getDayBounds();
        $slotMinutes = self::getSlotDurationMinutes();
        $lunchBreak  = self::getLunchBreakTimes();

        $cursor     = \Carbon\Carbon::createFromTimeString($bounds['start']);
        $dayEnd     = \Carbon\Carbon::createFromTimeString($bounds['end']);
        $lunchStart = \Carbon\Carbon::createFromTimeString($lunchBreak['start']);
        $lunchEnd   = \Carbon\Carbon::createFromTimeString($lunchBreak['end']);

        $slots = [];

        while ($cursor->lessThanOrEqualTo($dayEnd)) {
            $inLunch = ! $includeLunch
                && $cursor->greaterThanOrEqualTo($lunchStart)
                && $cursor->lessThan($lunchEnd);

            if (! $inLunch) {
                $slots[] = [
                    'value' => $cursor->format('H:i'),
                    'label' => $cursor->format('g:i A'),
                ];
            }

            $cursor->addMinutes($slotMinutes);
        }

        return $slots;
    }

    public static function normalizeActiveDays(array $days): array
    {
        $normalized = collect($days)
            ->map(fn ($day) => self::normalizeDayName((string) $day))
            ->filter(fn (?string $day) => $day !== null)
            ->unique()
            ->values()
            ->all();

        return $normalized ?: self::DEFAULT_ACTIVE_DAYS;
    }

    public static function normalizeDayName(string $day): ?string
    {
        $day = strtolower(trim($day));

        if ($day === '') {
            return null;
        }

        foreach (self::ALL_SCHEDULE_DAYS as $knownDay) {
            if ($day === strtolower($knownDay) || $day === strtolower(substr($knownDay, 0, 3))) {
                return $knownDay;
            }
        }

        return null;
    }

    public static function dayIsActive(string $day): bool
    {
        $normalized = self::normalizeDayName($day);

        return $normalized !== null && in_array($normalized, self::getActiveDays(), true);
    }

    /**
     * Get academic year and semester
     */
    public static function getAcademicPeriod(): array
    {
        $schoolYear = self::getValue('school_year', '2026-2027');
        $semester = self::normalizeSemester(self::getValue('semester', self::SEMESTER_FIRST));

        return [
            'school_year' => $schoolYear,
            'academic_year' => $schoolYear,
            'semester' => $semester,
            'semester_name' => self::getValue('semester_name', self::semesterDisplayName($semester, $schoolYear)),
            'workspace_key' => self::workspaceKey($schoolYear, $semester),
            'year_prefix' => self::academicYearPrefix($schoolYear),
            'edp_prefix' => self::edpTermPrefix($schoolYear, $semester),
        ];
    }

    public static function currentWorkspace(): array
    {
        return self::getAcademicPeriod();
    }

    public static function normalizeSemester(?string $semester): string
    {
        $semester = trim((string) $semester);

        return match (strtolower($semester)) {
            '1', '1st', 'first', 'first semester', '1st semester' => self::SEMESTER_FIRST,
            '2', '2nd', 'second', 'second semester', '2nd semester' => self::SEMESTER_SECOND,
            'summer', 'summer semester' => self::SEMESTER_SUMMER,
            default => self::SEMESTER_FIRST,
        };
    }

    public static function semesterLabel(?string $semester): string
    {
        return match (self::normalizeSemester($semester)) {
            self::SEMESTER_FIRST => '1st Semester',
            self::SEMESTER_SECOND => '2nd Semester',
            self::SEMESTER_SUMMER => 'Summer',
            default => '1st Semester',
        };
    }

    public static function semesterCode(?string $semester): string
    {
        return match (self::normalizeSemester($semester)) {
            self::SEMESTER_FIRST => '1ST',
            self::SEMESTER_SECOND => '2ND',
            self::SEMESTER_SUMMER => 'SUM',
            default => '1ST',
        };
    }

    public static function semesterEdpDigit(?string $semester): string
    {
        return match (self::normalizeSemester($semester)) {
            self::SEMESTER_FIRST => '1',
            self::SEMESTER_SECOND => '2',
            self::SEMESTER_SUMMER => '3',
            default => '1',
        };
    }

    public static function semesterDisplayName(?string $semester, ?string $schoolYear): string
    {
        return trim(self::semesterLabel($semester).' '.($schoolYear ?: ''));
    }

    public static function workspaceKey(?string $schoolYear = null, ?string $semester = null): string
    {
        $schoolYear = trim((string) ($schoolYear ?: self::getValue('school_year', '2026-2027')));
        $semester = self::normalizeSemester($semester ?: self::getValue('semester', self::SEMESTER_FIRST));

        return $schoolYear.'_'.$semester;
    }

    public static function academicYearPrefix(?string $schoolYear): string
    {
        if (preg_match('/^(\d{4})-\d{4}$/', (string) $schoolYear, $matches)) {
            return substr($matches[1], -2);
        }

        return substr((string) now()->year, -2);
    }

    public static function edpTermPrefix(?string $schoolYear = null, ?string $semester = null): string
    {
        return self::academicYearPrefix($schoolYear ?: self::getValue('school_year', '2026-2027'))
            .self::semesterEdpDigit($semester ?: self::getValue('semester', self::SEMESTER_FIRST));
    }

    public static function edpCodePrefix(string $major, int $yearLevel, ?string $schoolYear = null, ?string $semester = null): string
    {
        $major = strtoupper(trim($major ?: 'GEN'));
        $yearLevel = max(1, min(9, $yearLevel));

        return "{$major}-".self::edpTermPrefix($schoolYear, $semester).$yearLevel;
    }

    public static function nextAcademicYear(string $schoolYear): string
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $matches)) {
            $start = (int) now()->year;

            return $start.'-'.($start + 1);
        }

        $start = (int) $matches[1] + 1;

        return $start.'-'.($start + 1);
    }

    public static function nextAcademicPeriod(?string $semester = null, ?string $schoolYear = null): array
    {
        $semester = self::normalizeSemester($semester ?? self::getValue('semester', self::SEMESTER_FIRST));
        $schoolYear ??= self::getValue('school_year', '2026-2027');

        if ($semester === self::SEMESTER_FIRST) {
            $nextSemester = self::SEMESTER_SECOND;
            $nextSchoolYear = $schoolYear;
        } else {
            $nextSemester = self::SEMESTER_FIRST;
            $nextSchoolYear = self::nextAcademicYear($schoolYear);
        }

        return [
            'semester' => $nextSemester,
            'school_year' => $nextSchoolYear,
            'academic_year' => $nextSchoolYear,
            'semester_name' => self::semesterDisplayName($nextSemester, $nextSchoolYear),
            'workspace_key' => self::workspaceKey($nextSchoolYear, $nextSemester),
            'year_prefix' => self::academicYearPrefix($nextSchoolYear),
            'edp_prefix' => self::edpTermPrefix($nextSchoolYear, $nextSemester),
        ];
    }

    private static function normalizeTime(?string $time, string $default): string
    {
        if (! $time) {
            return $default;
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $default;
        }
    }
}