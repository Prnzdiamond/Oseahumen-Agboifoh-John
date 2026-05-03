<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\IconController;

Route::get('projects/meta',   [ProjectController::class, 'meta']);   // ← new
Route::group(['middleware' => 'validate.origin'], function () {

    // ── Owner ─────────────────────────────────────────────────────────────────
    Route::get('owner', [OwnerController::class, 'show']);

    // ── Projects ──────────────────────────────────────────────────────────────
    // IMPORTANT: 'projects/meta' MUST be declared before 'projects/{slug}'
    // otherwise Laravel's router treats "meta" as a slug value.
    Route::get('projects',        [ProjectController::class, 'index']);
    Route::get('projects/{slug}', [ProjectController::class, 'show']);

    // ── Contact ───────────────────────────────────────────────────────────────
    Route::post('contact', [ContactController::class, 'send']);

    // ── Technology catalog ────────────────────────────────────────────────────
    Route::get('technologies', [TechnologyController::class, 'index']);

    // ── Icon catalog ─────────────────────────────────────────────────────────
    Route::get('icons',            [IconController::class, 'index']);
    Route::get('icons/{name}/svg', [IconController::class, 'svg']);
});
