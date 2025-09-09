<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\Logging\Resources\ActivityController;

Route::prefix('activities')->group(function (): void {
    Route::get('', [ActivityController::class, 'index']);
    Route::get('/user/{user}', [ActivityController::class, 'getUserActivities']);
    Route::get('/model/{type}/{id}', [ActivityController::class, 'getModelActivities']);
    Route::get('/dashboard/activities', [ActivityController::class, 'getActivityDashboard']);
});
