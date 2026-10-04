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
