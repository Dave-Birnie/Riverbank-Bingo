<?php

use App\Support\LiveStatus;
use Illuminate\Support\Facades\Route;

// Polled by every public page so the live banner flips without a reload.
Route::get('/status', fn (LiveStatus $live) => response()
    ->json($live->toArray())
    ->header('Cache-Control', 'no-cache'))
    ->middleware('throttle:120,1')
    ->name('status');
