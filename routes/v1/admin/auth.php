<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\Auth\LoginController;
use Modules\V1\Admin\Controllers\Auth\LogoutController;
use Modules\V1\Auth\Controllers\AuthenticatedSessionController;
use Modules\V1\Auth\Controllers\EmailVerificationNotificationController;
use Modules\V1\Auth\Controllers\NewPasswordController;
use Modules\V1\Auth\Controllers\Oauth\GoogleAuthController;
use Modules\V1\Auth\Controllers\PasswordResetLinkController;
use Modules\V1\Auth\Controllers\RegisteredUserController;
use Modules\V1\Auth\Controllers\VerifyEmailController;

Route::post('/admin/auth/login', LoginController::class)
    ->middleware('guest')
    ->name('login');

