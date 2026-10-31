<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('pagination');
        Paginator::defaultSimpleView('pagination');
        RateLimiter::for('device-api', fn (Request $r) => Limit::perMinute(60)->by('device:'.($r->attributes->get('device')?->id ?? $r->ip())));
    }
}
