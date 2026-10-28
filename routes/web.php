<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/schedule', 'schedule')->name('schedule');
    Route::get('/watch-live', 'watch')->name('watch');
    Route::get('/distributors', 'distributors')->name('distributors');
    Route::get('/winners', 'winners')->name('winners');
    Route::get('/news', 'blog')->name('blog');
    Route::get('/news/{post:slug}', 'post')->name('blog.show');
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'sendContact')->middleware('throttle:5,1')->name('contact.send');
    Route::get('/{key}', 'page')->whereIn('key', SiteController::COPY_PAGES)->name('page');
});
