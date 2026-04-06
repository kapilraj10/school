<?php

namespace Tests\Unit\Services;

use App\Services\CloudinaryImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryImageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.cloudinary.cloud_name', 'demo-cloud');
        Config::set('services.cloudinary.api_key', 'demo-key');
        Config::set('services.cloudinary.api_secret', 'demo-secret');
        Config::set('services.cloudinary.folder', 'pos');
    }

    public function test_upload_image_returns_cloudinary_url_and_public_id(): void
    {
        Storage::fake('local');

        $storedPath = UploadedFile::fake()
            ->image('school-photo.jpg')
            ->store('gallery', 'local');

        Http::fake([
            'https://api.cloudinary.com/v1_1/demo-cloud/image/upload' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo-cloud/image/upload/v1/pos/school-photo.jpg',
                'public_id' => 'pos/school-photo',
            ], 200),
        ]);

        $service = new CloudinaryImageService;

        $result = $service->uploadImage(Storage::disk('local')->path($storedPath));

        $this->assertSame('https://res.cloudinary.com/demo-cloud/image/upload/v1/pos/school-photo.jpg', $result['secure_url']);
        $this->assertSame('pos/school-photo', $result['public_id']);
        $this->assertSame('pos', $result['folder']);
    }

    public function test_delete_image_returns_true_on_success(): void
    {
        Http::fake([
            'https://api.cloudinary.com/v1_1/demo-cloud/image/destroy' => Http::response([
                'result' => 'ok',
            ], 200),
        ]);

        $service = new CloudinaryImageService;

        $this->assertTrue($service->deleteImage('pos/school-photo'));
    }
}
