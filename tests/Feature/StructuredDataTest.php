<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    private function jsonLd(TestResponse $response): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $match);
        $this->assertNotEmpty($match, 'No JSON-LD block found');

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }

    private function article(string $slug, string $title, $publishedAt): Article
    {
        $article = Article::create(['title' => $slug, 'slug' => $slug, 'published_at' => $publishedAt]);
        $article->traductions()->create([
            'langue' => 'fr',
            'traduction' => ['title' => $title, 'description' => 'Contraception', 'article' => '<p>x</p>', 'illustration_id' => null],
        ]);

        return $article;
    }

    public function test_home_describes_the_physician_and_both_practices(): void
    {
        $graph = collect($this->jsonLd($this->get('/fr'))['@graph']);

        $physician = $graph->firstWhere('@type', 'Physician');
        $this->assertSame('Dr Claude Allouche', $physician['name']);
        $this->assertSame('+972-58-726-0264', $physician['telephone']);
        $this->assertSame('Raanana', $physician['address']['addressLocality']);
        $this->assertEqualsCanonicalizing(
            ['Raanana', 'Netanya'],
            $graph->where('@type', 'MedicalClinic')->pluck('address.addressLocality')->all()
        );
    }

    public function test_article_page_describes_the_article_and_its_author(): void
    {
        $this->article('diu', 'Le DIU', '2025-02-23 17:18:35');

        $data = $this->jsonLd($this->get('/fr/articles/diu'));

        $this->assertSame('Article', $data['@type']);
        $this->assertSame('Le DIU', $data['headline']);
        $this->assertSame('fr', $data['inLanguage']);
        $this->assertStringStartsWith('2025-02-23T17:18:35', $data['datePublished']);
        $this->assertSame('Physician', $data['author']['@type']);
        $this->assertSame('Dr Claude Allouche', $data['publisher']['name']);
    }

    public function test_script_tags_in_titles_cannot_break_out_of_the_json_ld_block(): void
    {
        $this->article('xss', 'Avant </script><script>alert(1)</script>', now()->subDay());

        $response = $this->get('/fr/articles/xss');

        $this->assertStringNotContainsString('</script><script>alert(1)', $response->getContent());
        $this->assertSame('Avant </script><script>alert(1)</script>', $this->jsonLd($response)['headline']);
    }
}
