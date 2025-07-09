<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers\Auth;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Modules\V1\Auth\Requests\LoginRequest;
use Modules\V1\Auth\Services\AuthenticationService;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\Admin\Resources\AdminResource;
use Shared\Helpers\ResponseHelper;

final class LoginController extends Controller
{
    /**
     * @OA\Post(
     *     path="/admin/auth/login",
     *     summary="Admin login",
     *     description="Authenticate an admin user",
     *     operationId="adminLogin",
     *     tags={"Admin Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Admin login credentials",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", example="adminpassword"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="access-token", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/AdminResource"),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="string", example="200"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response="422",
     *         description="Invalid credentials",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect."),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="statusCode", type="string", example="422"),
     *         ),
     *     ),
     * )
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = Admin::where('email', $request->email)->first();

        if ( ! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'status' => 'error',
                'statusCode' => '422',
            ], 422);
        }

        return ResponseHelper::success(
            data: new AdminResource($user),
            message: 'Login successful',
            meta: ['accessToken' => AuthenticationService::createToken($user, $request)]
        );
    }
}
