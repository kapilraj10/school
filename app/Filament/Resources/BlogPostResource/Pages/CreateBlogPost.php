<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use App\Services\CloudinaryImageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? 'published') === 'published' && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }

        $imagePath = $data['featured_image'] ?? null;

        if (! is_string($imagePath) || $imagePath === '') {
            return $data;
        }

        if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $data;
        }

        $absolutePath = $this->resolveImageAbsolutePath($imagePath);

        $cloudinaryConfigured =
            filled(config('services.cloudinary.cloud_name'))
            && filled(config('services.cloudinary.api_key'))
            && filled(config('services.cloudinary.api_secret'));

        $imageUrl = null;

        if ($cloudinaryConfigured) {
            try {
                $uploadResult = app(CloudinaryImageService::class)->uploadImage($absolutePath, 'blog-posts');
                $imageUrl = $uploadResult['secure_url'];
                $this->cleanupTemporaryImage($imagePath, $absolutePath);
            } catch (\Throwable) {
                $imageUrl = null;
            }
        }

        if ($imageUrl === null) {
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'blog_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$extension;
            $targetDirectory = public_path('images/blog-posts');

            if (! File::exists($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true);
            }

            File::copy($absolutePath, $targetDirectory.DIRECTORY_SEPARATOR.$filename);
            $this->cleanupTemporaryImage($imagePath, $absolutePath);

            $imageUrl = asset('images/blog-posts/'.$filename);
        }

        $data['featured_image'] = $imageUrl;

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
            'featured_image' => 'Uploaded featured image was not found. Please upload again.',
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
            ->title('Blog post creation failed')
            ->body('Please review the form and try again.')
            ->send();

        parent::onValidationError($exception);
    }
}
