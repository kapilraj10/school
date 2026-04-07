<?php

namespace App\Models;

use App\Services\CloudinaryImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpecialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_term_id',
        'class_room_id',
        'name',
        'event_type',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_school_wide',
        'blocks_timetable',
        'description',
        'venue',
        'notice_url',
        'notice_link_text',
        'show_on_home',
        'show_popup',
        'popup_image',
        'popup_cloudinary_public_id',
        'popup_cloudinary_folder',
    ];

    protected $casts = [
        'date' => 'date',
        'day_of_week' => 'integer',
        'is_school_wide' => 'boolean',
        'blocks_timetable' => 'boolean',
        'show_on_home' => 'boolean',
        'show_popup' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (SpecialEvent $event): void {
            if (empty($event->popup_cloudinary_public_id)) {
                return;
            }

            try {
                app(CloudinaryImageService::class)->deleteImage($event->popup_cloudinary_public_id);
            } catch (\Throwable) {
                // Ignore cleanup failures to avoid blocking deletes.
            }
        });
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function getPopupImageUrlAttribute(): ?string
    {
        if (blank($this->popup_image)) {
            return null;
        }

        if (Str::startsWith($this->popup_image, ['http://', 'https://', '/'])) {
            return $this->popup_image;
        }

        return Storage::url($this->popup_image);
    }

    public function getEventTimeTextAttribute(): string
    {
        if ($this->start_time && $this->end_time) {
            return sprintf(
                '%s – %s',
                date('g:i A', strtotime((string) $this->start_time)),
                date('g:i A', strtotime((string) $this->end_time))
            );
        }

        if ($this->start_time) {
            return date('g:i A', strtotime((string) $this->start_time));
        }

        return 'Time will be announced';
    }
}
