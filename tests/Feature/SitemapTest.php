<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $slug, $publishedAt, array $langues, bool $trashed = false): Article
    {
        $article = Article::create(['title' => $slug, 'slug' => $slug, 'published_at' => $publishedAt]);

        foreach ($langues as $langue) {
            $article->traductions()->create([
                'langue' => $langue,
                'traduction' => ['title' => $slug, 'description' => '', 'article' => '<p>x</p>', 'illustration_id' => null],
            ]);
        }

        if ($trashed) {
            $article->delete();
        }

        return $article;
    }

    public function test_sitemap_lists_localized_static_pages_with_alternates(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->getContent();

        $this->assertStringContainsString('<loc>http://localhost/fr</loc>', $xml);
        $this->assertStringContainsString('<loc>http://localhost/he/articles</loc>', $xml);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/en"', $xml);
        $this->assertStringNotContainsString('<loc>http://localhost</loc>', $xml);
    }

    public function test_sitemap_lists_each_published_translation_with_its_alternates_only(): void
    {
        $this->article('bilingue', now()->subDay(), ['fr', 'en']);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<loc>http://localhost/fr/articles/bilingue</loc>', $xml);
        $this->assertStringContainsString('<loc>http://localhost/en/articles/bilingue</loc>', $xml);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/en/articles/bilingue"', $xml);
        $this->assertStringNotContainsString('http://localhost/he/articles/bilingue', $xml);
    }

    public function test_sitemap_skips_drafts_scheduled_and_deleted_articles(): void
    {
        $this->article('brouillon', null, ['fr']);
        $this->article('programme', now()->addDay(), ['fr']);
        $this->article('supprime', now()->subDay(), ['fr'], trashed: true);

        $xml = $this->get('/sitemap.xml')->getContent();

        foreach (['brouillon', 'programme', 'supprime'] as $slug) {
            $this->assertStringNotContainsString("/articles/{$slug}", $xml);
        }
    }

    public function test_publishing_an_article_refreshes_the_cached_sitemap(): void
    {
        $article = $this->article('nouveau', null, ['fr']);
        $this->assertStringNotContainsString('/articles/nouveau', $this->get('/sitemap.xml')->getContent());

        $article->update(['published_at' => now()->subMinute()]);

        $this->assertStringContainsString('/articles/nouveau', $this->get('/sitemap.xml')->getContent());
    }

    public function test_sitemap_adds_an_x_default_alternate_pointing_to_fr(): void
    {
        $this->article('bilingue', now()->subDay(), ['en', 'fr']);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('hreflang="x-default" href="http://localhost/fr/articles/bilingue"', $xml);
    }

    public function test_sitemap_x_default_falls_back_to_the_first_available_locale(): void
    {
        $this->article('anglais', now()->subDay(), ['en']);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('hreflang="x-default" href="http://localhost/en/articles/anglais"', $xml);
    }
}
