<?php

namespace Modules\V1\Auth\Controllers;

use Shared\Helpers\ResponseHelper;
use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\V1\User\Models\User;

class EmailVerificationNotificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/email/verification-link",
     *     summary="Send email verification notification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body containing user email",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="User's email address"
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
     *                 example="Verification email sent successfully",
     *                 description="Message indicating successful email sending"
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
     *                 example="Email already verified",
     *                 description="Message indicating that the email is already verified"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Retrieve the user by email
            $user = User::where('email', $request->email)->first();

            // Check if the user's email is already verified
            if ($user->hasVerifiedEmail()) {
                return ResponseHelper::error('Email already verified', 400);
            }

            $user->sendEmailVerificationNotification();

            return ResponseHelper::success(message: 'Verification link sent successfully to your email');
        }
        catch (\Exception $e) {
            Log::error($e);
            return ResponseHelper::error();
        }

    }

}
