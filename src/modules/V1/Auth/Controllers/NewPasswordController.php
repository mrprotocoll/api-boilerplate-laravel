<?php

namespace Modules\V1\Auth\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use Modules\V1\Auth\Requests\ResetPasswordRequest;
use Shared\Helpers\GlobalHelper;
use Shared\Helpers\ResponseHelper;
use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Modules\V1\User\Models\User;

class NewPasswordController extends Controller
{

    /**
     * Change user password after email verification.
     *
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Change user password after email verification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request body containing the verification token and new password",
     *         @OA\JsonContent(
     *             required={"token", "password", "password_confirmation"},
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Verification token received in the email"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="New password"
     *             ),
     *             @OA\Property(
     *                 property="password_confirmation",
     *                 type="string",
     *                 description="Confirmation of the new password"
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
     *                 example="Password changed successfully",
     *                 description="Message indicating successful password change"
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
     *                 example="Verification token has expired",
     *                 description="Message indicating expired verification token"
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
    public function store(ResetPasswordRequest $request): JsonResponse
    {
        try {
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

            // Change user's password
            $user->password = Hash::make($request->password);
            $user->save();

            return ResponseHelper::success(message: 'Password changed successfully');

        }catch (DecryptException $e) {
            Log::error('Invalid decryption token: ' . $e->getMessage());
            return ResponseHelper::error('Invalid verification token', 422); // or throw a custom exception
        }catch (\Exception $e) {
            Log::error($e);
            return ResponseHelper::error();
        }
    }
}
