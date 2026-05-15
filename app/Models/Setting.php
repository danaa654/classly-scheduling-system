<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
        return [
            'school_year' => self::getValue('school_year', '2026-2027'),
            'semester' => self::getValue('semester', '1st Semester'),
        ];
    }

    private static function normalizeTime(?string $time, string $default): string
    {
        if (!$time) {
            return $default;
        }

        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $default;
        }
    }
}
