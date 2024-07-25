<?php

namespace App\Providers;

use App\Events\SaveTextFilterResponseEvent;
use App\Listeners\SaveTextFilterResponseListener;
use App\Services\TextFilterService;
use App\Services\TokenManagerService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TokenManagerService::class, function ($app) {
            return new TokenManagerService();
        });

        $this->app->singleton(TextFilterService::class, function ($app) {
            return new TextFilterService();
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            SaveTextFilterResponseEvent::class,
            SaveTextFilterResponseListener::class,
        );
    }
}