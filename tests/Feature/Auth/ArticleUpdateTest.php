<?php

namespace Tests\Feature\Auth;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_only_edits_bump_updated_at(): void
    {
        $article = Article::create(['title' => 't', 'slug' => 't', 'published_at' => now()->subDay()]);
        $article->traductions()->create(['langue' => 'fr', 'traduction' => ['title' => 'a', 'description' => '', 'article' => '<p>a</p>', 'illustration_id' => null]]);
        $category = Category::factory()->create();
        $before = $article->fresh()->updated_at;

        $this->travel(1)->minutes();

        $this->actingAs(User::factory()->create())->put(route('dashboard.articles.update', $article), [
            'title' => 't',
            'slug' => 't',
            'published_at' => $article->published_at->toDateTimeString(),
            'category_ids' => [$category->id],
            'traductions' => [['langue' => 'fr', 'traduction' => ['title' => 'b', 'description' => '', 'article' => '<p>b</p>', 'illustration_id' => null]]],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($article->fresh()->updated_at->gt($before));
    }
}
