<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_french_without_browser_preference(): void
    {
        // The test client sends 'Accept-Language: en-us' by default; clear it to simulate no preference.
        $this->withHeader('Accept-Language', '')->get('/')->assertRedirect('/fr');
    }

    public function test_root_redirects_to_french_for_unsupported_languages(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/')->assertRedirect('/fr');
    }

    public function test_root_redirects_to_the_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'he-IL,he;q=0.9')->get('/')->assertRedirect('/he');
    }

    public function test_root_redirect_keeps_the_query_string(): void
    {
        $this->withHeader('Accept-Language', '')->get('/?utm_source=gbp')->assertRedirect('/fr?utm_source=gbp');
    }

    public function test_pages_declare_their_language(): void
    {
        foreach (['fr', 'en', 'he'] as $locale) {
            $this->get("/{$locale}")->assertOk()->assertSee("<html lang=\"{$locale}\">", false);
        }
    }
}
