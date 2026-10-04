<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\FAQ;
use App\Seo\StructuredData;
use Illuminate\Foundation\Application;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index() 
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'schema' => StructuredData::home(App::getLocale()),
            'faqs' => FAQ::withTraductionInLocale()->orderBy('id')->get(),
            'articles' => new ResourceCollection(
                Article::with(['categories'])
                ->withTraductionInLocale()
                ->published()
                ->orderBy('published_at', 'desc')
                ->limit(3)->get()
            )
        ]);
    }
}
