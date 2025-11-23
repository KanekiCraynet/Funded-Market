<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Laravel + Vue 3 SPA
| All frontend routes handled by Vue Router
| API routes are in routes/api.php
|
*/

// Serve Vue SPA for all non-API routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');