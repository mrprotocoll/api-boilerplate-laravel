<?php

namespace Modules\V1\Auth\Controllers;

use Shared\Helpers\GlobalHelper;
use Shared\Helpers\ResponseHelper;
use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\V1\Auth\Mail\WelcomeMail;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;

class VerifyEmailController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/email/verify",
     *     summary="Verify user email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body containing the verification token",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Verification token"
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
     *                 example="User verified successfully",
     *                 description="Message indicating successful user verification"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success",
     *                 description="Status of the response"
     *             ),
     *             @OA\Property(
     *                 property="statusCode",
     *                 type="integer",
     *                 example=200,
     *                 description="HTTP status code"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Failed to verify email",
     *                 description="Message indicating failure to verify email"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error",
     *                 description="Status of the response"
     *             ),
     *             @OA\Property(
     *                 property="statusCode",
     *                 type="integer",
     *                 example=400,
     *                 description="HTTP status code"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Invalid verification token",
     *                 description="Message indicating invalid verification token"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error",
     *                 description="Status of the response"
     *             ),
     *             @OA\Property(
     *                 property="statusCode",
     *                 type="integer",
     *                 example=404,
     *                 description="HTTP status code"
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = GlobalHelper::decrypt($request->token);
        // Find the user by the verification token
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return ResponseHelper::error('Invalid verification token', 404);
        }

        // Check if the token has expired
        if ($user->verification_token_expiry && (new Carbon($user->verification_token_expiry))->isPast()) {
            return ResponseHelper::error('Verification token has expired', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ResponseHelper::error('Email already verified', 400);
        }

        if (!$user->markEmailAsVerified()) {
            return ResponseHelper::error('Failed to verify email');
        }

        Mail::send(new WelcomeMail($user, config('constants.user_dashboard')));

        $device = Str::limit($request->userAgent(), 255);
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'message' => 'User verified successfully',
            'status' => 'success',
            'statusCode' => '200',
            'accessToken' => $token,
            'data' => new UserResource($user)
        ]);
    }
}