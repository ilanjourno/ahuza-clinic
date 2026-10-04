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
