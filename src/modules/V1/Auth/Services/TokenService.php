<?php

namespace Modules\V1\Auth\Services;

use Illuminate\Support\Str;
use Modules\V1\Auth\Model\AccessToken;
use Modules\V1\User\Models\User;
use Shared\Helpers\GlobalHelper;

class TokenService
{
    public function createTokens(User $user)
    {
        // Revoke existing tokens
        $user->authTokens()->delete();

        $accessTokenExpiry = now()->addMinutes(config('sanctum.expiration', 1440));
        $refreshTokenExpiry = now()->addDays(30);

        $token = AccessToken::create([
            'tokenable_type' => get_class($user),
            'tokenable_id' => $user->id,
            'token' => base64_encode(Str::random(64)),
            'refresh_token' => base64_encode(Str::random(64)),
            'abilities' => ['*'],
            'expires_at' => $accessTokenExpiry,
            'refresh_token_expires_at' => $refreshTokenExpiry,
        ]);

        return [
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'token_type' => 'Bearer',
            'access_token_expires_at' => $accessTokenExpiry,
            'refresh_token_expires_at' => $refreshTokenExpiry,
        ];
    }

    public function refreshAccessToken(string $refreshToken)
    {
        $token = AccessToken::where('refresh_token', $refreshToken)
            ->where('refresh_token_expires_at', '>', now())
            ->first();

        if (!$token) {
            return null;
        }

        $user = $token->tokenable;
        $token->delete();

        return $this->createTokens($user);
    }

    public function generateTokenString()
    {
        return sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy)
        );
    }
}
