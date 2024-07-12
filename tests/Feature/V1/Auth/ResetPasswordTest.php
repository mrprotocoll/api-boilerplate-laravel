<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Auth;

use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\V1\User\Models\User;
use Shared\Helpers\GlobalHelper;
use Tests\TestCase;

/**
 * Test for forgot password request
 */
final class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->route = '/v1/auth/reset-password';
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function it_resets_password_successfully(): void
    {
        // Create a user with a verification token
        $user = User::factory()->create([
            'verification_token' => 'test-token',
            'verification_token_expiry' => Carbon::now()->addHour(),
        ]);

        // Encrypt the token
        $token = GlobalHelper::encrypt('test-token');

        // Send password reset request
        $response = $this->postJson($this->route, [
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the response contains the expected message
        $response->assertJson([
            'message' => 'Password changed successfully',
            'status' => 'success',
            'statusCode' => '200',
        ]);

        // Assert the user's password was changed
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        // Assert the verification token is cleared
//        $this->assertEmpty($user->fresh()->verification_token);
    }

    /** @test */
    public function it_returns_error_for_invalid_token(): void
    {
        // Send password reset request with an invalid token
        $response = $this->postJson($this->route, [
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert the response status code is 422
        $response->assertStatus(422);

    }

    /** @test */
    public function it_returns_error_for_expired_token(): void
    {
        // Create a user with an expired verification token
        $user = User::factory()->create([
            'verification_token' => 'test-token',
            'verification_token_expiry' => Carbon::now()->subHour(),
        ]);

        // Encrypt the token
        $token = GlobalHelper::encrypt('test-token');

        // Send password reset request
        $response = $this->postJson($this->route, [
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert the response status code is 400
        $response->assertStatus(400);

        // Assert the response contains the expected error message
        $response->assertJson([
            'message' => 'Verification token has expired',
            'status' => 'error',
            'statusCode' => '400',
        ]);
    }

    /** @test */
    public function it_returns_validation_errors_for_missing_fields(): void
    {
        // Send password reset request with missing fields
        $response = $this->postJson($this->route, []);

        // Assert the response status code is 422
        $response->assertStatus(422);

        // Assert the response contains validation errors
        $response->assertJsonValidationErrors(['token', 'password']);
    }

    /** @test */
    public function it_returns_validation_errors_for_password_mismatch(): void
    {
        // Send password reset request with mismatched password and confirmation
        $response = $this->postJson($this->route, [
            'token' => 'test-token',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

        // Assert the response status code is 422
        $response->assertStatus(422);

        // Assert the response contains validation errors
        $response->assertJsonValidationErrors(['password']);
    }
}
