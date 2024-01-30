<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DirectoryParser::class, function ($app) {
            return new DirectoryParser();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share data with all Inertia responses
        Inertia::share([
            // Share the authenticated user's ID
            'auth_user_id' => function () {
                return auth()->user() ? auth()->id() : null;
            },
        ]);
    }
}
