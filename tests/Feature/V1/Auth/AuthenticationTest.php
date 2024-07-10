<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Modules\V1\User\Models\User;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'Database\Seeders\RoleSeeder']);
});

test('users can authenticate using valid credentials and verified email', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertJson([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $user->id,
            'email' => $user->email,
        ],
    ]);
    $response->assertJsonStructure(['meta' => ['accessToken']]);
});

test('users cannot authenticate with invalid credentials', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid credentials',
    ]);
});

test('users cannot authenticate with unverified email', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->post('/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
        'message' => 'Email not verified. Kindly verify your email',
    ]);
});
