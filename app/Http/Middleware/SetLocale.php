<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang');

        if ($locale && in_array($locale, ['fi', 'en'])) {
            Session::put('locale', $locale);
            App::setLocale($locale);
        } elseif (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
             // Browser detection (simplified)
            $browserLocale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
            if (in_array($browserLocale, ['fi', 'en'])) {
                 App::setLocale($browserLocale);
            } else {
                App::setLocale(config('app.locale'));
            }
        }

        return $next($request);
    }
}
