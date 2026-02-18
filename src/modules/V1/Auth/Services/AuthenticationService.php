<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\V1\Auth\Notifications\Welcome;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ResponseHelper;

final class AuthenticationService
{

    public static function createToken(User|Authenticatable $user): string
    {
        $expiry = intval(config('sanctum.expiration'));
        $device = Str::limit(request()->userAgent(), 255);
        $token = $user->createToken($device, ['*'], now()->addMinutes($expiry))->plainTextToken;

        return $token;
    }

    public static function authLoginResponse(User $user, ?string $accessToken = null): \Illuminate\Http\JsonResponse
    {
        $inactivityTimeout = intval(config('sanctum.expiration'));

        $token = $accessToken ?? AuthenticationService::createToken($user);

        return ResponseHelper::success(
            data: new UserResource($user),
            message: 'Login successful',
            meta: [
                'accessToken' => $token,
                'expiresIn' => now()->addMinutes($inactivityTimeout),
            ]
        );
    }
}
