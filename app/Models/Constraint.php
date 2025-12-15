<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Constraint extends Model
{
    protected $fillable = [
        'name',
        'type',
        'rule',
        'priority',
        'is_active',
        'description',
    ];

    protected $casts = [
        'rule' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
