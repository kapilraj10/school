<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TimetableSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("timetable_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): static
    {
        $castValue = static::prepareValue($value, $type);

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $castValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("timetable_setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)])
            ->toArray();
    }

    /**
     * Get default settings
     */
    public static function getDefaults(): array
    {
        return [
            // General settings
            'school_days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'periods_per_day' => 8,
            'period_duration_minutes' => 40,
            'break_after_period' => 4,
            'break_duration_minutes' => 20,

            // Algorithm settings
            'max_same_subject_per_day' => 2,
            'respect_teacher_availability' => true,
            'balance_daily_load' => true,
            'avoid_consecutive_subjects' => true,

            // Class range settings
            'class_ranges' => [
                '1 - 4' => ['start' => 1, 'end' => 4, 'periods_per_day' => 8],
                '5 - 7' => ['start' => 5, 'end' => 7, 'periods_per_day' => 8],
                '8' => ['start' => 8, 'end' => 8, 'periods_per_day' => 8],
                '9 - 10' => ['start' => 9, 'end' => 10, 'periods_per_day' => 8],
            ],
        ];
    }

    /**
     * Cast stored value to appropriate type
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? [],
            'float', 'double' => (float) $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => json_encode($value),
            'boolean', 'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Scope for group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
