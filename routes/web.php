<?php

use App\Http\Controllers\SiteController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// The same pages in each language: English at the root, French under /fr
// with French URLs. Route names are "schedule" and "fr.schedule"; views use
// lroute('schedule') to get the one for the current language.
foreach (Locales::ALL as $locale) {
    $path = fn (string $name) => Locales::path($name, $locale);
    $isDefault = $locale === Locales::DEFAULT;

    Route::middleware("locale:{$locale}")
        ->prefix($isDefault ? '' : $locale)
        ->name($isDefault ? '' : "{$locale}.")
        ->controller(SiteController::class)
        ->group(function () use ($path) {
            Route::get('/', 'home')->name('home');
            Route::get($path('schedule'), 'schedule')->name('schedule');
            Route::get($path('watch'), 'watch')->name('watch');
            Route::get($path('distributors'), 'distributors')->name('distributors');
            Route::get($path('winners'), 'winners')->name('winners');
            Route::get($path('blog'), 'blog')->name('blog');
            Route::get($path('blog').'/{post:slug}', 'post')->name('blog.show');
            Route::get($path('contact'), 'contact')->name('contact');
            Route::post($path('contact'), 'sendContact')->middleware('throttle:5,1')->name('contact.send');

            foreach (SiteController::COPY_PAGES as $key) {
                Route::get($path("page.{$key}"), 'page')->defaults('key', $key)->name("page.{$key}");
            }
        });
}
