<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\V1\AI\Providers\AIFactory;
use Modules\V1\AI\Services\AIToolExecutor;
use Modules\V1\AI\Services\AIToolRegistry;

it('registers authenticated AI routes', function (): void {
    expect(Route::has('v1:user:ai.sessions.store'))->toBeTrue()
        ->and(Route::has('v1:user:ai.messages.store'))->toBeTrue()
        ->and(Route::has('v1:user:ai.messages.stream'))->toBeTrue();
});

it('creates the configured AI provider', function (): void {
    config(['ai.default_provider' => 'openai']);

    expect(AIFactory::make()->getProvider())->toBe('openai');
});

it('exposes default AI tools', function (): void {
    $registry = app(AIToolRegistry::class);

    expect($registry->supportedTools())->toContain(
        'current_time',
        'authenticated_user',
        'application_info',
        'navigate',
    );
});

it('executes the current time tool', function (): void {
    $registry = app(AIToolRegistry::class);
    $tool = $registry->handler('current_time');

    $result = app(AIToolExecutor::class)->execute(
        new Modules\V1\AI\DTO\AIToolCall('current_time', ['timezone' => 'UTC']),
        null,
    );

    expect($tool)->not->toBeNull()
        ->and($result['result']->status)->toBe('success')
        ->and($result['result']->kind)->toBe('current_time');
});
