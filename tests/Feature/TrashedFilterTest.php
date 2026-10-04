<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FAQ;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrashedFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_filtered_by_trashed_state(): void
    {
        $user = User::factory()->create();
        Category::create(['label' => 'active']);
        Category::create(['label' => 'trashed'])->delete();

        $this->actingAs($user)
            ->get(route('dashboard.categories.index', ['trashed' => 'only']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.label', 'trashed'));

        $this->actingAs($user)
            ->get(route('dashboard.categories.index', ['trashed' => 'with']))
            ->assertInertia(fn (Assert $page) => $page->has('categories.data', 2));

        $this->actingAs($user)
            ->get(route('dashboard.categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.label', 'active'));
    }

    public function test_faqs_can_be_filtered_by_trashed_state(): void
    {
        $user = User::factory()->create();
        FAQ::create(['label' => 'active']);
        FAQ::create(['label' => 'trashed'])->delete();

        $this->actingAs($user)
            ->get(route('dashboard.faqs.index', ['trashed' => 'only']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('faqs.data', 1)
                ->where('faqs.data.0.label', 'trashed'));

        $this->actingAs($user)
            ->get(route('dashboard.faqs.index', ['trashed' => 'with']))
            ->assertInertia(fn (Assert $page) => $page->has('faqs.data', 2));
    }
}
