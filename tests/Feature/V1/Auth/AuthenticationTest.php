<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Modules\V1\User\Models\User;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('logs in successfully with valid credentials and verified email', function (): void {
    $password = 'password123';
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'message',
        'status',
        'statusCode',
        'accessToken',
        'data' => [
            'id',
            'name',
            'email',
        ],
    ]);
});

it('users cannot authenticate with invalid credentials', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(401);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Invalid credentials',
    ]);
});

it('users cannot authenticate with unverified email', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->post('/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(403);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Email not verified. Kindly verify your email',
    ]);
});

it('logs out successfully when authenticated', function () {
    $user = User::factory()->create();
    $token = $user->createToken('TestDevice')->plainTextToken;

    $response = $this->postJson('/v1/auth/logout', [], ['Authorization' => "Bearer $token"]);

    $response->assertStatus(204);

    $this->assertCount(0, $user->tokens);
});

it('returns error when not authenticated', function () {
    $response = $this->postJson('/v1/auth/logout');

    $response->assertStatus(401);
});
