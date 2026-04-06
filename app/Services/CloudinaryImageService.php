<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CloudinaryImageService
{
    /**
     * Upload an image to Cloudinary.
     *
     * @return array{secure_url: string, public_id: string, folder: string|null}
     */
    public function uploadImage(UploadedFile|string $file, ?string $folder = null): array
    {
        $credentials = $this->credentials();

        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! $filePath || ! file_exists($filePath)) {
            throw new \RuntimeException('Invalid file path for Cloudinary upload.');
        }

        $folder = $folder ?? $credentials['folder'];
        $timestamp = time();

        $signaturePayload = $folder !== null && $folder !== ''
            ? "folder={$folder}&timestamp={$timestamp}{$credentials['api_secret']}"
            : "timestamp={$timestamp}{$credentials['api_secret']}";

        $signature = sha1($signaturePayload);

        $request = Http::attach('file', file_get_contents($filePath), basename($filePath));

        $response = $request
            ->asForm()
            ->post("https://api.cloudinary.com/v1_1/{$credentials['cloud_name']}/image/upload", [
                'api_key' => $credentials['api_key'],
                'timestamp' => $timestamp,
                'signature' => $signature,
                'folder' => $folder,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Cloudinary upload failed: '.$response->body());
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return [
            'secure_url' => (string) ($payload['secure_url'] ?? ''),
            'public_id' => (string) ($payload['public_id'] ?? ''),
            'folder' => $folder,
        ];
    }

    public function deleteImage(string $publicId): bool
    {
        if ($publicId === '') {
            return false;
        }

        $credentials = $this->credentials();
        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$credentials['api_secret']}");

        $response = Http::asForm()->post(
            "https://api.cloudinary.com/v1_1/{$credentials['cloud_name']}/image/destroy",
            [
                'public_id' => $publicId,
                'api_key' => $credentials['api_key'],
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]
        );

        if (! $response->successful()) {
            return false;
        }

        return $response->json('result') === 'ok';
    }

    /**
     * @return array{cloud_name: string, api_key: string, api_secret: string, folder: string|null}
     */
    private function credentials(): array
    {
        $cloudName = (string) config('services.cloudinary.cloud_name');
        $apiKey = (string) config('services.cloudinary.api_key');
        $apiSecret = (string) config('services.cloudinary.api_secret');
        $folder = config('services.cloudinary.folder');

        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            throw new \RuntimeException('Cloudinary credentials are not configured.');
        }

        return [
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'folder' => $folder ? (string) $folder : null,
        ];
    }
}
