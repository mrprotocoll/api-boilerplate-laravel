<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\V1\Auth\Enums\AuthProviderEnum;
use Modules\V1\Auth\Notifications\Welcome;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;
use Shared\Helpers\GlobalHelper;

final class AuthenticationService
{
    public static function findOrCreateUser(SocialiteUser $authUser): User
    {
        // Check if the user exists in the database
        $user = User::where('email', $authUser->getEmail())->first();

        if ( ! $user) {
            $fullName = $authUser->getName();
            $nameParts = explode(' ', $fullName);

            $firstName = $nameParts[0] ?? '';
            $lastName = implode(' ', array_slice($nameParts, 1)) ?? '';

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $authUser->getEmail(),
                'provider_type' => AuthProviderEnum::google->name,
                'provider_id' => $authUser->getId(),
                'role_id' => RoleEnum::USER->value,
                'password' => Hash::make(GlobalHelper::generateCode(new User())),
                'oauth' => true,
            ]);

            $user->markEmailAsVerified();
            $user->notify(new Welcome($user, config('constants.user_dashboard')));
        }

        return $user;
    }

    public static function createToken(User|Authenticatable $user, $request): string
    {
        $device = Str::limit($request->userAgent(), 255);
        $token = $user->createToken($device)->plainTextToken;

        return $token;
    }
}
