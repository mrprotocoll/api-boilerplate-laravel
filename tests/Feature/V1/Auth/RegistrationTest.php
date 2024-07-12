<?php

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

test('registers a new user', function () {

    $this->seed(RoleSeeder::class);

    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson('/v1/auth/register', $userData);

    $response
        ->assertStatus(201)
        ->assertJson([
            'message' => 'Registration successful',
        ]);

    // Assert the user was created in the database
    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role_id' => RoleEnum::USER->value,
    ]);

    // Optional: Assert email verification notification was sent
    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse(); // Assuming email verification is required
});
