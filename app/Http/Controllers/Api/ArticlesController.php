<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Request;

class ArticlesController extends Controller 
{
    public function index()
    {
        return new ResourceCollection(
            Article::with(['categories', 'illustration'])
            ->withTraductionInLocale()
            ->filter(Request::only('search'))
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(15)
        );
    }
}