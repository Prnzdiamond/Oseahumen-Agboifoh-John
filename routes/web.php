<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-cache', function () {
    Cache::put('test_key', 'Redis is working 🎉', 10);
    return Cache::get('test_key');
});
