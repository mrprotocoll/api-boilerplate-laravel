<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\Providers\AIFactory;
use Modules\V1\AI\Services\AIToolExecutor;
use Modules\V1\AI\Services\AIToolRegistry;
use Modules\V1\User\Models\User;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('registers authenticated AI routes', function (): void {
    expect(Route::has('v1:user:ai.sessions.store'))->toBeTrue()
        ->and(Route::has('v1:user:ai.messages.store'))->toBeTrue()
        ->and(Route::has('v1:user:ai.messages.stream'))->toBeTrue()
        ->and(Route::has('v1:admin:ai.sessions.store'))->toBeTrue()
        ->and(Route::has('v1:admin:ai.messages.store'))->toBeTrue()
        ->and(Route::has('v1:admin:ai.messages.stream'))->toBeTrue()
        ->and(Route::has('v1:admin:users:index'))->toBeFalse();
});

it('creates the configured AI provider', function (): void {
    config(['ai.default_provider' => 'openai']);

    expect(AIFactory::make()->getProvider())->toBe('openai');
});

it('exposes default AI tools', function (): void {
    $registry = app(AIToolRegistry::class);

    expect($registry->registeredTools())->toContain(
        'current_time',
        'authenticated_user',
        'authenticated_admin',
        'application_info',
        'navigate',
    );
});

it('scopes user and admin AI tools separately', function (): void {
    $registry = app(AIToolRegistry::class);
    $user = User::factory()->create();
    $admin = createAdminForAITest();

    $userTools = $registry->supportedTools(AIActorContext::forUser($user));
    $adminTools = $registry->supportedTools(AIActorContext::forAdmin($admin));

    expect($userTools)->toContain('authenticated_user')
        ->and($userTools)->not->toContain('authenticated_admin')
        ->and($adminTools)->toContain('authenticated_admin')
        ->and($adminTools)->not->toContain('authenticated_user');
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

it('creates admin AI sessions with admin actor ownership', function (): void {
    $admin = createAdminForAITest();
    $token = $admin->createToken('AdminAITest')->plainTextToken;

    $response = $this->postJson('/v1/admin/ai/sessions', [
        'sourcePage' => '/admin/dashboard',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJsonPath('data.session.sourcePage', '/admin/dashboard')
        ->assertJsonPath('data.supportedTools.1', 'authenticated_admin');

    $this->assertDatabaseHas('ai_sessions', [
        'actor_type' => $admin->getMorphClass(),
        'actor_id' => (string) $admin->id,
        'source_page' => '/admin/dashboard',
    ]);
});

it('prevents user tokens from creating admin AI sessions', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('UserAITest')->plainTextToken;

    $response = $this->postJson('/v1/admin/ai/sessions', [
        'sourcePage' => '/admin/dashboard',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertUnauthorized();
});

function createAdminForAITest(): Admin
{
    return Admin::forceCreate([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
        'super_admin' => true,
    ]);
}
