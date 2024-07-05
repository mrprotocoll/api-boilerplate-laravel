<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Controllers\Oauth;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\V1\Auth\Services\AuthenticationService;
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

    public function googleOauthLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        // Find or create the user in the database
        $user = Socialite::driver('google')->stateless()->user();

        $user = AuthenticationService::findOrCreateUser($user);
        // Create a new token for the user
        $device = Str::limit($request->userAgent(), 255);
        $token = $user->createToken($device)->plainTextToken;

        return ResponseHelper::success(
            data: new UserResource($user),
            message: 'Login successful',
            meta: ['accessToken' => $token]
        );
    }
    /**
     * Log in with Google authentication.
     *
     * @OA\Post(
     *     path="/auth/google/login",
     *     summary="Log in with Google authentication",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body containing authentication code",
     *         @OA\JsonContent(
     *             required={"authCode"},
     *             @OA\Property(
     *                 property="authCode",
     *                 type="string",
     *                 description="Authentication code received from Google"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
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
     *                 property="accessToken",
     *                 type="string",
     *                 format="text",
     *                 description="Access token for the user"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User resource data",
     *                 ref="#/components/schemas/UserResource"
     *             )
     *         )
     *     )
     * )
     */
    public function googleAuthLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request input
        $request->validate([
            'authCode' => 'required|string',
        ]);

        $token = Socialite::driver('google')->stateless()->getAccessTokenResponse($request->authCode);

        // Get user data from Google
        $authUser = Socialite::driver('google')->userFromToken($token['access_token']);

        // Find or create the user in the database
        $user = AuthenticationService::findOrCreateUser($authUser);

        // Create a new token for the user
        $device = Str::limit($request->userAgent(), 255);
        $token = $user->createToken($device)->plainTextToken;

        return ResponseHelper::success(
            data: new UserResource($user),
            message: 'Login successful',
            meta: ['accessToken' => $token]
        );
    }
}
