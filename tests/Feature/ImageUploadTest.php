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

    public function test_replacing_an_image_deletes_the_previous_file_only_after_storing_the_new_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('dashboard.images.store'), ['label' => 'avant', 'file' => UploadedFile::fake()->image('avant.jpg', 800, 600)]);
        $file = File::firstOrFail();
        $old = $file->name;
        Storage::assertExists($old);

        $this->actingAs($user)
            ->post(route('dashboard.images.update', $file), ['file' => UploadedFile::fake()->image('apres.jpg', 700, 500)])
            ->assertSessionHasNoErrors();

        $file->refresh();
        $this->assertNotSame($old, $file->name);
        Storage::assertMissing($old);
        Storage::assertExists($file->name);
    }

    public function test_a_failed_replacement_keeps_the_previous_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('dashboard.images.store'), ['label' => 'avant', 'file' => UploadedFile::fake()->image('avant.jpg', 800, 600)]);
        $file = File::firstOrFail();
        $old = $file->name;

        $this->mock(ImageOptimizer::class)->shouldReceive('optimize')->andThrow(new \RuntimeException('boom'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('dashboard.images.update', $file), ['file' => UploadedFile::fake()->image('apres.jpg', 700, 500)]);
            $this->fail('The replacement should have failed');
        } catch (\RuntimeException) {
        }

        Storage::assertExists($old);
        $this->assertSame($old, $file->fresh()->name);
    }

    public function test_label_only_update_keeps_the_stored_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('dashboard.images.store'), ['label' => 'avant', 'file' => UploadedFile::fake()->image('avant.jpg', 800, 600)]);
        $file = File::firstOrFail();

        $this->actingAs($user)->post(route('dashboard.images.update', $file), ['label' => 'apres'])->assertSessionHasNoErrors();

        Storage::assertExists($file->fresh()->name);
    }

    public function test_gif_uploads_are_stored_unchanged(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'gif', 'file' => UploadedFile::fake()->image('a.gif', 2000, 1000)])
            ->assertSessionHasNoErrors();

        $this->assertSame(2000, $this->storedWidth(File::firstOrFail()));
    }

    public function test_svg_uploads_pass_through_untouched(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>';

        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'svg', 'file' => UploadedFile::fake()->createWithContent('a.svg', $svg)])
            ->assertSessionHasNoErrors();

        $this->assertSame($svg, Storage::get(File::firstOrFail()->name));
    }
}
