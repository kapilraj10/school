<?php

namespace App\Filament\Resources\SchoolGalleryResource\Pages;

use App\Filament\Resources\SchoolGalleryResource;
use App\Services\CloudinaryImageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateSchoolGallery extends CreateRecord
{
    protected static string $resource = SchoolGalleryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $photoPath = $data['photo'] ?? null;

        if (! is_string($photoPath) || $photoPath === '') {
            throw ValidationException::withMessages([
                'photo' => 'Photo file is required.',
            ]);
        }

        $absolutePath = Storage::disk('public')->path($photoPath);

        try {
            $uploadResult = app(CloudinaryImageService::class)->uploadImage($absolutePath);
        } catch (\Throwable) {
            Storage::disk('public')->delete($photoPath);

            throw ValidationException::withMessages([
                'photo' => 'Photo upload failed. Please check Cloudinary settings and try again.',
            ]);
        }

        Storage::disk('public')->delete($photoPath);

        unset($data['photo']);

        $data['image_url'] = $uploadResult['secure_url'];
        $data['cloudinary_public_id'] = $uploadResult['public_id'];
        $data['cloudinary_folder'] = $uploadResult['folder'];
        $data['uploaded_by'] = Auth::id();

        return $data;
    }

    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Photo upload failed')
            ->body('Please check the image and try again.')
            ->send();

        parent::onValidationError($exception);
    }
}
