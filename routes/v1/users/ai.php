<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\AI\Controllers\AIController;

Route::prefix('ai')->name('ai.')->group(function (): void {
    Route::post('/sessions', [AIController::class, 'createSession'])
        ->middleware('throttle:30,1')
        ->name('sessions.store');

    Route::get('/sessions/latest', [AIController::class, 'latestSession'])
        ->middleware('throttle:40,1')
        ->name('sessions.latest');

    Route::get('/sessions', [AIController::class, 'sessions'])
        ->middleware('throttle:40,1')
        ->name('sessions.index');

    Route::post('/messages', [AIController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('messages.store');

    Route::post('/messages/stream', [AIController::class, 'streamMessage'])
        ->middleware('throttle:30,1')
        ->name('messages.stream');

    Route::patch('/messages/{messageId}/flag', [AIController::class, 'flagMessage'])
        ->middleware('throttle:40,1')
        ->name('messages.flag');

    Route::get('/sessions/{sessionToken}/messages', [AIController::class, 'messages'])
        ->middleware('throttle:40,1')
        ->name('messages.index');
});
