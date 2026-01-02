<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClassRange extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'start_class',
        'end_class',
        'periods_per_day',
        'periods_per_week',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'start_class' => 'integer',
        'end_class' => 'integer',
        'periods_per_day' => 'integer',
        'periods_per_week' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active ranges
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_class');
    }

    /**
     * Get class range for a specific class number
     */
    public static function getForClassNumber(int $classNumber): ?static
    {
        return Cache::remember("class_range_for_{$classNumber}", 3600, function () use ($classNumber) {
            return static::active()
                ->where('start_class', '<=', $classNumber)
                ->where('end_class', '>=', $classNumber)
                ->first();
        });
    }

    /**
     * Get the range name for a class number
     */
    public static function getRangeNameForClass(int $classNumber): string
    {
        $range = static::getForClassNumber($classNumber);

        return $range?->name ?? static::getFallbackRangeName($classNumber);
    }

    /**
     * Fallback range name calculation (for backwards compatibility)
     */
    public static function getFallbackRangeName(int $classNumber): string
    {
        if ($classNumber >= 1 && $classNumber <= 4) {
            return '1 - 4';
        }

        if ($classNumber >= 5 && $classNumber <= 7) {
            return '5 - 7';
        }

        if ($classNumber === 8) {
            return '8';
        }

        if ($classNumber >= 9 && $classNumber <= 10) {
            return '9 - 10';
        }

        if ($classNumber >= 11 && $classNumber <= 12) {
            return '11 - 12';
        }

        return '1 - 4';
    }

    /**
     * Get all active class ranges as options array
     */
    public static function getOptionsArray(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->pluck('display_name', 'name')
            ->toArray();
    }

    /**
     * Clear cache for class ranges
     */
    public static function clearCache(): void
    {
        // Clear cache for class numbers 1-15
        for ($i = 1; $i <= 15; $i++) {
            Cache::forget("class_range_for_{$i}");
        }
    }

    /**
     * Seed default class ranges
     */
    public static function seedDefaults(): void
    {
        $defaults = [
            ['name' => '1 - 4', 'display_name' => 'Class 1-4', 'start_class' => 1, 'end_class' => 4, 'sort_order' => 1],
            ['name' => '5 - 7', 'display_name' => 'Class 5-7', 'start_class' => 5, 'end_class' => 7, 'sort_order' => 2],
            ['name' => '8', 'display_name' => 'Class 8', 'start_class' => 8, 'end_class' => 8, 'sort_order' => 3],
            ['name' => '9 - 10', 'display_name' => 'Class 9-10', 'start_class' => 9, 'end_class' => 10, 'sort_order' => 4],
        ];

        foreach ($defaults as $range) {
            static::updateOrCreate(
                ['name' => $range['name']],
                array_merge($range, [
                    'periods_per_day' => 8,
                    'periods_per_week' => 48,
                    'is_active' => true,
                ])
            );
        }

        static::clearCache();
    }
}
