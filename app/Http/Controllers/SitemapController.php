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
