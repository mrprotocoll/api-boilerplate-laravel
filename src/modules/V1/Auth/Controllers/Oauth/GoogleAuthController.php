<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Controllers\Oauth;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\V1\Auth\Notifications\WelcomeNotification;
use Modules\V1\Auth\Services\AuthenticationService;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ResponseHelper;

final class GoogleAuthController extends Controller
{
    /**
     * Get the Google authentication URL.
     *
     * @OA\Get(
     *     path="/auth/oauth/google",
     *     summary="Get Google authentication URL",
     *     tags={"Authentication"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Login successful",
     *                 description="Message indicating successful login"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success",
     *                 description="Status of the response"
     *             ),
     *             @OA\Property(
     *                 property="statusCode",
     *                 type="string",
     *                 example="200",
     *                 description="HTTP status code"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="url",
     *                     type="string",
     *                     format="url",
     *                     description="URL for Google authentication"
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function googleAuthUrl(): \Illuminate\Http\JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return ResponseHelper::success(['url' => $url]);
    }

    public function googleAuthLogin(Request $request): JsonResponse
    {
        // Validate request input
        $request->validate([
            'authCode' => 'required|string',
        ]);

        $token = Socialite::driver('google')->stateless()->getAccessTokenResponse($request->authCode);

        // Get user data from Google
        $authUser = Socialite::driver('google')->userFromToken($token['access_token']);

        // Find or create the user in the database
        return $this->findOrCreateTheUserInTheDatabase($authUser, $request);
    }

    public function findOrCreateTheUserInTheDatabase($authUser, Request $request): JsonResponse
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
                'provider_type' => 'google',
                'provider_id' => $authUser->getId(),
                'password' => Hash::make('Passw0rd100000W@rd'),
                'oauth' => true,
            ]);

            $user->markEmailAsVerified();
            $user->notify(new WelcomeNotification());
        }

        // Revoke all existing tokens for the user
        $user->tokens()->delete();

        // Create a new token for the user
        return AuthenticationService::authLoginResponse($user);

    }
}
