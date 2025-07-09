<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\V1\Admin\Controllers\AdminBaseController;
use Shared\Helpers\ResponseHelper;

/**
 * @group Authentication
 *
 * Endpoint to manage user authentication
 */
final class LogoutController extends AdminBaseController
{
    /**
     * @OA\Post(
     *     path="/admin/auth/logout",
     *     summary="Admin logout",
     *     description="Logout the currently authenticated admin user",
     *     operationId="adminLogout",
     *     tags={"Admin Authentication"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response="204",
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="string", example="204"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response="402",
     *         description="Unauthorized user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthorized user"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="statusCode", type="string", example="402"),
     *         ),
     *     ),
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
//        if ( ! Auth::guard('admin')->check()) {
//            return response()->json([
//                'message' => 'Unauthorized user',
//                'status' => 'error',
//                'statusCode' => '402',
//            ], 402);
//        }

        // Revoke the token that was used to authenticate the current request
        if($request->user()->currentAccessToken()->delete()){
            return ResponseHelper::success(message: 'logged out successfully');
        }

        return ResponseHelper::error();
    }
}
