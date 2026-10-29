<?php

use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class)
    ->middleware('throttle:120,1')
    ->name('status');
