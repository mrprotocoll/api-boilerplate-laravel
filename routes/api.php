<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return json_encode('Welcome to My API');
});

/**
 * Version 1
 */
Route::prefix('v1')->as('v1:')->group(
    base_path('routes/v1/api.php'),
);
