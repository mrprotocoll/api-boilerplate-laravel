<?php

declare(strict_types=1);

//test('new users can register', function () {
//    $response = $this->post('auth/register', [
//        'name' => 'Test User',
//        'email' => 'test@example.com',
//        'password' => 'password',
//        'password_confirmation' => 'password',
//    ]);
//
//    $this->assertAuthenticated();
//    $response->assertNoContent();
//});

use Database\Seeders\RoleSeeder;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;

test('registers a new user', function (): void {

    $this->seed(RoleSeeder::class);

    $userData = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ];

    $response = $this->postJson('/v1/auth/register', $userData);

    $response
        ->assertStatus(201)
        ->assertJson([
            'message' => 'Registration successful',
        ]);

    // Assert the user was created in the database
    $this->assertDatabaseHas('users', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'role_id' => RoleEnum::USER->value,
    ]);

    // Optional: Assert email verification notification was sent
    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse(); // Assuming email verification is required
});
