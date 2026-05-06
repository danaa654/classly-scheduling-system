<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
     * Check if configuration is locked
     */
    public static function isConfigLocked(): bool
    {
        return (bool) self::where('key', 'config_locked')->first()?->value ?? true;
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
            'start' => self::getValue('day_start', '07:00'),
            'end' => self::getValue('day_end', '21:00'),
        ];
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
}
