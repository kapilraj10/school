<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PageClick extends Model
{
    protected $fillable = [
        'page_name',
        'url',
        'click_count',
    ];

    protected $casts = [
        'click_count' => 'integer',
    ];

    public static function recordClick(string $pageName, string $url): void
    {
        try {
            if (! Schema::hasTable((new static)->getTable())) {
                return;
            }

            $click = static::firstOrCreate(
                ['page_name' => $pageName, 'url' => $url],
                ['click_count' => 0]
            );

            $click->increment('click_count');
        } catch (QueryException) {
            // Gracefully ignore tracking when table is unavailable.
        }
    }

    public static function getTopClicked(int $limit = 5): Collection
    {
        try {
            if (! Schema::hasTable((new static)->getTable())) {
                return collect();
            }

            return static::orderBy('click_count', 'desc')
                ->limit($limit)
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }
}
