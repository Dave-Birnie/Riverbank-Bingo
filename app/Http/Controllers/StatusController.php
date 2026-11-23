<?php

namespace App\Http\Controllers;

use App\Support\LiveStatus;
use Illuminate\Http\JsonResponse;

/** Polled by every public page so the live banner flips without a reload. */
class StatusController extends Controller
{
    public function __invoke(LiveStatus $live): JsonResponse
    {
        return response()->json($live->toArray())->header('Cache-Control', 'no-cache');
    }
}
