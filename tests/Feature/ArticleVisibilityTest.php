<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArticleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function createArticle(string $slug, $publishedAt, bool $trashed = false): Article
    {
        $article = Article::create([
            'title' => $slug,
            'slug' => $slug,
            'published_at' => $publishedAt,
        ]);

        $article->traductions()->create([
            'langue' => 'fr',
            'traduction' => [
                'title' => $slug,
                'description' => $slug,
                'article' => "<p>{$slug}</p>",
                'illustration_id' => null,
            ],
        ]);

        if ($trashed) {
            $article->delete();
        }

        return $article;
    }

    private function createArticlesInEveryState(): void
    {
        $this->createArticle('published', now()->subDay());
        $this->createArticle('scheduled', now()->addDay());
        $this->createArticle('draft', null);
        $this->createArticle('trashed', now()->subDay(), trashed: true);
    }

    public function test_public_article_list_only_shows_published_articles(): void
    {
        $this->createArticlesInEveryState();

        $this->get('/fr/articles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Articles/Index')
                ->has('articles.data', 1)
                ->where('articles.data.0.slug', 'published'));
    }

    public function test_home_page_only_shows_published_articles(): void
    {
        $this->createArticlesInEveryState();

        $this->get('/fr')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('articles', 1)
                ->where('articles.0.slug', 'published'));
    }

    public function test_articles_api_only_returns_published_articles(): void
    {
        $this->createArticlesInEveryState();

        $this->withSession(['locale' => 'fr'])
            ->getJson('/api/articles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'published');
    }

    public function test_guests_can_read_a_published_article(): void
    {
        $this->createArticle('published', now()->subDay());

        $this->get('/fr/articles/published')->assertOk();
    }

    public function test_guests_cannot_read_unpublished_or_deleted_articles(): void
    {
        $this->createArticle('scheduled', now()->addDay());
        $this->createArticle('draft', null);
        $this->createArticle('trashed', now()->subDay(), trashed: true);

        $this->get('/fr/articles/scheduled')->assertNotFound();
        $this->get('/fr/articles/draft')->assertNotFound();
        $this->get('/fr/articles/trashed')->assertNotFound();
    }

    public function test_authenticated_users_can_preview_unpublished_articles(): void
    {
        $this->createArticle('draft', null);

        $this->actingAs(User::factory()->create())
            ->get('/fr/articles/draft')
            ->assertOk();
    }
}
