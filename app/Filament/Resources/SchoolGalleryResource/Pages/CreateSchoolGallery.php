<?php

namespace App\Filament\Resources\SchoolGalleryResource\Pages;

use App\Filament\Resources\SchoolGalleryResource;
use App\Services\CloudinaryImageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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

        $absolutePath = $this->resolvePhotoAbsolutePath($photoPath);

        $cloudinaryConfigured =
            filled(config('services.cloudinary.cloud_name')) &&
            filled(config('services.cloudinary.api_key')) &&
            filled(config('services.cloudinary.api_secret'));

        $imageUrl = null;
        $cloudinaryPublicId = '';
        $cloudinaryFolder = null;

        if ($cloudinaryConfigured) {
            try {
                $uploadResult = app(CloudinaryImageService::class)->uploadImage($absolutePath);

                $imageUrl = $uploadResult['secure_url'];
                $cloudinaryPublicId = $uploadResult['public_id'];
                $cloudinaryFolder = $uploadResult['folder'];

                $this->cleanupTemporaryPhoto($photoPath, $absolutePath);
            } catch (\Throwable) {
                $imageUrl = null;
            }
        }

        if ($imageUrl === null) {
            $extension = pathinfo($photoPath, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'gallery_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$extension;
            $targetDirectory = public_path('images/school-gallery');

            if (! File::exists($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true);
            }

            $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$filename;

            File::copy($absolutePath, $targetPath);
            $this->cleanupTemporaryPhoto($photoPath, $absolutePath);

            $imageUrl = asset('images/school-gallery/'.$filename);
        }

        unset($data['photo']);

        $data['image_url'] = $imageUrl;
        $data['cloudinary_public_id'] = $cloudinaryPublicId;
        $data['cloudinary_folder'] = $cloudinaryFolder;
        $data['uploaded_by'] = Auth::id();

        return $data;
    }

    private function resolvePhotoAbsolutePath(string $photoPath): string
    {
        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($photoPath)) {
            return $publicDisk->path($photoPath);
        }

        $livewireTmpPath = storage_path('app/livewire-tmp/'.basename($photoPath));

        if (File::exists($livewireTmpPath)) {
            return $livewireTmpPath;
        }

        throw ValidationException::withMessages([
            'photo' => 'Uploaded photo file was not found. Please upload the photo again.',
        ]);
    }

    private function cleanupTemporaryPhoto(string $photoPath, string $absolutePath): void
    {
        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($photoPath)) {
            $publicDisk->delete($photoPath);

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
            ->title('Photo upload failed')
            ->body('Please check the image and try again.')
            ->send();

        parent::onValidationError($exception);
    }
}
