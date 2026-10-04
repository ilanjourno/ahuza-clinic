<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
    }

    private function storedWidth(File $file): int
    {
        return getimagesize(Storage::path($file->name))[0];
    }

    public function test_large_dashboard_uploads_are_downscaled(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'grand', 'file' => UploadedFile::fake()->image('grand.jpg', 3000, 2000)])
            ->assertSessionHasNoErrors();

        $file = File::firstOrFail();
        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth($file));
        $this->assertSame(Storage::size($file->name), (int) $file->size);
    }

    public function test_editor_uploads_are_downscaled(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/api/files', ['label' => 'editeur', 'file' => UploadedFile::fake()->image('editeur.png', 2400, 1200)])
            ->assertSuccessful();

        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth(File::firstOrFail()));
    }

    public function test_replacing_an_image_downscales_the_new_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('dashboard.images.store'), ['label' => 'avant', 'file' => UploadedFile::fake()->image('avant.jpg', 800, 600)]);
        $file = File::firstOrFail();

        $this->actingAs($user)
            ->post(route('dashboard.images.update', $file), ['file' => UploadedFile::fake()->image('apres.jpg', 2000, 1000)])
            ->assertSessionHasNoErrors();

        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth($file->fresh()));
    }

    public function test_small_images_keep_their_dimensions(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'petit', 'file' => UploadedFile::fake()->image('petit.jpg', 800, 600)]);

        $this->assertSame(800, $this->storedWidth(File::firstOrFail()));
    }
}
