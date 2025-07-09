<?php

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\AdminController;
use Modules\V1\Admin\Controllers\Auth\LogoutController;

Route::put('/me', [AdminController::class, 'show']);
Route::put('/update', [AdminController::class, 'update']);
Route::put('/change-password', [AdminController::class, 'changePassword']);
Route::post('/auth/logout', LogoutController::class)->name('logout');

/**
 * Users Routes
 */
Route::prefix('users')->as('users:')->group(
    base_path('routes/v1/admin/users.php'),
);

Route::prefix('logs')->as('logs:')->group(
    base_path('routes/v1/admin/log.php'),
);

/**
 * Admin Routes
 */
Route::as('')->group(
    base_path('routes/v1/admin/admin.php'),
);


