<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArticleTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $slug, array $langues): Article
    {
        $article = Article::create(['title' => $slug, 'slug' => $slug, 'published_at' => now()->subDay()]);

        foreach ($langues as $langue) {
            $article->traductions()->create([
                'langue' => $langue,
                'traduction' => ['title' => "{$slug} {$langue}", 'description' => '', 'article' => '<p>x</p>', 'illustration_id' => null],
            ]);
        }

        return $article;
    }

    public function test_article_page_lists_only_existing_translations_in_site_order(): void
    {
        $this->article('bilingue', ['en', 'fr']);

        $this->get('/fr/articles/bilingue')->assertInertia(fn (Assert $page) => $page
            ->component('Articles/Show')
            ->where('availableLocales', ['fr', 'en']));
    }

    public function test_single_language_article_lists_one_locale(): void
    {
        $this->article('francais', ['fr']);

        $this->get('/fr/articles/francais')->assertInertia(fn (Assert $page) => $page
            ->where('availableLocales', ['fr']));
    }
}
