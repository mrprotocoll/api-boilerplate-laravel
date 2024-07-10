<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Shared\Enums\RoleEnum;
use Modules\V1\User\Models\User;

final class AuthenticationService
{
    public static function findOrCreateUser($authUser): User
    {
        // Check if the user exists in the database
        $user = User::where('email', $authUser->getEmail())->first();

        if ( ! $user) {
            // User does not exist, create a new user
            $user = User::create([
                'name' => $authUser->getName(),
                'email' => $authUser->getEmail(),
                'provider_type' => 'google',
                'provider_id' => $authUser->getId(),
                'role_id' => RoleEnum::USER->value,
                'password' => Hash::make('#Password2024'),
                'oauth' => true,
                'balance' => env('INITIAL_CREDIT'),
            ]);
        }

        return $user;
    }
}
