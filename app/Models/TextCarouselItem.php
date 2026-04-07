<?php

namespace App\Models;

use App\Services\CloudinaryImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TextCarouselItem extends Model
{
    /** @use HasFactory<\Database\Factories\TextCarouselItemFactory> */
    use HasFactory;

    protected $fillable = [
        'quote',
        'author_name',
        'author_role',
        'author_image',
        'cloudinary_public_id',
        'cloudinary_folder',
        'rating',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::deleting(function (TextCarouselItem $item): void {
            if (empty($item->cloudinary_public_id)) {
                return;
            }

            try {
                app(CloudinaryImageService::class)->deleteImage($item->cloudinary_public_id);
            } catch (\Throwable) {
                // Ignore cleanup failures to avoid blocking deletes.
            }
        });
    }

    public function getAuthorImageUrlAttribute(): ?string
    {
        if (blank($this->author_image)) {
            return null;
        }

        if (Str::startsWith($this->author_image, ['http://', 'https://', '/'])) {
            return $this->author_image;
        }

        return Storage::url($this->author_image);
    }
}
