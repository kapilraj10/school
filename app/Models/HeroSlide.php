<?php

namespace App\Models;

use App\Services\CloudinaryImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeroSlide extends Model
{
    /** @use HasFactory<\Database\Factories\HeroSlideFactory> */
    use HasFactory;

    protected $fillable = [
        'subtitle',
        'title',
        'description',
        'button_text',
        'button_link',
        'background_image',
        'cloudinary_public_id',
        'cloudinary_folder',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::deleting(function (HeroSlide $slide): void {
            if (empty($slide->cloudinary_public_id)) {
                return;
            }

            try {
                app(CloudinaryImageService::class)->deleteImage($slide->cloudinary_public_id);
            } catch (\Throwable) {
                // Ignore cleanup failures to avoid blocking deletes.
            }
        });
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        if (blank($this->background_image)) {
            return null;
        }

        if (Str::startsWith($this->background_image, ['http://', 'https://', '/'])) {
            return $this->background_image;
        }

        return Storage::url($this->background_image);
    }
}
