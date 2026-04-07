<?php

namespace App\Filament\Resources\SpecialEventResource\Pages;

use App\Filament\Resources\SpecialEventResource;
use App\Services\CloudinaryImageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateSpecialEvent extends CreateRecord
{
    protected static string $resource = SpecialEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $popupImagePath = $data['popup_image'] ?? null;

        if (! is_string($popupImagePath) || $popupImagePath === '' || str_starts_with($popupImagePath, 'http')) {
            $data['popup_cloudinary_public_id'] = null;
            $data['popup_cloudinary_folder'] = null;

            return $data;
        }

        $absolutePath = $this->resolveImageAbsolutePath($popupImagePath);

        $cloudinaryConfigured =
            filled(config('services.cloudinary.cloud_name'))
            && filled(config('services.cloudinary.api_key'))
            && filled(config('services.cloudinary.api_secret'));

        $uploadedImageUrl = null;
        $cloudinaryPublicId = null;
        $cloudinaryFolder = null;

        if ($cloudinaryConfigured) {
            try {
                $uploadResult = app(CloudinaryImageService::class)->uploadImage($absolutePath, 'event-popup');
                $uploadedImageUrl = $uploadResult['secure_url'];
                $cloudinaryPublicId = $uploadResult['public_id'];
                $cloudinaryFolder = $uploadResult['folder'];
                $this->cleanupTemporaryImage($popupImagePath, $absolutePath);
            } catch (\Throwable) {
                $uploadedImageUrl = null;
            }
        }

        if ($uploadedImageUrl === null) {
            $extension = pathinfo($popupImagePath, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'event_popup_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$extension;
            $targetDirectory = public_path('images/event-popup');

            if (! File::exists($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true);
            }

            File::copy($absolutePath, $targetDirectory.DIRECTORY_SEPARATOR.$filename);
            $this->cleanupTemporaryImage($popupImagePath, $absolutePath);
            $uploadedImageUrl = asset('images/event-popup/'.$filename);
        }

        $data['popup_image'] = $uploadedImageUrl;
        $data['popup_cloudinary_public_id'] = $cloudinaryPublicId;
        $data['popup_cloudinary_folder'] = $cloudinaryFolder;

        return $data;
    }

    private function resolveImageAbsolutePath(string $imagePath): string
    {
        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($imagePath)) {
            return $publicDisk->path($imagePath);
        }

        $livewireTmpPath = storage_path('app/livewire-tmp/'.basename($imagePath));

        if (File::exists($livewireTmpPath)) {
            return $livewireTmpPath;
        }

        throw ValidationException::withMessages([
            'popup_image' => 'Uploaded popup image was not found. Please upload again.',
        ]);
    }

    private function cleanupTemporaryImage(string $imagePath, string $absolutePath): void
    {
        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($imagePath)) {
            $publicDisk->delete($imagePath);

            return;
        }

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Special event creation failed')
            ->body('Please review the form and try again.')
            ->send();

        parent::onValidationError($exception);
    }
}
