<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Modules\V1\Auth\Enums\AuthProviderEnum;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;
use Shared\Helpers\GlobalHelper;


final class AuthenticationService
{
    public static function findOrCreateUser(SocialiteUser $authUser): User
    {
        // Check if the user exists in the database
        $user = User::where('email', $authUser->getEmail())->first();

        if ( ! $user) {
            // User does not exist, create a new user
            $user = User::create([
                'name' => $authUser->getName(),
                'email' => $authUser->getEmail(),
                'provider_type' => AuthProviderEnum::google->name,
                'provider_id' => $authUser->getId(),
                'role_id' => RoleEnum::USER->value,
                'password' => Hash::make(GlobalHelper::generateCode(new User())),
                'oauth' => true,
            ]);
        }

        return $user;
    }
}
