<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Controllers\AdminController;

Route::prefix('admins')->as('admins:')->group(function (): void {
    Route::get('', [AdminController::class, 'index'])->name('index');
    Route::post('', [AdminController::class, 'store'])->name('store');
    Route::patch('{admin}', [AdminController::class, 'update'])->name('update');
    Route::patch('{admin}/change-role', [AdminController::class, 'changeRole'])->name('changeRole');
});
