<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Modules\V1\Auth\Notifications\Welcome;
use Modules\V1\User\Models\User;
use Shared\Helpers\GlobalHelper;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->route = 'v1/auth/email/verify';
    Notification::fake();
});

it('verifies user email with valid token', function (): void {
    $token = 'validtoken';
    $encryptedToken = GlobalHelper::encrypt($token);

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->addMinutes(30),
        'email_verified_at' => null
    ]);

    $response = $this->postJson($this->route, [
        'token' => $encryptedToken,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'User verified successfully',
        'status' => 'success',
        'statusCode' => '200',
    ]);

    Notification::assertSentTo([$user], Welcome::class);
});

it('returns error for invalid token', function (): void {
    $response = $this->postJson($this->route, [
        'token' => 'invalid_token',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid verification token',
        'status' => 'error',
        'statusCode' => '422',
    ]);
});

it('returns error for expired token', function (): void {
    $token = 'valid_token';
    $encryptedToken = GlobalHelper::encrypt($token);

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->subMinutes(30),
    ]);

    $response = $this->postJson($this->route, [
        'token' => $encryptedToken,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Verification token has expired',
        'status' => 'error',
        'statusCode' => '400',
    ]);
});

it('returns error for already verified email', function (): void {
    $token = 'valid_token';
    $encryptedToken = GlobalHelper::encrypt($token);

    $user = User::factory()->create([
        'verification_token' => $token,
        'verification_token_expiry' => Carbon::now()->addMinutes(30),
        'email_verified_at' => Carbon::now(),
    ]);

    $response = $this->postJson($this->route, [
        'token' => $encryptedToken,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Email already verified',
        'status' => 'error',
        'statusCode' => '400',
    ]);
});

