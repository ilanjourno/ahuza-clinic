<?php

namespace App\Seo;

use App\Models\Article;
use App\Models\Traduction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Schema.org JSON-LD for the public pages, rendered by app.blade.php. */
class StructuredData
{
    public const PHONE = '+972-58-726-0264';

    public const EMAIL = 'drallouchegyneco@gmail.com';

    public const SAME_AS = [
        'https://www.facebook.com/DrAllouche2020',
        'https://www.linkedin.com/in/claude-allouche-776ba719b/',
    ];

    public static function physicianId(): string
    {
        return url('/').'/#physician';
    }

    public static function home(string $locale): array
    {
        $base = url('/');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Physician',
                    '@id' => self::physicianId(),
                    'name' => 'Dr Claude Allouche',
                    'url' => "{$base}/{$locale}",
                    'image' => "{$base}/images/og-image.jpg",
                    'telephone' => self::PHONE,
                    'email' => self::EMAIL,
                    'medicalSpecialty' => ['Gynecologic', 'Obstetric'],
                    'sameAs' => self::SAME_AS,
                    'hospitalAffiliation' => [
                        '@type' => 'Hospital',
                        'name' => 'Laniado Hospital',
                        'address' => self::address('Netanya'),
                    ],
                    'location' => [
                        ['@id' => "{$base}/#raanana"],
                        ['@id' => "{$base}/#netanya"],
                    ],
                ],
                [
                    '@type' => 'MedicalClinic',
                    '@id' => "{$base}/#raanana",
                    'name' => 'Clinic 4 You',
                    'telephone' => self::PHONE,
                    'address' => self::address('Raanana', 'Hamasger 8'),
                ],
                [
                    '@type' => 'MedicalClinic',
                    '@id' => "{$base}/#netanya",
                    'name' => 'Dr Claude Allouche - Netanya',
                    'telephone' => self::PHONE,
                    'address' => self::address('Netanya', 'Migdal Hahaloutsim, 6 rehov Petah Tikva, 10e étage, salle 1002'),
                ],
            ],
        ];
    }

    public static function article(Article $article, Traduction $traduction, string $locale): array
    {
        $content = $traduction->traduction;
        $url = url("/{$locale}/articles/{$article->slug}");

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => "{$url}#article",
            'mainEntityOfPage' => $url,
            'headline' => Str::limit($content['title'] ?? $article->title, 107),
            'description' => $content['description'] ?? null,
            'inLanguage' => $locale,
            'image' => $content['illustration']['link'] ?? null,
            'datePublished' => $article->published_at ? Carbon::parse($article->published_at)->toAtomString() : null,
            'dateModified' => $article->updated_at?->toAtomString(),
            'author' => [
                '@type' => 'Physician',
                '@id' => self::physicianId(),
                'name' => 'Dr Claude Allouche',
                'url' => url("/{$locale}"),
            ],
            'publisher' => ['@id' => self::physicianId()],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function address(string $locality, ?string $street = null): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $locality,
            'addressCountry' => 'IL',
        ]);
    }
}
