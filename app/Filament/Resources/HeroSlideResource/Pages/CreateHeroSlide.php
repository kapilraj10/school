<?php

namespace App\Filament\Resources\HeroSlideResource\Pages;

use App\Filament\Resources\HeroSlideResource;
use App\Services\CloudinaryImageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateHeroSlide extends CreateRecord
{
    protected static string $resource = HeroSlideResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $imagePath = $data['background_image'] ?? null;

        if (! is_string($imagePath) || $imagePath === '' || str_starts_with($imagePath, 'http')) {
            return $data;
        }

        $absolutePath = $this->resolveImageAbsolutePath($imagePath, 'background_image');

        $cloudinaryConfigured =
            filled(config('services.cloudinary.cloud_name'))
            && filled(config('services.cloudinary.api_key'))
            && filled(config('services.cloudinary.api_secret'));

        $uploadedImageUrl = null;
        $cloudinaryPublicId = '';
        $cloudinaryFolder = null;

        if ($cloudinaryConfigured) {
            try {
                $uploadResult = app(CloudinaryImageService::class)->uploadImage($absolutePath, 'hero-slides');
                $uploadedImageUrl = $uploadResult['secure_url'];
                $cloudinaryPublicId = $uploadResult['public_id'];
                $cloudinaryFolder = $uploadResult['folder'];
                $this->cleanupTemporaryImage($imagePath, $absolutePath);
            } catch (\Throwable) {
                $uploadedImageUrl = null;
            }
        }

        if ($uploadedImageUrl === null) {
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'hero_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$extension;
            $targetDirectory = public_path('images/hero-slides');

            if (! File::exists($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true);
            }

            File::copy($absolutePath, $targetDirectory.DIRECTORY_SEPARATOR.$filename);
            $this->cleanupTemporaryImage($imagePath, $absolutePath);
            $uploadedImageUrl = asset('images/hero-slides/'.$filename);
        }

        $data['background_image'] = $uploadedImageUrl;
        $data['cloudinary_public_id'] = $cloudinaryPublicId;
        $data['cloudinary_folder'] = $cloudinaryFolder;

        return $data;
    }

    private function resolveImageAbsolutePath(string $imagePath, string $field): string
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
            $field => 'Uploaded image file was not found. Please upload the image again.',
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
            ->title('Hero slide creation failed')
            ->body('Please review the form and try again.')
            ->send();

        parent::onValidationError($exception);
    }
}
