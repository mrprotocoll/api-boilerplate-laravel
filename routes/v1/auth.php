<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\Auth\LoginController;
use Modules\V1\Auth\Controllers\AuthenticatedSessionController;
use Modules\V1\Auth\Controllers\EmailVerificationNotificationController;
use Modules\V1\Auth\Controllers\NewPasswordController;
use Modules\V1\Auth\Controllers\Oauth\GoogleAuthController;
use Modules\V1\Auth\Controllers\PasswordResetLinkController;
use Modules\V1\Auth\Controllers\RegisteredUserController;
use Modules\V1\Auth\Controllers\VerifyEmailController;

Route::prefix('auth')->group(function (): void {

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('register');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('login');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::post('/email/verify', VerifyEmailController::class)
        ->name('verification.verify');

    Route::post('/email/verification-link', EmailVerificationNotificationController::class)
        ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('logout');

    Route::get('/google/url', [GoogleAuthController::class, 'googleAuthUrl']);
    Route::post('/google/login', [GoogleAuthController::class, 'googleAuthLogin']);

    Route::post('/refresh-token', [AuthenticatedSessionController::class, 'refreshToken']);
});

Route::prefix('admin/auth')->group(function (): void {
    Route::post('login', LoginController::class)
        ->middleware('guest')
        ->name('login');
});
