<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\AI\Controllers\AdminAIController;

Route::prefix('ai')->name('ai.')->group(function (): void {
    Route::post('/sessions', [AdminAIController::class, 'createSession'])
        ->middleware('throttle:30,1')
        ->name('sessions.store');

    Route::get('/sessions/latest', [AdminAIController::class, 'latestSession'])
        ->middleware('throttle:40,1')
        ->name('sessions.latest');

    Route::get('/sessions', [AdminAIController::class, 'sessions'])
        ->middleware('throttle:40,1')
        ->name('sessions.index');

    Route::post('/messages', [AdminAIController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('messages.store');

    Route::post('/messages/stream', [AdminAIController::class, 'streamMessage'])
        ->middleware('throttle:30,1')
        ->name('messages.stream');

    Route::patch('/messages/{messageId}/flag', [AdminAIController::class, 'flagMessage'])
        ->middleware('throttle:40,1')
        ->name('messages.flag');

    Route::get('/sessions/{sessionToken}/messages', [AdminAIController::class, 'messages'])
        ->middleware('throttle:40,1')
        ->name('messages.index');
});
