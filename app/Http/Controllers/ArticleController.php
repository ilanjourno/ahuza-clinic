<?php

namespace App\Http\Controllers;

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

        $article = $article->load(['categories', 'traductions' => fn ($query) => $query->where('langue', $locale), 'keywords']);

        if($article->traductions->isEmpty()) {
           throw new NotFoundHttpException();
        }

        return Inertia::render('Articles/Show', ['article' => $article]);
    }
}
