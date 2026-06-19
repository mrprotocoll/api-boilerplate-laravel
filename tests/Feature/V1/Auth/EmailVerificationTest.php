<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Modules\V1\Auth\Notifications\WelcomeNotification;
use Modules\V1\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->route = 'v1/auth/email/verify';
    Notification::fake();
});

it('verifies user email with valid token', function (): void {
    $token = 'validtoken';

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->addMinutes(30),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson($this->route, [
        'token' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Login successful',
        'status' => 'success',
        'statusCode' => 200,
    ]);

    Notification::assertSentTo([$user], WelcomeNotification::class);
});

it('returns error for invalid token', function (): void {
    $response = $this->postJson($this->route, [
        'token' => 'invalid_token',
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'message' => 'Invalid verification token',
        'status' => 'error',
        'statusCode' => 404,
    ]);
});

it('returns error for expired token', function (): void {
    $token = 'valid_token';

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->subMinutes(30),
    ]);

    $response = $this->postJson($this->route, [
        'token' => $token,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Verification token has expired',
        'status' => 'error',
        'statusCode' => 400,
    ]);
});

it('returns error for already verified email', function (): void {
    $token = 'valid_token';

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->addMinutes(30),
        'email_verified_at' => Carbon::now(),
    ]);

    $response = $this->postJson($this->route, [
        'token' => $token,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Email already verified',
        'status' => 'error',
        'statusCode' => 400,
    ]);
});
