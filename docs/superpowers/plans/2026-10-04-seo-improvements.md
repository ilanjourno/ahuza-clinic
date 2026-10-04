# Améliorations SEO de claude-allouche.com — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corriger, dans le code et l'infrastructure, les problèmes relevés par l'audit SEO du 4 octobre 2026 (score 44/100) : langue des pages, hreflang, liens 404, H1, données structurées, sitemap, accessibilité, vitesse.

**Architecture:** Tout ce qui se teste côté serveur (langue, traductions disponibles d'un article, JSON-LD, sitemap, upload d'images) est calculé dans Laravel et couvert par PHPUnit. Le `<head>` reste rendu par `GuestLayout.tsx`, en s'appuyant sur de petites fonctions pures (`Helpers/seo.ts`, `Helpers/dates.ts`) testées avec le test runner natif de Node, sans nouvelle dépendance. En production, `php artisan serve` est remplacé par nginx + php-fpm dans la même image Docker, pour la compression, le cache et les en-têtes de sécurité.

**Tech Stack:** Laravel 11 (PHP 8.3 en prod, 8.4 en local), Inertia 1 + React 18 + TypeScript avec SSR, Bootstrap 5 / SCSS, spatie/laravel-sitemap 7, luxon 3, i18n-js 4, Docker `php:8.3-fpm` sur Cloud Run, Cloud Build.

**Spec:** `claude-allouche.com-audit/FULL-AUDIT-REPORT.md` et `claude-allouche.com-audit/ACTION-PLAN.md` (dossier non versionné à ce jour — à commiter avec ce plan).

## Global Constraints

- PHP de production : 8.3 (`php:8.3-fpm`). Aucune syntaxe ni dépendance PHP 8.4. Ne jamais commiter les `composer.lock` / `package-lock.json` modifiés localement.
- Tests PHP : `php artisan test`, sur la base locale `ahuza_testing` imposée par `phpunit.xml`. Ne jamais lancer l'app, les tests ou des migrations avec le `.env` local : il pointe vers la base de production.
- Langues du site : `fr`, `en`, `he` ; langue par défaut `fr`.
- Domaine : `https://claude-allouche.com`.
- Coordonnées (NAP) à reprendre à l'identique : téléphone `+972-58-726-0264` (affiché `058-726-0264`), e-mail `drallouchegyneco@gmail.com`, Raanana `Clinic 4 You, Hamasger 8, Raanana`, Netanya `Migdal Hahaloutsim, 6 rehov Petah Tikva, 10e étage, salle 1002, Netanya`.
- Commits : gitmoji + message en anglais, comme l'historique, terminés par `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Le contenu en base de production (FAQ, articles, mots-clés) n'est pas modifié par ce plan : voir « Hors code ».
- Erreurs TypeScript préexistantes tolérées (4) : `react-use` (2), `TableV2.tsx:352`, `ssr.tsx:23`. Aucune nouvelle erreur acceptée.

## Review Focus

1. Titre ou description d'article contenant `"`, `<` ou `&` : le `<head>` doit rester du HTML valide et la meta description complète. Couvert par le test `escapeAttr` (Task 2) et par la vérification SSR sur l'article « Le "DIU" & vous » du seeder (Task 0).
2. Article publié dans une seule langue : aucun lien (drapeau, hreflang, sitemap) vers une traduction absente. Tests en Task 3 et Task 7.
3. Brouillon, article programmé ou supprimé : absent du sitemap. Test en Task 7 (la visibilité des pages est déjà couverte par `ArticleVisibilityTest`).
4. Visiteur sans `Accept-Language`, ou avec une langue non gérée, sur `/` : redirection vers `/fr` (aujourd'hui la langue de repli serait `en`). Test en Task 1.
5. `</script>` dans un titre d'article : le bloc JSON-LD ne doit pas casser la page. Test en Task 6.

## Vérification locale (procédure partagée)

Plusieurs tâches vérifient le HTML rendu côté serveur. Procédure, à lancer depuis la racine du projet :

```bash
# 1. Une seule fois : base de prévisualisation locale (rôle ahuza/ahuza déjà créé pour les tests)
createdb -h 127.0.0.1 -O ahuza ahuza_preview 2>/dev/null || true

# 2. Dans chaque terminal : ces variables écrasent le .env (qui pointe vers la prod)
export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=ahuza_preview \
       DB_USERNAME=ahuza DB_PASSWORD=ahuza SESSION_DRIVER=file CACHE_STORE=array \
       FILESYSTEM_DISK=local APP_URL=http://127.0.0.1:8123

# 3. Données de test (le seeder est créé en Task 0)
php artisan migrate:fresh --force
php artisan db:seed --class=SeoPreviewSeeder --force

# 4. Build client + SSR, puis serveurs SSR et HTTP en arrière-plan
rm -f public/hot
npm run build
php artisan inertia:start-ssr > /tmp/ahuza-ssr.log 2>&1 &
php artisan serve --host=127.0.0.1 --port=8123 > /tmp/ahuza-serve.log 2>&1 &
until curl -s -o /dev/null http://127.0.0.1:8123/fr; do sleep 1; done

# 5. Contrôles propres à chaque tâche (voir la tâche)

# 6. Arrêt et nettoyage
pkill -f "inertia:start-ssr"; pkill -f "artisan serve --host=127.0.0.1 --port=8123"
rm -rf public/build bootstrap/ssr
```

---

### Task 0: Jeu de données de prévisualisation

**Files:**
- Create: `database/seeders/SeoPreviewSeeder.php`

**Interfaces:**
- Produces: les slugs `le-diu-et-vous` (publié, fr/en/he, guillemets et `&` dans les métadonnées), `article-francais-seulement` (publié, fr seul) et `brouillon` (non publié, fr), plus une FAQ trilingue et l'utilisateur `preview@example.test` (mot de passe `password`). Utilisés par les vérifications des Tasks 1 à 8.

- [ ] **Step 1: Créer le seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\FAQ;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only data covering the SEO edge cases: quotes in metadata,
 * single-language articles and drafts.
 */
class SeoPreviewSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create(['email' => 'preview@example.test']);

        $faq = FAQ::create(['label' => 'rdv']);
        foreach (['fr' => 'Comment prendre rendez-vous ?', 'en' => 'How do I book?', 'he' => 'איך קובעים תור?'] as $langue => $question) {
            $faq->traductions()->create([
                'langue' => $langue,
                'traduction' => ['question' => $question, 'answer' => 'WhatsApp : 058-726-0264'],
            ]);
        }

        $category = Category::create(['label' => 'Gynécologie', 'background_color' => '#e6f2f5', 'text_color' => '#1f6f8b']);

        $this->article($category, 'le-diu-et-vous', now()->subDays(10), [
            'fr' => ['Le "DIU" & vous : <questions> fréquentes', 'Un DIU ou "dispositif intra-utérin" est un moyen de contraception inséré dans l\'utérus.'],
            'en' => ['The "IUD" & you', 'An IUD or "intrauterine device" is a contraceptive placed in the uterus.'],
            'he' => ['התקן תוך רחמי', 'התקן תוך רחמי הוא אמצעי מניעה.'],
        ]);
        $this->article($category, 'article-francais-seulement', now()->subDays(5), [
            'fr' => ['Article uniquement en français', 'Cet article n\'existe qu\'en français.'],
        ]);
        $this->article($category, 'brouillon', null, [
            'fr' => ['Brouillon', 'Pas encore publié.'],
        ]);
    }

    private function article(Category $category, string $slug, $publishedAt, array $translations): void
    {
        $article = Article::create(['title' => $slug, 'slug' => $slug, 'published_at' => $publishedAt]);
        $article->categories()->attach($category->id);

        foreach ($translations as $langue => [$title, $description]) {
            $article->traductions()->create([
                'langue' => $langue,
                'traduction' => [
                    'title' => $title,
                    'description' => $description,
                    'article' => '<h1>'.e($title).'</h1><p>'.e($description).'</p>',
                    'illustration_id' => null,
                ],
            ]);
        }
    }
}
```

- [ ] **Step 2: Vérifier qu'il s'exécute sur la base de prévisualisation**

Run (étapes 1 à 3 de la procédure partagée, puis) :
`php artisan tinker --execute='echo App\Models\Article::count()." articles, ".App\Models\FAQ::count()." faq";'`
Expected: `3 articles, 1 faq`

- [ ] **Step 3: Commit**

```bash
git add database/seeders/SeoPreviewSeeder.php
git commit -m ":seedling: Add local SEO preview seeder

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1: Langue déclarée et redirection de la racine

Les 255 pages déclarent `<html lang="en">` : `SetLocale` met la langue en session mais n'appelle jamais `App::setLocale`. La racine `/` sert le français sans redirection (doublon de `/fr`), et la langue de repli est `en`. La navbar pointe vers `/` puis ajoute `/#ancre`.

**Files:**
- Modify: `app/Http/Middleware/SetLocale.php` (réécriture complète)
- Modify: `resources/js/Components/Layouts/Navbar.tsx` (liens vers l'accueil)
- Modify: `tests/Feature/ExampleTest.php`
- Create: `tests/Feature/LocaleTest.php`

**Interfaces:**
- Produces: `App\Http\Middleware\SetLocale::LOCALES = ['fr', 'en', 'he']` (Tasks 3, 6, 7) ; après le middleware, `app()->getLocale()` vaut la langue de la page (Task 6).

- [ ] **Step 1: Écrire les tests**

`tests/Feature/LocaleTest.php` :

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_french_without_browser_preference(): void
    {
        $this->get('/')->assertRedirect('/fr');
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
        $this->get('/?utm_source=gbp')->assertRedirect('/fr?utm_source=gbp');
    }

    public function test_pages_declare_their_language(): void
    {
        foreach (['fr', 'en', 'he'] as $locale) {
            $this->get("/{$locale}")->assertOk()->assertSee("<html lang=\"{$locale}\">", false);
        }
    }
}
```

Dans `tests/Feature/ExampleTest.php`, remplacer `$response = $this->get('/');` par `$response = $this->get('/fr');` (la racine redirige désormais).

- [ ] **Step 2: Vérifier qu'ils échouent**

Run: `php artisan test tests/Feature/LocaleTest.php`
Expected: FAIL. Les tests de redirection reçoivent 200 au lieu de 302, et `test_pages_declare_their_language` ne trouve pas `<html lang="fr">`.

- [ ] **Step 3: Réécrire le middleware**

`app/Http/Middleware/SetLocale.php` :

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /** Supported locales; the first one is the fallback. */
    public const LOCALES = ['fr', 'en', 'he'];

    public function handle($request, Closure $next)
    {
        $sessionLocale = Session::get('locale');
        $routeLocale = in_array($request->route('locale'), self::LOCALES, true) ? $request->route('locale') : null;
        $locale = $routeLocale ?? $sessionLocale ?? $request->getPreferredLanguage(self::LOCALES);

        if (! $routeLocale) {
            $path = $request->path() === '/' ? '' : '/'.$request->path();
            $query = $request->getQueryString();

            return Redirect::to('/'.$locale.$path.($query ? '?'.$query : ''));
        }

        App::setLocale($locale);

        if ($locale !== $sessionLocale) {
            Session::put('locale', $locale);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Pointer les liens de la navbar vers l'accueil de la langue courante**

```bash
python3 - <<'PY'
import pathlib
p = pathlib.Path("resources/js/Components/Layouts/Navbar.tsx")
s = p.read_text()
s = s.replace('route("welcome") + "/#', 'route("welcome", { locale }) + "#')
s = s.replace('href={route("welcome")}', 'href={route("welcome", { locale })}')
assert 'route("welcome")' not in s
p.write_text(s)
PY
```

- [ ] **Step 5: Vérifier**

Run: `php artisan test`
Expected: tous les tests passent.

Run : procédure partagée (étapes 2 à 4), puis
`curl -s http://127.0.0.1:8123/fr | grep -o '<html lang="[a-z]*"'; curl -s http://127.0.0.1:8123/fr | grep -o 'href="[^"]*#about"' | head -1`
Expected: `<html lang="fr"` puis `href="http://127.0.0.1:8123/fr#about"`. Nettoyer (étape 6).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/SetLocale.php resources/js/Components/Layouts/Navbar.tsx tests/Feature/LocaleTest.php tests/Feature/ExampleTest.php
git commit -m ":globe_with_meridians: Declare the page language and redirect the root by locale

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 2: Un `<head>` fiable

Le `<Head>` d'Inertia sérialise les balises sans échapper les valeurs : un `"` dans une description d'article coupe la meta description et casse le HTML (constaté sur l'article « DIU 1 »). Les meta descriptions dépassent 160 caractères sur 240 pages ; 126 titres dépassent 60 caractères ; `og:locale` vaut `fr` au lieu de `fr_FR` ; le titre hébreu de l'accueil est au féminin.

**Files:**
- Create: `resources/js/Helpers/seo.ts`
- Create: `resources/js/Helpers/seo.test.ts`
- Modify: `package.json` (script `test:js`)
- Modify: `tsconfig.json` (exclure les fichiers de test)
- Modify: `resources/js/Layouts/GuestLayout.tsx` (réécriture complète)
- Modify: `resources/js/Constants/Translations/fr.json`, `en.json`, `he.json`

**Interfaces:**
- Produces:
  - `escapeAttr(value: string): string`
  - `pageTitle(title: string | undefined, brand: string, fallback: string, max = 60): string`
  - `metaDescription(text: string, max = 155): string`
  - `ogLocale(locale: string): string`
  - `GuestLayout` prop `ogType?: "website" | "article"` (défaut `"website"`)
  - clé de traduction `metaDescription`
  - script npm `test:js`

- [ ] **Step 1: Brancher le test runner de Node**

Dans `package.json`, section `scripts`, ajouter :

```json
"test:js": "node --test \"resources/js/**/*.test.ts\""
```

Dans `tsconfig.json`, ajouter au même niveau que `include` :

```json
"exclude": ["resources/js/**/*.test.ts"]
```

Node 22 exécute le TypeScript nativement. Les tests importent le module avec son extension (`./seo.ts`), ce que `tsc` refuserait, d'où l'exclusion.

- [ ] **Step 2: Écrire le test**

`resources/js/Helpers/seo.test.ts` :

```ts
import { test } from "node:test";
import assert from "node:assert/strict";
import { escapeAttr, metaDescription, ogLocale, pageTitle } from "./seo.ts";

test("escapeAttr neutralises characters that break an HTML attribute", () => {
    assert.equal(
        escapeAttr('Le "DIU" & <vous>'),
        "Le &quot;DIU&quot; &amp; &lt;vous&gt;"
    );
});

test("pageTitle appends the brand only when the result fits in 60 characters", () => {
    assert.equal(
        pageTitle("Ménopause", "Dr Claude Allouche", "Accueil"),
        "Ménopause - Dr Claude Allouche"
    );
    const long = "Métrorragies aux 2e et 3e trimestres de grossesse : que faire ?";
    assert.equal(pageTitle(long, "Dr Claude Allouche", "Accueil"), long);
    assert.equal(pageTitle(undefined, "Dr Claude Allouche", "Accueil"), "Accueil");
});

test("metaDescription collapses whitespace and cuts on a word boundary", () => {
    assert.equal(metaDescription("  Court   texte. "), "Court texte.");
    const cut = metaDescription("mot ".repeat(60));
    assert.ok(cut.length <= 155, `length ${cut.length}`);
    assert.ok(cut.endsWith("mot…"));
});

test("ogLocale maps site locales to Open Graph locales", () => {
    assert.equal(ogLocale("fr"), "fr_FR");
    assert.equal(ogLocale("en"), "en_US");
    assert.equal(ogLocale("he"), "he_IL");
});
```

- [ ] **Step 3: Vérifier qu'il échoue**

Run: `npm run test:js`
Expected: FAIL, `Cannot find module '.../resources/js/Helpers/seo.ts'`.

- [ ] **Step 4: Implémenter les fonctions**

`resources/js/Helpers/seo.ts` :

```ts
const OG_LOCALES: Record<string, string> = {
    fr: "fr_FR",
    en: "en_US",
    he: "he_IL",
};

export const ogLocale = (locale: string): string =>
    OG_LOCALES[locale] ?? OG_LOCALES.fr;

// Inertia's <Head> serialises tags to HTML strings without escaping, so every
// value placed in an attribute or in <title> has to be escaped by hand.
export const escapeAttr = (value: string): string =>
    value
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

export const pageTitle = (
    title: string | undefined,
    brand: string,
    fallback: string,
    max = 60
): string => {
    if (!title) return fallback;
    const branded = `${title} - ${brand}`;
    return branded.length <= max ? branded : title;
};

export const metaDescription = (text: string, max = 155): string => {
    const clean = text.replace(/\s+/g, " ").trim();
    if (clean.length <= max) return clean;
    const cut = clean.slice(0, max - 1);
    const lastSpace = cut.lastIndexOf(" ");
    return (lastSpace > 0 ? cut.slice(0, lastSpace) : cut) + "…";
};
```

- [ ] **Step 5: Vérifier que le test passe**

Run: `npm run test:js`
Expected: `# pass 4`, `# fail 0`.

- [ ] **Step 6: Ajouter les traductions**

```bash
python3 - <<'PY'
import json, pathlib
changes = {
    "fr": {"metaDescription": "Dr Claude Allouche, gynécologue-obstétricien à Raanana (Clinic 4 You) et Netanya : suivi gynécologique, grossesse, échographies et ménopause."},
    "en": {"metaDescription": "Dr Claude Allouche, obstetrician-gynecologist in Raanana (Clinic 4 You) and Netanya: gynecological care, pregnancy, ultrasounds and menopause."},
    "he": {
        "metaDescription": "ד״ר קלוד אלוש, רופא נשים ומיילד ברעננה (קליניק פור יו) ובנתניה: מעקב גינקולוגי, הריון, אולטרסאונד וגיל המעבר.",
        "title": "ד״ר קלוד אלוש - רופא נשים מומחה",
    },
}
for lang, values in changes.items():
    p = pathlib.Path(f"resources/js/Constants/Translations/{lang}.json")
    raw = p.read_text()
    data = json.loads(raw)
    data.update(values)
    assert len(data["metaDescription"]) <= 155
    p.write_text(json.dumps(data, ensure_ascii=False, indent=4) + ("\n" if raw.endswith("\n") else ""))
PY
```

- [ ] **Step 7: Réécrire `GuestLayout.tsx`**

```tsx
import OgImage from "@/Assets/images/og-image.jpg";
import Footer from "@/Components/Layouts/Footer";
import Navigation, { NavbarProps } from "@/Components/Layouts/Navbar";
import {
    escapeAttr,
    metaDescription,
    ogLocale,
    pageTitle,
} from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import { PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { Fragment, PropsWithChildren } from "react";

export default function GuestLayout({
    children,
    transparent,
    variant = "dark",
    title,
    description,
    keywords,
    imageUrl,
    ogType = "website",
}: PropsWithChildren<{
    transparent?: boolean;
    variant?: NavbarProps["variant"];
    description?: string;
    keywords?: string[];
    title?: string;
    imageUrl?: string;
    ogType?: "website" | "article";
}>) {
    const { t, locale } = useTranslation();
    const { ziggy } = usePage<PageProps>().props;
    const currentUrl = route(route().current() as string, route().params);
    const summary = metaDescription(description || t("metaDescription"));

    // Inertia's <Head> does not escape values: every text goes through escapeAttr.
    return (
        <Fragment>
            <Head>
                <title>
                    {escapeAttr(pageTitle(title, t("name"), t("title")))}
                </title>
                <meta name="description" content={escapeAttr(summary)} />
                <meta
                    name="keywords"
                    content={escapeAttr(
                        keywords ? keywords.join(", ") : t("keywords")
                    )}
                />
                <link rel="canonical" href={currentUrl} />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                />
                <meta
                    property="og:title"
                    content={escapeAttr(title ?? t("title"))}
                />
                <meta property="og:description" content={escapeAttr(summary)} />
                <meta property="og:url" content={currentUrl} />
                <meta property="og:type" content={ogType} />
                <meta
                    property="og:image"
                    content={escapeAttr(
                        imageUrl ?? new URL(OgImage, ziggy.url).href
                    )}
                />
                <meta property="og:locale" content={ogLocale(locale)} />
                <link
                    rel="alternate"
                    hrefLang="fr"
                    href={route("welcome", { locale: "fr" })}
                />
                <link
                    rel="alternate"
                    hrefLang="en"
                    href={route("welcome", { locale: "en" })}
                />
                <link
                    rel="alternate"
                    hrefLang="he"
                    href={route("welcome", { locale: "he" })}
                />
            </Head>
            <Navigation transparent={transparent} variant={variant} />
            {children}
            <Footer />
        </Fragment>
    );
}
```

- [ ] **Step 8: Vérifier le rendu serveur**

Run: `npx tsc --noEmit -p .`
Expected: uniquement les 4 erreurs préexistantes.

Run : procédure partagée (étapes 2 à 4), puis
`curl -s http://127.0.0.1:8123/fr/articles/le-diu-et-vous | grep -oE '<title[^>]*>[^<]*</title>|<meta name="description"[^>]*>|<meta property="og:locale"[^>]*>'`

Expected (trois lignes) :
- `<title inertia>Le &quot;DIU&quot; &amp; &lt;questions&gt; fréquentes - Dr Claude Allouche</title>`
- `<meta name="description" content="Un DIU ou &quot;dispositif intra-utérin&quot; est un moyen de contraception inséré dans l'utérus." inertia>`
- `<meta property="og:locale" content="fr_FR" inertia>`

Ouvrir aussi http://127.0.0.1:8123/he et vérifier que l'onglet affiche « ד״ר קלוד אלוש - רופא נשים מומחה ». Nettoyer (étape 6).

- [ ] **Step 9: Commit**

```bash
git add package.json tsconfig.json resources/js/Helpers/seo.ts resources/js/Helpers/seo.test.ts resources/js/Layouts/GuestLayout.tsx resources/js/Constants/Translations
git commit -m ":mag: Escape head metadata and cap titles and descriptions

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 3: Ne lier que les traductions qui existent

Sur 246 articles, les hreflang pointent vers l'accueil de chaque langue. Les drapeaux génèrent 38 liens 404 vers des traductions absentes (13 articles en français seulement, 12 sans hébreu). Il n'y a pas de `x-default`.

**Files:**
- Modify: `app/Http/Controllers/ArticleController.php` (réécriture complète)
- Modify: `resources/js/Layouts/GuestLayout.tsx` (réécriture complète)
- Modify: `resources/js/Components/molecules/FlagSelect.tsx` (réécriture complète)
- Modify: `resources/js/Pages/Articles/Show.tsx` (réécriture complète)
- Create: `tests/Feature/ArticleTranslationsTest.php`

**Interfaces:**
- Consumes: `SetLocale::LOCALES` (Task 1) ; `GuestLayout` de la Task 2.
- Produces: prop Inertia `availableLocales: string[]` sur `Articles/Show`, dans l'ordre de `SetLocale::LOCALES` ; prop `GuestLayout` `alternateLocales?: string[]` (défaut : les trois langues).

- [ ] **Step 1: Écrire le test**

`tests/Feature/ArticleTranslationsTest.php` :

```php
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
```

- [ ] **Step 2: Vérifier qu'il échoue**

Run: `php artisan test tests/Feature/ArticleTranslationsTest.php`
Expected: FAIL avec `Property [availableLocales] does not exist.`

- [ ] **Step 3: Exposer les langues disponibles**

`app/Http/Controllers/ArticleController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Article;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends Controller
{
    public function index()
    {
        return Inertia::render('Articles/Index', [
            'filters' => Request::all('search'),
            'articles' => new ResourceCollection(
                Article::with(['categories'])
                ->withTraductionInLocale()
                ->filter(Request::only('search'))
                ->published()
                ->orderBy('published_at', 'desc')
                ->paginate(15)
            )
        ]);
    }

    public function show(string $locale, Article $article)
    {
        // Logged-in users can preview drafts, scheduled and deleted articles.
        if (! Auth::check() && ! Article::published()->whereKey($article->id)->exists()) {
            throw new NotFoundHttpException();
        }

        $availableLocales = array_values(array_intersect(
            SetLocale::LOCALES,
            $article->traductions()->pluck('langue')->all()
        ));

        $article = $article->load(['categories', 'traductions' => fn ($query) => $query->where('langue', $locale), 'keywords']);

        if($article->traductions->isEmpty()) {
           throw new NotFoundHttpException();
        }

        return Inertia::render('Articles/Show', [
            'article' => $article,
            'availableLocales' => $availableLocales,
        ]);
    }
}
```

- [ ] **Step 4: Vérifier que le test passe**

Run: `php artisan test tests/Feature/ArticleTranslationsTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: hreflang par page et `x-default` dans `GuestLayout.tsx`**

```tsx
import OgImage from "@/Assets/images/og-image.jpg";
import Footer from "@/Components/Layouts/Footer";
import Navigation, { NavbarProps } from "@/Components/Layouts/Navbar";
import LANGUES from "@/Constants/langues";
import {
    escapeAttr,
    metaDescription,
    ogLocale,
    pageTitle,
} from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import { PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { Fragment, PropsWithChildren } from "react";

export default function GuestLayout({
    children,
    transparent,
    variant = "dark",
    title,
    description,
    keywords,
    imageUrl,
    ogType = "website",
    alternateLocales = LANGUES.map((langue) => langue.value),
}: PropsWithChildren<{
    transparent?: boolean;
    variant?: NavbarProps["variant"];
    description?: string;
    keywords?: string[];
    title?: string;
    imageUrl?: string;
    ogType?: "website" | "article";
    alternateLocales?: string[];
}>) {
    const { t, locale } = useTranslation();
    const { ziggy } = usePage<PageProps>().props;
    const currentRoute = route().current() as string;
    const currentUrl = route(currentRoute, route().params);
    const urlIn = (lang: string) =>
        route(currentRoute, { ...route().params, locale: lang });
    const defaultLocale = alternateLocales.includes("fr")
        ? "fr"
        : alternateLocales[0];
    const summary = metaDescription(description || t("metaDescription"));

    // Inertia's <Head> does not escape values: every text goes through escapeAttr.
    return (
        <Fragment>
            <Head>
                <title>
                    {escapeAttr(pageTitle(title, t("name"), t("title")))}
                </title>
                <meta name="description" content={escapeAttr(summary)} />
                <meta
                    name="keywords"
                    content={escapeAttr(
                        keywords ? keywords.join(", ") : t("keywords")
                    )}
                />
                <link rel="canonical" href={currentUrl} />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                />
                <meta
                    property="og:title"
                    content={escapeAttr(title ?? t("title"))}
                />
                <meta property="og:description" content={escapeAttr(summary)} />
                <meta property="og:url" content={currentUrl} />
                <meta property="og:type" content={ogType} />
                <meta
                    property="og:image"
                    content={escapeAttr(
                        imageUrl ?? new URL(OgImage, ziggy.url).href
                    )}
                />
                <meta property="og:locale" content={ogLocale(locale)} />
                {alternateLocales.map((lang) => (
                    <link
                        key={lang}
                        rel="alternate"
                        hrefLang={lang}
                        href={urlIn(lang)}
                    />
                ))}
                <link
                    rel="alternate"
                    hrefLang="x-default"
                    href={urlIn(defaultLocale)}
                />
            </Head>
            <Navigation transparent={transparent} variant={variant} />
            {children}
            <Footer />
        </Fragment>
    );
}
```

- [ ] **Step 6: Filtrer les drapeaux**

`resources/js/Components/molecules/FlagSelect.tsx` :

```tsx
// Import des images des drapeaux
import Flag from "../atoms/Flag";
import { usePage } from "@inertiajs/react";
import { PageProps } from "@/types";
import Article from "@/Interfaces/Article";

const FlagSelect = () => {
    const { locale, article, availableLocales } = usePage<
        PageProps<{
            article?: Article;
            availableLocales?: string[];
        }>
    >().props;

    // On an article page, only offer the translations that exist.
    const languages = [
        { code: "fr", label: "Français" },
        { code: "en", label: "English" },
        { code: "he", label: "עברית" },
    ].filter(
        (lang) => !availableLocales || availableLocales.includes(lang.code)
    );

    return (
        <div className="d-flex gap-2 align-items-center">
            {languages.map((lang) => (
                <a
                    key={lang.code}
                    href={route(route().current() as string, {
                        ...(article && { article: article.slug }),
                        locale: lang.code,
                    })}
                    className={`flag-link ${locale === lang.code ? "active" : ""}`}
                    title={lang.label}
                    style={{
                        opacity: locale === lang.code ? 1 : 0.6,
                        transition: "opacity 0.2s",
                        cursor: "pointer",
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.opacity = "1")}
                    onMouseLeave={(e) =>
                        (e.currentTarget.style.opacity =
                            locale === lang.code ? "1" : "0.6")
                    }
                >
                    <Flag lang={lang.code} />
                </a>
            ))}
        </div>
    );
};

export default FlagSelect;
```

- [ ] **Step 7: Transmettre les langues depuis la page article**

`resources/js/Pages/Articles/Show.tsx` :

```tsx
import Article from "@/Interfaces/Article";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { FC } from "react";
import { Col, Container, Row } from "react-bootstrap";

const ArticleShow: FC<
    PageProps<{
        article: Article;
        availableLocales: string[];
    }>
> = ({ article, availableLocales }) => {
    return (
        <GuestLayout
            variant="light"
            transparent={false}
            title={article.traductions[0].traduction.title}
            description={article.traductions[0].traduction.description}
            keywords={article.keywords.map((keyword) => keyword.label)}
            imageUrl={article.traductions[0].traduction.illustration?.link}
            alternateLocales={availableLocales}
        >
            <div className="pattern-square"></div>
            <div className="py-xl-9 py-4">
                <Container>
                    <Row>
                        <Col lg={8} className="offset-lg-2">
                            <article
                                dangerouslySetInnerHTML={{
                                    __html: article.traductions[0].traduction
                                        .article,
                                }}
                            />
                        </Col>
                    </Row>
                </Container>
            </div>
        </GuestLayout>
    );
};

export default ArticleShow;
```

- [ ] **Step 8: Vérifier le rendu serveur**

Run: `npx tsc --noEmit -p .` puis `php artisan test`
Expected: les 4 erreurs TypeScript préexistantes uniquement ; tous les tests PHP passent.

Run : procédure partagée (étapes 2 à 4), puis :

```bash
P=http://127.0.0.1:8123/fr/articles/article-francais-seulement
curl -s $P | grep -o '<link rel="alternate"[^>]*>'
curl -s $P | grep -c '/en/articles/article-francais-seulement\|/he/articles/article-francais-seulement'
curl -s http://127.0.0.1:8123/fr/articles/le-diu-et-vous | grep -o 'hrefLang="[a-z-]*"' | wc -l
```

Expected :
- deux balises : `hrefLang="fr"` et `hrefLang="x-default"`, toutes deux vers `/fr/articles/article-francais-seulement` ;
- puis `0` ;
- puis `4` (fr, en, he, x-default).

Nettoyer (étape 6).

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/ArticleController.php resources/js/Layouts/GuestLayout.tsx resources/js/Components/molecules/FlagSelect.tsx resources/js/Pages/Articles/Show.tsx tests/Feature/ArticleTranslationsTest.php
git commit -m ":globe_with_meridians: Link only to existing article translations

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 4: Page article — H1, auteur et dates

233 des 247 articles n'ont aucun H1 : le titre n'apparaît que dans `<title>`. La page n'indique ni auteur ni date, un signal faible pour un contenu médical (YMYL). `og:type` vaut `website`.

**Files:**
- Create: `resources/js/Helpers/dates.ts`
- Create: `resources/js/Helpers/dates.test.ts`
- Modify: `resources/js/Helpers/seo.ts` (ajout de `demoteHeadings`)
- Modify: `resources/js/Helpers/seo.test.ts` (ajout d'un test)
- Modify: `resources/js/Layouts/GuestLayout.tsx` (réécriture complète)
- Modify: `resources/js/Pages/Articles/Show.tsx` (réécriture complète)
- Modify: `resources/js/Constants/Translations/fr.json`, `en.json`, `he.json`

**Interfaces:**
- Consumes: `GuestLayout` et `Show.tsx` de la Task 3.
- Produces:
  - `parseDate(value: string | null | undefined): DateTime | null`
  - `formatDate(value: string | null | undefined, locale: string): string | null` (Task 5)
  - `demoteHeadings(html: string): string`
  - props `GuestLayout` `publishedTime?: string | null` et `modifiedTime?: string | null` (ISO 8601)
  - clés de traduction `article.byline`, `article.published`, `article.updated`

- [ ] **Step 1: Écrire les tests**

`resources/js/Helpers/dates.test.ts` :

```ts
import { test } from "node:test";
import assert from "node:assert/strict";
import { formatDate, parseDate } from "./dates.ts";

test("parseDate reads SQL and ISO timestamps", () => {
    assert.equal(parseDate("2025-02-23 17:18:35")?.toISODate(), "2025-02-23");
    assert.equal(
        parseDate("2025-02-23T17:18:35.000000Z")?.toISODate(),
        "2025-02-23"
    );
    assert.equal(parseDate(null), null);
    assert.equal(parseDate("not a date"), null);
});

test("formatDate localises the date", () => {
    assert.equal(formatDate("2025-02-23 17:18:35", "fr"), "23 février 2025");
    assert.equal(formatDate("2025-02-23 17:18:35", "en"), "February 23, 2025");
    assert.equal(formatDate(undefined, "fr"), null);
});
```

Ajouter à la fin de `resources/js/Helpers/seo.test.ts` :

```ts
test("demoteHeadings turns editor h1 into h2", () => {
    assert.equal(
        demoteHeadings('<h1 class="x">A</h1><p>b</p><H1>C</H1><h10>'),
        '<h2 class="x">A</h2><p>b</p><h2>C</h2><h10>'
    );
});
```

et remplacer sa ligne d'import par :

```ts
import { demoteHeadings, escapeAttr, metaDescription, ogLocale, pageTitle } from "./seo.ts";
```

- [ ] **Step 2: Vérifier qu'ils échouent**

Run: `npm run test:js`
Expected: FAIL. `dates.ts` est introuvable et `demoteHeadings` n'est pas exporté.

- [ ] **Step 3: Implémenter**

`resources/js/Helpers/dates.ts` :

```ts
import { DateTime } from "luxon";

// published_at arrives as "yyyy-MM-dd HH:mm:ss" (no cast), timestamps as ISO 8601.
export const parseDate = (
    value: string | null | undefined
): DateTime | null => {
    if (!value) return null;
    const sql = DateTime.fromSQL(value, { zone: "utc" });
    const date = sql.isValid ? sql : DateTime.fromISO(value, { zone: "utc" });
    return date.isValid ? date : null;
};

export const formatDate = (
    value: string | null | undefined,
    locale: string
): string | null =>
    parseDate(value)?.setLocale(locale).toLocaleString(DateTime.DATE_FULL) ??
    null;
```

Ajouter à la fin de `resources/js/Helpers/seo.ts` :

```ts
// The article page renders its title as the only <h1>: headings typed in the
// editor move down one level.
export const demoteHeadings = (html: string): string =>
    html.replace(/<(\/?)h1(?=[\s>])/gi, "<$1h2");
```

- [ ] **Step 4: Vérifier que les tests passent**

Run: `npm run test:js`
Expected: `# pass 7`, `# fail 0`.

- [ ] **Step 5: Ajouter les traductions**

```bash
python3 - <<'PY'
import json, pathlib
article = {
    "fr": {"byline": "Par le Dr Claude Allouche, gynécologue-obstétricien", "published": "Publié le %{date}", "updated": "Mis à jour le %{date}"},
    "en": {"byline": "By Dr Claude Allouche, obstetrician-gynecologist", "published": "Published on %{date}", "updated": "Updated on %{date}"},
    "he": {"byline": "מאת ד״ר קלוד אלוש, רופא נשים ומיילד", "published": "פורסם ב-%{date}", "updated": "עודכן ב-%{date}"},
}
for lang, values in article.items():
    p = pathlib.Path(f"resources/js/Constants/Translations/{lang}.json")
    raw = p.read_text()
    data = json.loads(raw)
    data["article"] = values
    p.write_text(json.dumps(data, ensure_ascii=False, indent=4) + ("\n" if raw.endswith("\n") else ""))
PY
```

- [ ] **Step 6: Balises `article:*` dans `GuestLayout.tsx`**

```tsx
import OgImage from "@/Assets/images/og-image.jpg";
import Footer from "@/Components/Layouts/Footer";
import Navigation, { NavbarProps } from "@/Components/Layouts/Navbar";
import LANGUES from "@/Constants/langues";
import {
    escapeAttr,
    metaDescription,
    ogLocale,
    pageTitle,
} from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import { PageProps } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { Fragment, PropsWithChildren } from "react";

export default function GuestLayout({
    children,
    transparent,
    variant = "dark",
    title,
    description,
    keywords,
    imageUrl,
    ogType = "website",
    alternateLocales = LANGUES.map((langue) => langue.value),
    publishedTime,
    modifiedTime,
}: PropsWithChildren<{
    transparent?: boolean;
    variant?: NavbarProps["variant"];
    description?: string;
    keywords?: string[];
    title?: string;
    imageUrl?: string;
    ogType?: "website" | "article";
    alternateLocales?: string[];
    publishedTime?: string | null;
    modifiedTime?: string | null;
}>) {
    const { t, locale } = useTranslation();
    const { ziggy } = usePage<PageProps>().props;
    const currentRoute = route().current() as string;
    const currentUrl = route(currentRoute, route().params);
    const urlIn = (lang: string) =>
        route(currentRoute, { ...route().params, locale: lang });
    const defaultLocale = alternateLocales.includes("fr")
        ? "fr"
        : alternateLocales[0];
    const summary = metaDescription(description || t("metaDescription"));

    // Inertia's <Head> does not escape values: every text goes through escapeAttr.
    return (
        <Fragment>
            <Head>
                <title>
                    {escapeAttr(pageTitle(title, t("name"), t("title")))}
                </title>
                <meta name="description" content={escapeAttr(summary)} />
                <meta
                    name="keywords"
                    content={escapeAttr(
                        keywords ? keywords.join(", ") : t("keywords")
                    )}
                />
                <link rel="canonical" href={currentUrl} />
                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                />
                <meta
                    property="og:title"
                    content={escapeAttr(title ?? t("title"))}
                />
                <meta property="og:description" content={escapeAttr(summary)} />
                <meta property="og:url" content={currentUrl} />
                <meta property="og:type" content={ogType} />
                <meta
                    property="og:image"
                    content={escapeAttr(
                        imageUrl ?? new URL(OgImage, ziggy.url).href
                    )}
                />
                <meta property="og:locale" content={ogLocale(locale)} />
                {publishedTime && (
                    <meta
                        property="article:published_time"
                        content={publishedTime}
                    />
                )}
                {modifiedTime && (
                    <meta
                        property="article:modified_time"
                        content={modifiedTime}
                    />
                )}
                {alternateLocales.map((lang) => (
                    <link
                        key={lang}
                        rel="alternate"
                        hrefLang={lang}
                        href={urlIn(lang)}
                    />
                ))}
                <link
                    rel="alternate"
                    hrefLang="x-default"
                    href={urlIn(defaultLocale)}
                />
            </Head>
            <Navigation transparent={transparent} variant={variant} />
            {children}
            <Footer />
        </Fragment>
    );
}
```

- [ ] **Step 7: Réécrire `Show.tsx`**

```tsx
import { formatDate, parseDate } from "@/Helpers/dates";
import { demoteHeadings } from "@/Helpers/seo";
import useTranslation from "@/Hooks/useTranslation";
import Article from "@/Interfaces/Article";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { FC } from "react";
import { Col, Container, Row } from "react-bootstrap";

const ArticleShow: FC<
    PageProps<{
        article: Article;
        availableLocales: string[];
    }>
> = ({ article, availableLocales }) => {
    const { t, locale } = useTranslation();
    const traduction = article.traductions[0].traduction;
    const published = formatDate(article.published_at, locale);
    const updated = formatDate(article.updated_at, locale);

    return (
        <GuestLayout
            variant="light"
            transparent={false}
            title={traduction.title}
            description={traduction.description}
            keywords={article.keywords.map((keyword) => keyword.label)}
            imageUrl={traduction.illustration?.link}
            alternateLocales={availableLocales}
            ogType="article"
            publishedTime={parseDate(article.published_at)?.toISO()}
            modifiedTime={parseDate(article.updated_at)?.toISO()}
        >
            <div className="pattern-square"></div>
            <div className="py-xl-9 py-4">
                <Container>
                    <Row>
                        <Col lg={8} className="offset-lg-2">
                            <header className="mb-5">
                                <h1 className="mb-3">{traduction.title}</h1>
                                <p className="mb-0">
                                    <a
                                        href={
                                            route("welcome", { locale }) +
                                            "#about"
                                        }
                                    >
                                        {t("article.byline")}
                                    </a>
                                </p>
                                <p className="small mb-0">
                                    {published &&
                                        t("article.published", {
                                            date: published,
                                        })}
                                    {updated && updated !== published && (
                                        <>
                                            {" · "}
                                            {t("article.updated", {
                                                date: updated,
                                            })}
                                        </>
                                    )}
                                </p>
                            </header>
                            <article
                                dangerouslySetInnerHTML={{
                                    __html: demoteHeadings(traduction.article),
                                }}
                            />
                        </Col>
                    </Row>
                </Container>
            </div>
        </GuestLayout>
    );
};

export default ArticleShow;
```

- [ ] **Step 8: Vérifier le rendu serveur**

Run: `npx tsc --noEmit -p .`
Expected: uniquement les 4 erreurs préexistantes.

Run : procédure partagée (étapes 2 à 4), puis :

```bash
P=http://127.0.0.1:8123/fr/articles/le-diu-et-vous
curl -s $P | grep -o '<h1' | wc -l
curl -s $P | grep -oE 'og:type" content="[a-z]+"|article:published_time" content="[^"]+"|Publié le [^<]+'
```

Expected :
- `1` ;
- `og:type" content="article"` ;
- `article:published_time" content="20…` ;
- `Publié le …` (date d'il y a 10 jours).

Ouvrir http://127.0.0.1:8123/he/articles/le-diu-et-vous et vérifier la ligne « פורסם ב-… ». Nettoyer (étape 6).

- [ ] **Step 9: Commit**

```bash
git add resources/js/Helpers resources/js/Layouts/GuestLayout.tsx resources/js/Pages/Articles/Show.tsx resources/js/Constants/Translations
git commit -m ":memo: Show article title as H1 with author and dates

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 5: Cartes d'articles — vraie date, auteur, chargement différé

Toutes les cartes affichent « Nov 26, 2023 », codé en dur. Le lien auteur pointe vers `#`, et aucune image n'a `loading="lazy"`.

**Files:**
- Modify: `resources/js/Components/molecules/ArticleItem.tsx` (réécriture complète)

**Interfaces:**
- Consumes: `formatDate`, `parseDate` (Task 4).

- [ ] **Step 1: Réécrire le composant**

```tsx
import Avatar from "@/Assets/images/avatar.jpg";
import { formatDate, parseDate } from "@/Helpers/dates";
import useTranslation from "@/Hooks/useTranslation";
import Article from "@/Interfaces/Article";
import { Link } from "@inertiajs/react";
import classNames from "classnames";
import { FC } from "react";
import { Col, Figure, Image } from "react-bootstrap";

interface ArticleItemProps {
    article: Article;
}

const ArticleItem: FC<ArticleItemProps> = ({ article }) => {
    const { locale } = useTranslation();
    const traduction = article.traductions[0].traduction;
    const href = route("articles.show", { article: article.slug, locale });

    return (
        <Col as="article" lg={4} md={6} xs={12}>
            <Figure className="mb-4 zoom-img w-100">
                <Link href={href}>
                    <Image
                        src={
                            traduction.illustration
                                ? traduction.illustration.link
                                : ""
                        }
                        width={"100%"}
                        height={400}
                        style={{ objectFit: "cover" }}
                        alt={traduction.title}
                        loading="lazy"
                        decoding="async"
                        rounded
                    />
                </Link>
            </Figure>
            {article.categories.map((category, index) => (
                <span
                    key={category.id}
                    style={{
                        backgroundColor: category.background_color,
                        color: category.text_color,
                    }}
                    className={classNames("badge badge-pill", {
                        "ms-1": index > 0,
                    })}
                >
                    {category.label}
                </span>
            ))}
            <h3 className="my-3 lh-base h4">
                <Link href={href} className="text-reset">
                    {traduction.title}
                </Link>
            </h3>
            <div className="d-flex align-items-center justify-content-between mb-3 mb-md-0">
                <div className="d-flex align-items-center">
                    <Image
                        src={Avatar}
                        alt="Dr Claude Allouche"
                        className="avatar avatar-xs rounded-circle"
                    />
                    <div className="ms-2">
                        <a
                            href={route("welcome", { locale }) + "#about"}
                            className="text-reset fs-6"
                        >
                            Dr Claude Allouche
                        </a>
                    </div>
                </div>
                <div className="ms-3">
                    <time
                        className="fs-6"
                        dateTime={
                            parseDate(article.published_at)?.toISODate() ??
                            undefined
                        }
                    >
                        {formatDate(article.published_at, locale)}
                    </time>
                </div>
            </div>
        </Col>
    );
};

export default ArticleItem;
```

- [ ] **Step 2: Vérifier le rendu serveur**

Run: `npx tsc --noEmit -p .`
Expected: uniquement les 4 erreurs préexistantes.

Run : procédure partagée (étapes 2 à 4), puis :

```bash
curl -s http://127.0.0.1:8123/fr | grep -c 'Nov 26, 2023'
curl -s http://127.0.0.1:8123/fr | grep -oE '<time[^>]*>[^<]+</time>|loading="lazy"' | sort -u
```

Expected :
- `0` ;
- `loading="lazy"` et deux balises `<time dateTime="20…">` portant des dates en français.

Nettoyer (étape 6).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/molecules/ArticleItem.tsx
git commit -m ":bug: Show real publication dates on article cards

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 6: Données structurées JSON-LD

Aucune page ne contient de JSON-LD. Le JSON-LD est rendu par `app.blade.php` côté serveur : il est présent dans le HTML que lisent les robots, que le SSR réponde ou non, et il se teste avec PHPUnit.

**Files:**
- Create: `app/Seo/StructuredData.php`
- Create: `public/images/og-image.jpg` (copie de `resources/js/Assets/images/og-image.jpg`)
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `app/Http/Controllers/ArticleController.php` (réécriture complète)
- Modify: `resources/views/app.blade.php`
- Create: `tests/Feature/StructuredDataTest.php`

**Interfaces:**
- Consumes: `app()->getLocale()` (Task 1) ; `ArticleController` de la Task 3.
- Produces:
  - `App\Seo\StructuredData::home(string $locale): array`
  - `StructuredData::article(Article $article, Traduction $traduction, string $locale): array`
  - prop Inertia `schema` sur `Welcome` et `Articles/Show`

- [ ] **Step 1: Écrire les tests**

`tests/Feature/StructuredDataTest.php` :

```php
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
    }

    public function test_script_tags_in_titles_cannot_break_out_of_the_json_ld_block(): void
    {
        $this->article('xss', 'Avant </script><script>alert(1)</script>', now()->subDay());

        $response = $this->get('/fr/articles/xss');

        $this->assertStringNotContainsString('</script><script>alert(1)', $response->getContent());
        $this->assertSame('Avant </script><script>alert(1)</script>', $this->jsonLd($response)['headline']);
    }
}
```

- [ ] **Step 2: Vérifier qu'ils échouent**

Run: `php artisan test tests/Feature/StructuredDataTest.php`
Expected: FAIL, `No JSON-LD block found` (3 fois).

- [ ] **Step 3: Écrire le générateur**

`app/Seo/StructuredData.php` :

```php
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
```

Copier l'image référencée par `image` :

```bash
mkdir -p public/images && cp resources/js/Assets/images/og-image.jpg public/images/og-image.jpg
```

- [ ] **Step 4: Fournir le JSON-LD aux pages**

Dans `app/Http/Controllers/HomeController.php` :
- ajouter `use App\Seo\StructuredData;` et `use Illuminate\Support\Facades\App;` aux imports ;
- ajouter `'schema' => StructuredData::home(App::getLocale()),` juste après la ligne `'phpVersion' => PHP_VERSION,`.

`app/Http/Controllers/ArticleController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Article;
use App\Seo\StructuredData;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends Controller
{
    public function index()
    {
        return Inertia::render('Articles/Index', [
            'filters' => Request::all('search'),
            'articles' => new ResourceCollection(
                Article::with(['categories'])
                ->withTraductionInLocale()
                ->filter(Request::only('search'))
                ->published()
                ->orderBy('published_at', 'desc')
                ->paginate(15)
            )
        ]);
    }

    public function show(string $locale, Article $article)
    {
        // Logged-in users can preview drafts, scheduled and deleted articles.
        if (! Auth::check() && ! Article::published()->whereKey($article->id)->exists()) {
            throw new NotFoundHttpException();
        }

        $availableLocales = array_values(array_intersect(
            SetLocale::LOCALES,
            $article->traductions()->pluck('langue')->all()
        ));

        $article = $article->load(['categories', 'traductions' => fn ($query) => $query->where('langue', $locale), 'keywords']);

        if($article->traductions->isEmpty()) {
           throw new NotFoundHttpException();
        }

        return Inertia::render('Articles/Show', [
            'article' => $article,
            'availableLocales' => $availableLocales,
            'schema' => StructuredData::article($article, $article->traductions->first(), $locale),
        ]);
    }
}
```

Dans `resources/views/app.blade.php`, insérer juste avant la ligne `@inertiaHead` :

```blade
        @isset($page['props']['schema'])
            <script type="application/ld+json">{!! json_encode($page['props']['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endisset
```

`JSON_HEX_TAG` encode `<` et `>`, ce qui empêche un `</script>` de sortir du bloc.

- [ ] **Step 5: Vérifier que les tests passent**

Run: `php artisan test`
Expected: tous les tests passent, dont les 3 nouveaux.

- [ ] **Step 6: Valider le balisage**

Run : procédure partagée (étapes 2 à 4), puis `curl -s http://127.0.0.1:8123/fr | grep -o '<script type="application/ld+json">.*</script>' | head -c 400`
Expected: un objet JSON qui commence par `{"@context":"https://schema.org","@graph":[{"@type":"Physician"`. Nettoyer (étape 6).

Après le déploiement (Task 13), passer `https://claude-allouche.com/fr` et une page article dans https://validator.schema.org (0 erreur attendue).

- [ ] **Step 7: Commit**

```bash
git add app/Seo/StructuredData.php app/Http/Controllers/HomeController.php app/Http/Controllers/ArticleController.php resources/views/app.blade.php public/images/og-image.jpg tests/Feature/StructuredDataTest.php
git commit -m ":sparkles: Add Physician, clinic and article structured data

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 7: Sitemap dynamique avec alternates, et `llms.txt`

Le sitemap est généré une seule fois, au build Docker : la tâche planifiée quotidienne ne tourne jamais, car `supervisord` ne lance pas le scheduler. Il n'a pas d'alternates hreflang et inclut `/`, qui duplique `/fr`. `/llms.txt` n'existe pas.

**Files:**
- Create: `app/Seo/SitemapBuilder.php`
- Create: `app/Http/Controllers/SitemapController.php`
- Create: `public/llms.txt`
- Modify: `routes/web.php`
- Modify: `routes/console.php`
- Modify: `app/Models/Article.php` (méthode `booted`)
- Modify: `Dockerfile.production` (retirer la génération du sitemap)
- Delete: `app/Console/Commands/GenerateSitemap.php`
- Create: `tests/Feature/SitemapTest.php`

**Interfaces:**
- Consumes: `SetLocale::LOCALES` (Task 1) ; `Article::published()` (déjà présent).
- Produces:
  - `App\Seo\SitemapBuilder::render(): string`
  - `SitemapBuilder::forget(): void`
  - la route `GET /sitemap.xml`, nommée `sitemap`

- [ ] **Step 1: Écrire les tests**

`tests/Feature/SitemapTest.php` :

```php
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
}
```

- [ ] **Step 2: Vérifier qu'ils échouent**

Run: `php artisan test tests/Feature/SitemapTest.php`
Expected: FAIL. En test, `/sitemap.xml` passe par le groupe de routes localisées et redirige vers `/fr/sitemap.xml`, d'où `Expected response status code [200] but received 302`.

- [ ] **Step 3: Implémenter le sitemap**

`app/Seo/SitemapBuilder.php` :

```php
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
```

`app/Http/Controllers/SitemapController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Seo\SitemapBuilder;

class SitemapController extends Controller
{
    public function __invoke()
    {
        return response(SitemapBuilder::render(), 200, ['Content-Type' => 'application/xml']);
    }
}
```

Dans `routes/web.php`, ajouter `use App\Http\Controllers\SitemapController;` aux imports, et cette ligne juste avant `Route::post('/contact', ...)`. Elle doit rester avant le groupe `/{locale?}`, qui sinon intercepterait `sitemap.xml` comme une langue :

```php
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
```

Dans `app/Models/Article.php`, ajouter `use App\Seo\SitemapBuilder;` aux imports, et cette méthode juste après `protected $fillable = [...]` :

```php
    protected static function booted(): void
    {
        $refreshSitemap = fn () => SitemapBuilder::forget();

        static::saved($refreshSitemap);
        static::deleted($refreshSitemap);
        static::restored($refreshSitemap);
    }
```

- [ ] **Step 4: Retirer l'ancienne génération statique**

```bash
git rm app/Console/Commands/GenerateSitemap.php
printf '<?php\n' > routes/console.php
python3 - <<'PY'
import pathlib
p = pathlib.Path("Dockerfile.production")
s = p.read_text()
old = "# Generate sitemap.xml\nRUN php artisan sitemap:generate\n\n"
assert old in s
p.write_text(s.replace(old, ""))
PY
rm -f public/sitemap.xml
```

- [ ] **Step 5: Vérifier que les tests passent**

Run: `php artisan test`
Expected: tous les tests passent.

- [ ] **Step 6: Créer `public/llms.txt`**

```markdown
# Dr Claude Allouche — Gynécologue-obstétricien

> Site officiel du Dr Claude Allouche, gynécologue-obstétricien francophone en Israël. Consultations à Raanana (Clinic 4 You, Hamasger 8) et à Netanya (Migdal Hahaloutsim, 6 rehov Petah Tikva, 10e étage, salle 1002) ; accouchements et chirurgie à l'hôpital Laniado. Articles de santé de la femme en français, anglais et hébreu.

## Pages principales

- [Accueil (français)](https://claude-allouche.com/fr): parcours, services, FAQ, contact
- [Home (English)](https://claude-allouche.com/en)
- [דף הבית (עברית)](https://claude-allouche.com/he)
- [Articles (français)](https://claude-allouche.com/fr/articles)
- [Articles (English)](https://claude-allouche.com/en/articles)
- [מאמרים (עברית)](https://claude-allouche.com/he/articles)

## Contact

- Téléphone et WhatsApp : +972 58-726-0264
- E-mail : drallouchegyneco@gmail.com

## Optional

- [Sitemap](https://claude-allouche.com/sitemap.xml): liste complète des articles dans les trois langues
```

- [ ] **Step 7: Vérifier en local**

Run : procédure partagée (étapes 2 à 4), puis :

```bash
curl -s -o /dev/null -w '%{http_code} %{content_type}\n' http://127.0.0.1:8123/llms.txt
curl -s http://127.0.0.1:8123/sitemap.xml | grep -c '<loc>'
```

Expected :
- `200 text/plain; charset=UTF-8` ;
- `10` : 6 pages fixes, 3 traductions de `le-diu-et-vous` et 1 de `article-francais-seulement`.

Nettoyer (étape 6).

- [ ] **Step 8: Commit**

```bash
git add app/Seo/SitemapBuilder.php app/Http/Controllers/SitemapController.php app/Models/Article.php routes/web.php routes/console.php Dockerfile.production public/llms.txt tests/Feature/SitemapTest.php
git commit -m ":sparkles: Serve a live sitemap with hreflang alternates and add llms.txt

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

(`git rm` a déjà indexé la suppression de `GenerateSitemap.php`.)

---

### Task 8: Accessibilité

Échecs relevés par Lighthouse sur l'accueil :
- liens du texte distingués par la seule couleur ;
- labels du formulaire à 4,34:1 de contraste ;
- icône WhatsApp sans `alt` ;
- liens vers les réseaux sociaux et la vidéo sans nom accessible ;
- titres qui sautent des niveaux (h2 → h4, h3 → h5) ;
- drapeaux avec `alt="Flag"`.

**Files:**
- Modify: `resources/scss/_user.scss`
- Modify: `resources/js/Pages/Articles/Show.tsx` (classe `article-body`)
- Modify: `resources/js/Components/atoms/Flag.tsx`, `resources/js/Components/molecules/FlagSelect.tsx`
- Modify: `resources/js/Components/Sections/Main.tsx`, `Whatsapp.tsx`, `SocialNetworks.tsx`, `Contact.tsx`, `Services.tsx`
- Modify: `resources/js/Components/Layouts/Footer.tsx`
- Modify: `resources/js/Constants/Translations/fr.json`, `en.json`, `he.json`

**Interfaces:**
- Consumes: `Show.tsx` (Task 4) et `FlagSelect.tsx` (Task 3).
- Produces: clé de traduction `video` ; prop `Flag` `alt?: string`.

- [ ] **Step 1: Mesurer l'état de départ**

Run : procédure partagée (étapes 2 à 4), puis :

```bash
CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" npx -y lighthouse http://127.0.0.1:8123/fr --quiet --chrome-flags="--headless=new" --only-categories=accessibility --output=json --output-path=/tmp/a11y-before.json
python3 -c "import json; a=json.load(open('/tmp/a11y-before.json'))['audits']; print(sorted(k for k in ('image-alt','link-name','heading-order','link-in-text-block','color-contrast') if a[k]['score']==0))"
```

Expected: `['color-contrast', 'heading-order', 'image-alt', 'link-in-text-block', 'link-name']`. Laisser les serveurs tourner.

- [ ] **Step 2: Styles**

Ajouter à la fin de `resources/scss/_user.scss` :

```scss
// Links inside running text must not rely on color alone (WCAG 1.4.1).
.accordion-body a,
.article-body a {
  text-decoration: underline;
}

// Slate-500 labels on the light form card only reach 4.3:1.
.form-label {
  color: $gray-700;
}
```

Dans `resources/js/Pages/Articles/Show.tsx`, remplacer la ligne `<article` qui précède `dangerouslySetInnerHTML` par `<article className="article-body"`.

- [ ] **Step 3: Textes alternatifs, noms de liens et niveaux de titres**

```bash
python3 - <<'PY'
import json, pathlib

def replace(path, old, new, count):
    p = pathlib.Path(path)
    s = p.read_text()
    assert s.count(old) == count, f"{path}: {old!r} found {s.count(old)} times, expected {count}"
    p.write_text(s.replace(old, new))

def insert_after(path, anchor, line, count):
    """Insert `line` after each line whose stripped text equals `anchor`, keeping its indentation."""
    p = pathlib.Path(path)
    out, found = [], 0
    for current in p.read_text().split("\n"):
        out.append(current)
        if current.strip() == anchor:
            found += 1
            out.append(current[: len(current) - len(current.lstrip())] + line)
    assert found == count, f"{path}: {anchor!r} found {found} times, expected {count}"
    p.write_text("\n".join(out))

FACEBOOK = 'href="https://www.facebook.com/DrAllouche2020"'
LINKEDIN = 'href="https://www.linkedin.com/in/claude-allouche-776ba719b/"'
for f in ["resources/js/Components/Sections/SocialNetworks.tsx", "resources/js/Components/Sections/Contact.tsx", "resources/js/Components/Layouts/Footer.tsx"]:
    insert_after(f, FACEBOOK, 'aria-label="Facebook"', 1)
    insert_after(f, LINKEDIN, 'aria-label="LinkedIn"', 1)

MAIN = "resources/js/Components/Sections/Main.tsx"
replace(MAIN, 'alt="video"', 'alt={t("video")}', 1)
insert_after(MAIN, 'href="https://www.youtube.com/watch?si=KRTh4j0eSW5pJebF&v=gwCh30H7D3U&feature=youtu.be"', 'aria-label={t("video")}', 1)
insert_after(MAIN, "width={30}", 'alt=""', 1)
insert_after("resources/js/Components/Sections/Whatsapp.tsx", "width={130}", 'alt=""', 1)

replace("resources/js/Components/Sections/Services.tsx", '<h4 className="mb-0">', '<h3 className="h4 mb-0">', 3)
replace("resources/js/Components/Sections/Services.tsx", "</h4>", "</h3>", 3)
replace("resources/js/Components/Sections/Contact.tsx", '<h5 className="mb-0">', '<h4 className="h5 mb-0">', 3)
replace("resources/js/Components/Sections/Contact.tsx", "</h5>", "</h4>", 3)

replace("resources/js/Components/atoms/Flag.tsx", "    lang: string;\n}", "    lang: string;\n    alt?: string;\n}", 1)
replace("resources/js/Components/atoms/Flag.tsx", "({ lang })", "({ lang, alt })", 1)
replace("resources/js/Components/atoms/Flag.tsx", 'alt="Flag"', "alt={alt ?? lang}", 1)
replace("resources/js/Components/molecules/FlagSelect.tsx", "<Flag lang={lang.code} />", "<Flag lang={lang.code} alt={lang.label} />", 1)

videos = {
    "fr": "Voir la vidéo de présentation du Dr Claude Allouche",
    "en": "Watch Dr Claude Allouche's introduction video",
    "he": "לצפייה בסרטון ההיכרות של ד״ר קלוד אלוש",
}
for lang, text in videos.items():
    p = pathlib.Path(f"resources/js/Constants/Translations/{lang}.json")
    raw = p.read_text()
    data = json.loads(raw)
    data["video"] = text
    p.write_text(json.dumps(data, ensure_ascii=False, indent=4) + ("\n" if raw.endswith("\n") else ""))
PY
```

Expected: le script se termine sans `AssertionError`. `Main.tsx` dispose déjà de `t` via `useTranslation()`.

- [ ] **Step 4: Vérifier**

Run: `npx tsc --noEmit -p .`
Expected: uniquement les 4 erreurs préexistantes.

Run: `npm run build`, puis relancer Lighthouse avec `--output-path=/tmp/a11y-after.json` et le même `python3 -c` sur `/tmp/a11y-after.json`.
Expected: au plus `['color-contrast']`. Ce qui reste vient de la couleur du badge de catégorie saisie en base (voir « Hors code »). Nettoyer (étape 6).

- [ ] **Step 5: Commit**

```bash
git add resources/scss/_user.scss resources/js/Pages/Articles/Show.tsx resources/js/Components resources/js/Constants/Translations
git commit -m ":wheelchair: Fix link names, alt texts, heading order and contrast

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 9: Serveur web de production (nginx + php-fpm)

En production, le site tourne sur `php artisan serve`, le serveur de développement de PHP, à un seul worker :
- aucune compression (CSS de 431 Ko et JS de 328 Ko servis bruts) ;
- aucun cache sur les assets, pourtant hashés ;
- aucun en-tête de sécurité ;
- `X-Powered-By` exposé ;
- TTFB moyen de 1,05 s, avec un pic à 6 s.

Les migrations tournent aujourd'hui pendant `docker build`, donc toute construction de l'image se connecte à la base de prod. Elles passent dans une étape Cloud Build dédiée.

**Files:**
- Create: `docker/nginx.conf`, `docker/security-headers.conf`, `docker/php.ini`
- Modify: `docker/supervisord.conf` (réécriture complète)
- Modify: `Dockerfile.production` (réécriture complète)
- Modify: `cloudbuild.yaml` (réécriture complète)

**Interfaces:**
- Consumes: `public/llms.txt` (Task 7) pour vérifier le service des fichiers statiques.

- [ ] **Step 1: Configuration nginx**

`docker/security-headers.conf` :

```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000" always;
```

`docker/nginx.conf` :

```nginx
user www-data;
worker_processes auto;
pid /run/nginx.pid;
error_log /dev/stderr warn;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    access_log /dev/stdout;
    sendfile on;
    server_tokens off;
    client_max_body_size 20m;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml image/svg+xml;

    server {
        listen 8080 default_server;
        root /app/public;
        index index.php;

        include /etc/nginx/security-headers.conf;

        # Vite assets are content-hashed: cache them for a year.
        location /build/assets/ {
            include /etc/nginx/security-headers.conf;
            add_header Cache-Control "public, max-age=31536000, immutable";
            try_files $uri =404;
        }

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_hide_header X-Powered-By;
        }

        location ~ /\.(?!well-known) {
            deny all;
        }
    }
}
```

Le `include` est répété dans `location /build/assets/` parce qu'un `add_header` dans un bloc `location` annule ceux hérités du serveur.

`docker/php.ini` :

```ini
expose_php = Off
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M

opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
; The image is immutable: never re-check files on disk.
opcache.validate_timestamps = 0
```

- [ ] **Step 2: Supervisord, Dockerfile, Cloud Build**

`docker/supervisord.conf` :

```ini
[supervisord]
nodaemon=true
user=root
logfile=/dev/null
logfile_maxbytes=0
pidfile=/run/supervisord.pid

[program:php-fpm]
command=php-fpm --nodaemonize
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g "daemon off;"
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:inertia]
command=php /app/artisan inertia:start-ssr
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

`Dockerfile.production` :

```dockerfile
# nginx + PHP-FPM + Inertia SSR, all managed by supervisord
FROM php:8.3-fpm

WORKDIR /app

COPY .env .env

RUN apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    nginx \
    supervisor \
    libpq-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring exif pcntl bcmath gd opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --optimize-autoloader

RUN php artisan key:generate

RUN npm install && npm run build

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/security-headers.conf /etc/nginx/security-headers.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# php-fpm runs as www-data and writes compiled views, cache and logs here
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

`cloudbuild.yaml` :

```yaml
steps:
  - name: "node:20"
    entrypoint: "npm"
    args: ["install", "@google-cloud/secret-manager"]
  - name: "node:20"
    entrypoint: "node"
    args: ["secret-manager.js"]
  - name: "gcr.io/cloud-builders/docker"
    args:
      [
        "build",
        "-f",
        "Dockerfile.production",
        "-t",
        "europe-west9-docker.pkg.dev/claudeallouche/ahuza/ahuza:lasted",
        ".",
      ]
  - name: "gcr.io/cloud-builders/docker"
    args:
      ["push", "europe-west9-docker.pkg.dev/claudeallouche/ahuza/ahuza:lasted"]
  # Migrations run with the new image, before it receives traffic
  - name: "europe-west9-docker.pkg.dev/claudeallouche/ahuza/ahuza:lasted"
    entrypoint: "php"
    args: ["/app/artisan", "migrate", "--force"]
  - name: "gcr.io/cloud-builders/gcloud"
    args:
      [
        "run",
        "deploy",
        "ahuza",
        "--image",
        "europe-west9-docker.pkg.dev/claudeallouche/ahuza/ahuza:lasted",
        "--platform",
        "managed",
        "--region",
        "europe-west1",
        "--allow-unauthenticated",
      ]

options:
  logging: CLOUD_LOGGING_ONLY
```

- [ ] **Step 3: Commit (avant le test local, qui construit l'image depuis `HEAD`)**

```bash
git add docker Dockerfile.production cloudbuild.yaml
git commit -m ":zap: Serve production with nginx and php-fpm, compression and caching

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

- [ ] **Step 4: Construire et tester l'image en local, sans aucun accès à la prod**

```bash
CTX=$(mktemp -d)
git archive HEAD | tar -x -C "$CTX"
cat > "$CTX/.env" <<EOF
APP_NAME="Dr Claude Allouche"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=http://localhost:8080
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
SESSION_DRIVER=file
CACHE_STORE=file
FILESYSTEM_DISK=local
LOG_CHANNEL=stderr
VITE_APP_NAME="Dr Claude Allouche"
EOF
docker build -f "$CTX/Dockerfile.production" -t ahuza-local "$CTX"
docker run -d --rm --name ahuza-local -p 8080:8080 ahuza-local
until curl -s -o /dev/null http://localhost:8080/login; do sleep 2; done
curl -s -o /dev/null -D - -H 'Accept-Encoding: gzip' http://localhost:8080/login | grep -iE '^(content-encoding|x-powered-by|x-content-type-options|strict-transport-security):'
CSS=$(curl -s http://localhost:8080/login | grep -o '/build/assets/app-[^"]*\.css' | head -1)
curl -s -o /dev/null -D - -H 'Accept-Encoding: gzip' "http://localhost:8080$CSS" | grep -iE '^(content-encoding|cache-control):'
curl -s -o /dev/null -w '%{http_code} %{content_type}\n' http://localhost:8080/llms.txt
docker run --rm ahuza-local php -r 'exit((imagetypes() & (IMG_JPG|IMG_WEBP)) === (IMG_JPG|IMG_WEBP) ? 0 : 1);' && echo "gd jpeg+webp ok"
docker stop ahuza-local
```

Expected :
- page `/login` : `content-encoding: gzip`, `x-content-type-options: nosniff`, `strict-transport-security: max-age=31536000`, et aucune ligne `x-powered-by` ;
- CSS : `content-encoding: gzip` et `cache-control: public, max-age=31536000, immutable` ;
- `llms.txt` : `200 text/plain; charset=utf-8` ;
- `gd jpeg+webp ok` (GD compilé avec JPEG et WebP, sinon tout upload JPEG/WebP échoue).

La page `/login` ne touche pas la base de données. Le build prend environ 10 minutes.

- [ ] **Step 5: Si un contrôle échoue**

Lire `docker logs ahuza-local` avant de l'arrêter, corriger la configuration, puis faire un commit `:bug:` séparé, et relancer le Step 4.

---

### Task 10: Images statiques allégées

`youtube.jpg` (2046×2048, 426 Ko) est affichée à 837 px au maximum. `whatsapp.png` (3840×2160, 180 Ko) est affichée en 30 et 130 px.

**Files:**
- Modify: `resources/js/Assets/images/youtube.jpg`
- Modify: `resources/js/Assets/images/whatsapp.png`

- [ ] **Step 1: Redimensionner (outil `sips` intégré à macOS)**

```bash
sips -Z 1000 -s formatOptions 80 resources/js/Assets/images/youtube.jpg
sips -Z 260 resources/js/Assets/images/whatsapp.png
ls -l resources/js/Assets/images/youtube.jpg resources/js/Assets/images/whatsapp.png | awk '{print $5, $9}'
```

Expected: `youtube.jpg` sous 150 000 octets ; `whatsapp.png` sous 30 000 octets.

- [ ] **Step 2: Vérifier visuellement**

Run : procédure partagée (étapes 2 à 4). Ouvrir http://127.0.0.1:8123/fr : la vignette vidéo et les icônes WhatsApp sont nettes. Nettoyer (étape 6).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Assets/images/youtube.jpg resources/js/Assets/images/whatsapp.png
git commit -m ":zap: Resize oversized static images

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 11: Optimiser les images à l'upload

Les illustrations d'articles sont stockées telles quelles sur GCS : 695 Ko et 546 Ko pour celles de l'accueil. L'upload se fait par trois chemins (dashboard création, dashboard modification, éditeur d'articles), qui dupliquent la même logique.

**Files:**
- Create: `app/Services/ImageOptimizer.php`
- Modify: `app/Models/File.php`
- Modify: `app/Http/Controllers/Auth/ImageController.php`
- Modify: `app/Http/Controllers/Api/FileStorageController.php`
- Create: `tests/Feature/ImageUploadTest.php`

**Interfaces:**
- Produces:
  - `App\Services\ImageOptimizer::optimize(UploadedFile $upload): void`, avec les constantes `MAX_WIDTH = 1600` et `QUALITY = 82`
  - `App\Models\File::attributesFor(UploadedFile $upload): array` (clés `name`, `size`, `mime_type`, `link`)

- [ ] **Step 1: Écrire les tests**

`tests/Feature/ImageUploadTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
    }

    private function storedWidth(File $file): int
    {
        return getimagesize(Storage::path($file->name))[0];
    }

    public function test_large_dashboard_uploads_are_downscaled(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'grand', 'file' => UploadedFile::fake()->image('grand.jpg', 3000, 2000)])
            ->assertSessionHasNoErrors();

        $file = File::firstOrFail();
        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth($file));
        $this->assertSame(Storage::size($file->name), (int) $file->size);
    }

    public function test_editor_uploads_are_downscaled(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/api/files', ['label' => 'editeur', 'file' => UploadedFile::fake()->image('editeur.png', 2400, 1200)])
            ->assertSuccessful();

        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth(File::firstOrFail()));
    }

    public function test_replacing_an_image_downscales_the_new_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('dashboard.images.store'), ['label' => 'avant', 'file' => UploadedFile::fake()->image('avant.jpg', 800, 600)]);
        $file = File::firstOrFail();

        $this->actingAs($user)
            ->post(route('dashboard.images.update', $file), ['file' => UploadedFile::fake()->image('apres.jpg', 2000, 1000)])
            ->assertSessionHasNoErrors();

        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->storedWidth($file->fresh()));
    }

    public function test_small_images_keep_their_dimensions(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.images.store'), ['label' => 'petit', 'file' => UploadedFile::fake()->image('petit.jpg', 800, 600)]);

        $this->assertSame(800, $this->storedWidth(File::firstOrFail()));
    }
}
```

- [ ] **Step 2: Vérifier qu'ils échouent**

Run: `php artisan test tests/Feature/ImageUploadTest.php`
Expected: FAIL, `Class "App\Services\ImageOptimizer" not found`.

- [ ] **Step 3: Implémenter l'optimiseur**

`app/Services/ImageOptimizer.php` :

```php
<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Downscales and recompresses uploaded JPEG, PNG and WebP images in place.
 * A file that was not resized is only replaced when re-encoding makes it smaller.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 82;

    public function optimize(UploadedFile $upload): void
    {
        $path = $upload->getRealPath();
        $mime = $upload->getMimeType();
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            return;
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $path);
        }

        $resized = imagesx($image) > self::MAX_WIDTH;
        if ($resized) {
            $image = imagescale($image, self::MAX_WIDTH);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $optimized = tempnam(sys_get_temp_dir(), 'img');
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $optimized, self::QUALITY),
            'image/png' => imagepng($image, $optimized, 9),
            'image/webp' => imagewebp($image, $optimized, self::QUALITY),
        };

        if ($resized || filesize($optimized) < filesize($path)) {
            rename($optimized, $path);
        } else {
            unlink($optimized);
        }

        clearstatcache(true, $path);
    }

    // GD drops EXIF data, so phone photos must be rotated before re-encoding.
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = @exif_read_data($path)['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
```

- [ ] **Step 4: Un seul chemin d'upload**

Dans `app/Models/File.php` :
- ajouter `use App\Services\ImageOptimizer;` et `use Illuminate\Http\UploadedFile;` aux imports ;
- remplacer la méthode `store` par :

```php
    /** Optimizes and stores an upload, returning the attributes to persist. */
    public static function attributesFor(UploadedFile $upload): array
    {
        app(ImageOptimizer::class)->optimize($upload);

        $size = $upload->getSize();
        $mimeType = $upload->getMimeType();
        $name = $upload->store();

        return [
            'name' => $name,
            'size' => $size,
            'mime_type' => $mimeType,
            'link' => Storage::url($name),
        ];
    }

    public static function store(FileStoreRequest $request)
    {
        return File::create(['label' => $request->label, ...self::attributesFor($request->file('file'))]);
    }
```

Dans `app/Http/Controllers/Auth/ImageController.php`, remplacer le corps de `store` par :

```php
        File::store($request);

        return Redirect::route('dashboard.images.index')->with('success', 'Image created.');
```

et, dans `update`, le bloc `if ($request->hasFile('file')) { ... }` par :

```php
        if ($request->hasFile('file')) {
            Storage::delete($image->name);
            $image->fill(File::attributesFor($request->file('file')));
        }
```

Dans `app/Http/Controllers/Api/FileStorageController.php`, remplacer le corps de `store` par `return File::store($request);` et supprimer l'import devenu inutile `use Illuminate\Support\Facades\Storage;`.

- [ ] **Step 5: Vérifier que les tests passent**

Run: `php artisan test`
Expected: tous les tests passent.

- [ ] **Step 6: Commit**

```bash
git add app/Services/ImageOptimizer.php app/Models/File.php app/Http/Controllers/Auth/ImageController.php app/Http/Controllers/Api/FileStorageController.php tests/Feature/ImageUploadTest.php
git commit -m ":zap: Downscale and recompress uploaded images

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 12: CSS allégé

Le CSS fait 422 Ko, dont 391 Ko jamais utilisés. Le thème importe des styles que le site n'emploie pas :
- documentation (`docs`) ;
- coloration syntaxique (`prism`) ;
- carrousel (`swiper`) ;
- lecteur vidéo (`plyr`) ;
- indicatifs téléphoniques (`intl`) ;
- mode sombre (`darkmode`).

Aucune de ces classes n'apparaît dans `resources/js` ni dans `resources/views`.

**Files:**
- Modify: `resources/scss/theme/_theme.scss`

- [ ] **Step 1: Mesurer et capturer l'état de départ**

Run : `npm run build 2>&1 | grep 'app-.*\.css'`. Noter la taille.

Run : procédure partagée (étapes 2 à 4), puis capturer les pages de référence :

```bash
cat > shots.tmp.mjs <<'JS'
import puppeteer from "puppeteer";
const [, , dir] = process.argv;
const browser = await puppeteer.launch({ executablePath: "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome", headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1366, height: 900 });
for (const [name, path] of [["home", "/fr"], ["articles", "/fr/articles"], ["article", "/fr/articles/le-diu-et-vous"], ["he", "/he"], ["login", "/login"]]) {
  await page.goto(`http://127.0.0.1:8123${path}`, { waitUntil: "networkidle0" });
  await page.screenshot({ path: `${dir}/${name}.png`, fullPage: true });
}
await browser.close();
JS
mkdir -p /tmp/css-before && node shots.tmp.mjs /tmp/css-before
```

- [ ] **Step 2: Retirer les imports inutilisés**

Dans `resources/scss/theme/_theme.scss`, supprimer ces lignes :

```scss
@import "./components/docs";
```

```scss
// vendor classes
@import "./vendors/prism/prism";
@import "./vendors/swiper/swiper";
@import "./vendors/plyr/plyr";
@import "./vendors/intl/intl";

// darkmode classes
@import "darkmode";
```

- [ ] **Step 3: Comparer**

Run: `npm run build 2>&1 | grep 'app-.*\.css'`
Expected: un fichier plus petit qu'au Step 1.

Relancer le serveur sur le nouveau build (étape 4 de la procédure), puis :

```bash
mkdir -p /tmp/css-after && node shots.tmp.mjs /tmp/css-after
rm shots.tmp.mjs
```

Comparer chaque paire d'images `/tmp/css-before/*.png` et `/tmp/css-after/*.png`. Elles doivent être visuellement identiques. Nettoyer (étape 6).

- [ ] **Step 4: Commit**

```bash
git add resources/scss/theme/_theme.scss
git commit -m ":fire: Drop unused theme styles

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 13: Déploiement et contrôle en production

**Prérequis : accord explicite de l'utilisateur avant de déployer.** Tant que le déclencheur Cloud Build n'est pas rebranché sur `ilanjourno/ahuza-clinic`, le déploiement est manuel.

- [ ] **Step 1: Noter la révision actuelle (pour un éventuel retour arrière)**

```bash
PREVIOUS_REVISION=$(gcloud run services describe ahuza --project=claudeallouche --region=europe-west1 --format="value(status.latestReadyRevisionName)")
echo "$PREVIOUS_REVISION"
```

Pré-déploiement :
- (a) mettre `LOG_CHANNEL=stderr` dans le `.env` du Secret Manager, pour que les erreurs Laravel atteignent Cloud Logging ;
- (b) vérifier les variables d'environnement du service : `gcloud run services describe ahuza --project=claudeallouche --region=europe-west1 --format="value(spec.template.spec.containers[0].env)"`. Celles définies sur le service sont invisibles pour php-fpm (`clear_env`) : seul le `.env` compte ;
- (c) après le déploiement, téléverser une image JPEG depuis le tableau de bord et l'ouvrir.

- [ ] **Step 2: Pousser et déployer depuis un export propre de `main`**

```bash
git push origin main
CTX=$(mktemp -d)
git archive main | tar -x -C "$CTX"
BUILD_ID=$(gcloud builds submit "$CTX" --project=claudeallouche --config="$CTX/cloudbuild.yaml" --async --format="value(id)")
until s=$(gcloud builds describe "$BUILD_ID" --project=claudeallouche --format="value(status)") && echo "$s" | grep -qE '^(SUCCESS|FAILURE|INTERNAL_ERROR|TIMEOUT|CANCELLED)$'; do sleep 15; done; echo "$s"
```

Expected: `SUCCESS`. En cas d'échec, lire les logs : `gcloud logging read "resource.type=build AND resource.labels.build_id=$BUILD_ID" --project=claudeallouche --limit=50 --format="value(textPayload)"`.

- [ ] **Step 3: Contrôler la production**

```bash
D=https://claude-allouche.com
curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' $D/
curl -s $D/fr | grep -o '<html lang="fr"'
curl -s -o /dev/null -D - -H 'Accept-Encoding: gzip' $D/fr | grep -iE '^(content-encoding|strict-transport-security|x-powered-by):'
curl -s $D/fr | grep -c 'application/ld+json'
curl -s $D/sitemap.xml | grep -c 'xhtml:link'
curl -s -o /dev/null -w '%{http_code}\n' $D/llms.txt
curl -s $D/sitemap.xml | grep -c '<loc>http://'
curl -s $D/fr | grep -o 'rel="canonical" href="https://claude-allouche.com/fr"'
```

Expected :
- `302 -> https://claude-allouche.com/fr` ;
- `<html lang="fr"` ;
- `content-encoding: gzip` et `strict-transport-security`, sans `x-powered-by` ;
- `1` ;
- un nombre supérieur à 0 ;
- `200` ;
- `0` (aucune URL `http://` dans le sitemap) ;
- une occurrence de `rel="canonical" href="https://claude-allouche.com/fr"`.

Relancer ensuite le crawl et Lighthouse de l'audit (`claude-allouche.com-audit/`). Valider `/fr` et un article sur https://validator.schema.org et https://search.google.com/test/rich-results.

- [ ] **Step 4: Retour arrière si nécessaire**

```bash
gcloud run services update-traffic ahuza --project=claudeallouche --region=europe-west1 --to-revisions="$PREVIOUS_REVISION=100"
```

---

## Hors code — actions côté propriétaire du site

À faire en parallèle. Ces actions pèsent autant que le code pour le référencement local.

1. **Google Business Profile.** Créer ou revendiquer une fiche par cabinet : Raanana (Clinic 4 You, Hamasger 8) et Netanya (Migdal Hahaloutsim). Pour chacune :
   - catégorie « Gynécologue obstétricien » ;
   - nom, adresse et téléphone identiques au site ;
   - horaires ;
   - photos du cabinet ;
   - lien vers `https://claude-allouche.com/fr`.
2. **Mettre à jour les profils existants.** LinkedIn (titre et expérience mentionnent encore « Ahuza clinic Raanana »), page Facebook (adresse), et la fiche d'annuaire français [mablouseblanche.fr](https://www.mablouseblanche.fr/pro/A10003769840/docteur-allouche-claude-gynecologie-obstetrique-evreux), qui indique Évreux.
3. **Annuaires israéliens en hébreu.** easy.co.il, B144. Aucune présence n'a été trouvée sous « קלוד אלוש ».
4. **Google Search Console.** Valider la propriété du domaine, soumettre `https://claude-allouche.com/sitemap.xml`, demander la réindexation de `/fr`, `/en` et `/he` (Google affiche encore « - Ahuza » dans les titres), puis suivre le rapport hreflang.
5. **Contenu, depuis le dashboard :**
   - remplacer « Ahuza » dans les 3 FAQ concernées, dans la signature des articles et dans les mots-clés ;
   - vérifier les liens Google Maps des FAQ ;
   - choisir des couleurs lisibles pour la catégorie à badge vert sur rose ;
   - rédiger une description de 140 à 155 caractères pour les articles qui n'en ont pas ;
   - raccourcir les titres de plus de 60 caractères.
6. **Traductions et articles courts.**
   - Traduire en hébreu les 25 articles qui n'ont pas de version hébraïque, en commençant par les plus consultés.
   - Étoffer ou fusionner les 18 articles de moins de 300 mots, le plus court étant `/he/articles/menopause1`.
7. **DNS `www`.** Ajouter un enregistrement `www.claude-allouche.com` qui redirige en 301 vers `claude-allouche.com`. Aujourd'hui `www` ne résout pas.
8. **Avis.** Solliciter des avis Google sur les deux fiches.
9. **Cloud Build.** Connecter `ilanjourno/ahuza-clinic` à Cloud Build dans la console GCP, puis repointer le déclencheur `ahuza`, pour que chaque push sur `main` redéploie automatiquement.

## Décisions à prendre (hors de ce plan)

- **Mise en page de droite à gauche pour l'hébreu.** Aujourd'hui `dir="rtl"` n'est jamais posé. L'ajouter sans feuille Bootstrap RTL décalerait les marges : c'est un chantier à part (compiler `bootstrap.rtl` pour `he`). Ce plan ne corrige que `lang`.
- **Instance Cloud Run toujours active (`min-instances=1`).** Supprime les démarrages à froid (TTFB jusqu'à 6 s), contre un coût mensuel fixe.
- **Ré-optimisation des illustrations existantes sur GCS.** Elle écrase les originaux : à faire seulement après une sauvegarde du bucket. La Task 11 ne traite que les nouveaux uploads.
- **Langues parlées par le médecin.** À confirmer pour ajouter `knowsLanguage` au JSON-LD.
- **Limite d'upload.** Elle est fixée à 2 Mo (`max:2048`). Avec l'optimisation de la Task 11 et `upload_max_filesize = 20M` (Task 9), on peut la relever pour accepter les photos de téléphone.
