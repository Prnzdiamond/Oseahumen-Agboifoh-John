<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProjectController;

Route::group(["middleware" => "validate.origin"], function () {

    Route::get('owner', [OwnerController::class, 'show']);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{slug}', [ProjectController::class, 'show']);
    // routes/api.php
    // routes/api.php
    Route::post('/contact', [ContactController::class, 'send']);
});
