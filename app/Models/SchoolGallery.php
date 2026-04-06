<?php

namespace App\Models;

use App\Services\CloudinaryImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'cloudinary_public_id',
        'cloudinary_folder',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (SchoolGallery $gallery): void {
            if (empty($gallery->cloudinary_public_id)) {
                return;
            }

            try {
                app(CloudinaryImageService::class)->deleteImage($gallery->cloudinary_public_id);
            } catch (\Throwable) {
                // Ignore cleanup failures to avoid blocking deletes.
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
