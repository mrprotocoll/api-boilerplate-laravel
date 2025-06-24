<?php

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\Auth\LogoutController;
use Modules\V1\Admin\Controllers\ReferralController;
use Modules\V1\Admin\Controllers\StatsController;
use Modules\V1\User\Controllers\UserController;

Route::put('/me', [UserController::class, 'show']);
Route::put('/update', [UserController::class, 'update']);
Route::put('/change-password', [UserController::class, 'changePassword']);
Route::get('/referrals', [ReferralController::class, 'leaderboard'])->name('leaderboard');
Route::get('/stats', [StatsController::class, 'stats'])->name('stats');
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


