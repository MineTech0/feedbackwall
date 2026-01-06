<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Pail only in local environment (it's a dev dependency)
        if ($this->app->environment('local') && class_exists(\Laravel\Pail\PailServiceProvider::class)) {
            $this->app->register(\Laravel\Pail\PailServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (Coolify/proxy terminates SSL)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('feedback-submissions', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('feedback-votes', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
