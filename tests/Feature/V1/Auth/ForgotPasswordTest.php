<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\V1\Auth\Notifications\ResetPassword;
use Modules\V1\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    $this->route = '/v1/auth/forgot-password';
    $this->seed(RoleSeeder::class);
});

it('can request a reset password link', function (): void {
    $user = User::factory()->create();

    $response = $this->post($this->route, ['email' => $user->email]);

    // Debugging: Assert response is OK
    $response->assertOk();

    // Debugging: Ensure user email is correct
    expect($user->email)->toBe($user->email);

    // Assert Notification was sent
    Notification::assertSentTo(
        [$user],
        ResetPassword::class,
        function ($notification, $channels) {
            return in_array('mail', $channels);
        }
    );
});

it('sends password reset link for valid email', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson($this->route, [
        'email' => $user->email,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Password reset link sent successfully',
        'status' => 'success',
        'statusCode' => '200',
    ]);
});

it('returns error for invalid email', function (): void {
    $response = $this->postJson($this->route, [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

it('returns error for non-existent email', function (): void {
    $response = $this->postJson($this->route, [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});
