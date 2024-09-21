<?php

namespace App\Providers;

use App\Services\TextFilterService;
use App\Services\TokenManagerService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        //Rate limiter for the primary API endpoint
        RateLimiter::for('text-filter', function (Request $request) {
            return Limit::perMinute(100)->by($request->bearerToken() ?? $request->ip())->response(function (Request $request, array $headers) {
                return response([
                    'status' => 'failed',
                    'timestamp' => Carbon::now()->timestamp,
                    'error_message' => 'To Many Requests. Retry After ' . $headers['Retry-After'] . ' seconds',
                ], 429, $headers);
            });
        });

        //Rate limiter for the /generate-token and /refresh-token endpoints
        RateLimiter::for('generate-or-refresh-token', function (Request $request) {
            return Limit::perMinute(10)->by($request->user_id ?? $request->ip());
        });

        //Rate limiter for homepage API tester
        RateLimiter::for('text-filter-tester', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
