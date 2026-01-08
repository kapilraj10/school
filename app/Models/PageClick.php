<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        $click = static::firstOrCreate(
            ['page_name' => $pageName, 'url' => $url],
            ['click_count' => 0]
        );

        $click->increment('click_count');
    }

    public static function getTopClicked(int $limit = 5)
    {
        return static::orderBy('click_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
