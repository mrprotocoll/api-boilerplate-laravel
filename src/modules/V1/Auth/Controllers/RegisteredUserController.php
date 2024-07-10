<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\V1\Auth\Requests\RegisterRequest;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class RegisteredUserController extends Controller
{
    /**
     * @OA\Post(
     *      path="/auth/register",
     *      summary="Register a new user",
     *     tags={"Authentication"},
     *      description="Registers a new user with the provided information.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(
     *                  @OA\Property(property="name", type="string", description="User's name"),
     *                  @OA\Property(property="email", type="string", format="email", description="User's email"),
     *                  @OA\Property(property="password", type="string", format="password", description="User's password"),
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Registration successful",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Registration successful"),
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="statusCode", type="string", example="201"),
     *              @OA\Property(property="access-token", type="string", example="your_access_token"),
     *              @OA\Property(property="data", ref="#/components/schemas/UserResource"),
     *          )
     *      ),
     *
     *      @OA\Response(response=422, ref="#/components/responses/422"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function store(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = new User();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_id = RoleEnum::USER->value;
        $user->password = Hash::make($request->password);
        $user->save();

        // send email verification mail
        $user->sendEmailVerificationNotification();

        return ResponseHelper::success(message: 'Registration successful', status: 201);
    }
}
