<?php

namespace App\Seo;

use App\Http\Middleware\SetLocale;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/** Builds sitemap.xml from the database with hreflang alternates, cached for an hour. */
class SitemapBuilder
{
    public const CACHE_KEY = 'sitemap.xml';

    public static function render(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => self::build()->render());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (['', '/articles'] as $path) {
            foreach (SetLocale::LOCALES as $locale) {
                $sitemap->add(self::withAlternates(Url::create(url("/{$locale}{$path}")), $path, SetLocale::LOCALES));
            }
        }

        Article::published()->with('traductions')->get()->each(function (Article $article) use ($sitemap) {
            $translations = $article->traductions->unique('langue');
            $locales = $translations->pluck('langue')->all();
            $path = "/articles/{$article->slug}";

            foreach ($translations as $translation) {
                $url = Url::create(url("/{$translation->langue}{$path}"))
                    ->setLastModificationDate($article->updated_at);

                if ($image = $translation->traduction['illustration']['link'] ?? null) {
                    $url->addImage($image);
                }

                $sitemap->add(self::withAlternates($url, $path, $locales));
            }
        });

        return $sitemap;
    }

    private static function withAlternates(Url $url, string $path, array $locales): Url
    {
        foreach ($locales as $locale) {
            $url->addAlternate(url("/{$locale}{$path}"), $locale);
        }

        return $url;
    }
}
