<?php

declare(strict_types=1);

namespace Modules\V1\User\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ResponseHelper;

final class UserController extends Controller
{
    public function show(User $user): \Illuminate\Http\JsonResponse
    {
        return ResponseHelper::success(new UserResource($user));
    }

    /**
     * @OA\Put(
     *     path="/user",
     *     summary="Update user profile",
     *     description="Updates the user's name and job title in the profile",
     *     operationId="updateUserProfile",
     *     tags={"User"},
     *
     *     @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/UserUpdateRequest")
     *      ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Profile updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=204),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserResource"),
     *         ),
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     * )
     */
    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $user->update([
            'name' => $request->name,
        ]);

        return ResponseHelper::success(data: new UserResource($user), message: 'Profile updated successfully');
    }

    /**
     * @OA\Put(
     *     path="/user/change-password",
     *     summary="Change User Password",
     *     description="Change the user's password.",
     *     operationId="changePassword",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password change request data",
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="current_password", type="string", description="Current password", example="old_password"),
     *                 @OA\Property(property="new_password", type="string", description="New password", example="new_password"),
     *                 @OA\Property(property="new_password_confirmation", type="string", description="Confirm new password", example="new_password"),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ( ! Hash::check($request->input('current_password'), $user->password)) {
            return ResponseHelper::error('Invalid current password', 402);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return ResponseHelper::success('Password changed successfully');
    }
}
