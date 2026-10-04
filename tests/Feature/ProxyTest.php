<?php

namespace Tests\Feature;

use App\Seo\SitemapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProxyTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'claude-allouche.com'];

    public function test_absolute_urls_use_the_forwarded_https_host(): void
    {
        SitemapBuilder::forget();

        $html = $this->withHeaders(self::HEADERS)->get('/fr')->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
        $physician = collect(json_decode($match[1], true)['@graph'])->firstWhere('@type', 'Physician');

        $this->assertSame('https://claude-allouche.com/fr', $physician['url']);

        SitemapBuilder::forget();
        $xml = $this->withHeaders(self::HEADERS)->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<loc>https://claude-allouche.com/fr</loc>', $xml);
        SitemapBuilder::forget();
    }
}
