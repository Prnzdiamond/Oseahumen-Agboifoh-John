<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\IconController;

Route::group(['middleware' => 'validate.origin'], function () {

    // ── Existing routes — unchanged ───────────────────────────────────────────
    Route::get('owner',           [OwnerController::class,  'show']);
    Route::get('projects',        [ProjectController::class, 'index']);
    Route::get('projects/{slug}', [ProjectController::class, 'show']);
    Route::post('contact',        [ContactController::class, 'send']);

    // ── Phase 1: New catalog endpoints ───────────────────────────────────────

    // Technology catalog
    // GET /api/technologies          → full catalog (cached)
    // The frontend fetches this once to power icon resolution + URL filtering.
    Route::get('technologies', [TechnologyController::class, 'index']);

    // Icon catalog
    // GET /api/icons                 → full catalog (cached)
    // GET /api/icons?search=music    → search by keyword
    // GET /api/icons?category=social → filter by category
    Route::get('icons',              [IconController::class, 'index']);

    // Single icon SVG — for the Filament visual picker (Phase 2)
    // GET /api/icons/{name}/svg      → returns raw SVG markup
    Route::get('icons/{name}/svg',   [IconController::class, 'svg']);
});
