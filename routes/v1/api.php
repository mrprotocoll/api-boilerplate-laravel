<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return json_encode('Version 1');
});

/**
 * User Routes
 */
Route::middleware(['auth:sanctum'])->prefix('user')->as('user:')->group(
    base_path('routes/v1/users/api.php'),
);

/**
 * Authentication Routes
 */
Route::as('auth:')->group(
    base_path('routes/v1/auth.php'),
);
