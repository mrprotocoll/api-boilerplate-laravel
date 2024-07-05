<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Controllers;

use App\Http\Controllers\V1\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class PasswordResetLinkController extends Controller
{
    /**
     * Send password reset link.
     *
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     summary="Send password reset link",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body containing user email",
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="User's email address"
     *             )
     *         )
     *     ),
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
     *                 example="Password reset link sent successfully",
     *                 description="Message indicating successful password reset link sending"
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
     *
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Retrieve the user by email
        $user = User::where('email', $request->email)->first();

        // Send password reset notification
        try {
            $user->sendPasswordResetNotification();
        } catch (Exception $e) {
            Log::error($e);

            return ResponseHelper::error('Failed to send password reset link');
        }

        return ResponseHelper::success(message: 'Password reset link sent successfully');

    }
}
