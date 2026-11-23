<?php

namespace App\Providers;

use App\Support\LiveStatus;
use App\Support\Settings;
use App\View\Composers\SiteComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One copy per request, so settings and live state are read once and
        // agree everywhere on the page.
        $this->app->scoped(Settings::class);
        $this->app->scoped(LiveStatus::class);
    }

    public function boot(): void
    {
        // Page views get the site-wide data; their layout inherits it.
        View::composer(['site.*', 'blog.*'], SiteComposer::class);
    }
}
