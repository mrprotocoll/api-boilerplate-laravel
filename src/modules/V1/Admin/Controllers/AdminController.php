<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\Admin\Notifications\AdminInvite;
use Modules\V1\Admin\Requests\AdminInviteRequest;
use Modules\V1\Admin\Resources\AdminResource;
use Modules\V1\User\Models\Role;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\ResponseHelper;

final class AdminController extends AdminBaseController
{
    /**
     * @OA\Get(
     *      path="/admin/admins",
     *      summary="Get all admins",
     *      description="Returns a list of all admins.",
     *      tags={"Admins"},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AdminResource")),
     *          )
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index()
    {
        $admin = Admin::paginate();

        return ResponseHelper::success(AdminResource::collection($admin));
    }

    /**
     * @OA\Get(
     *      path="/admin/admins/{admin}",
     *      summary="Get admin details",
     *      description="Returns details of a specific agent.",
     *      tags={"Admins"},
     *
     *      @OA\Parameter(
     *          name="agent",
     *          in="path",
     *          description="Admin ID",
     *          required=true,
     *
     *          @OA\Schema(
     *              type="string",
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="data", ref="#/components/schemas/AdminResource"),
     *          )
     *      ),
     *
     *      @OA\Response(response=404, ref="#/components/responses/404"),
     *      @OA\Response(response=500, ref="#/components/responses/500"),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(Admin $admin)
    {
        return ResponseHelper::success(new AdminResource($admin));
    }

    /**
     * @OA\Post(
     *     path="/admin/admins",
     *     summary="Invite a new admin",
     *     tags={"Admin"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/AdminInviteRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AdminResource")
     *     ),
     *
     *     @OA\Response(
     *         response=209,
     *         description="Account already exists"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function store(AdminInviteRequest $request)
    {
        // check if admin already exists
        if (Admin::where('email', $request->email)->exists()) {
            return ResponseHelper::error('Account already exist', 209);
        }

        try {
            $request->password = Hash::make($request->password);
            $role = Role::find($request->role);
            $admin = Admin::create($request->validated());

            $timestamp = DateTimeHelper::timestamp();
            $admin->roles()->attach($role, [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            // create password reset
            $token = $admin->createVerificationToken();
            $inviteLink = config('constants.admin_invite_link') . "/{$token}";
            $admin->notify(new AdminInvite($inviteLink));

            return ResponseHelper::success(data: new AdminResource($admin), message: 'Registration successful', status: 201);
        } catch (Exception $e) {
            Log::error($e);

            return ResponseHelper::error();
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/admins/{admin}",
     *     summary="Update an admin",
     *     tags={"Admin"},
     *
     *     @OA\Parameter(
     *         name="admin",
     *         in="path",
     *         required=true,
     *         description="UUID of the admin",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email"},
     *
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Admin updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/AdminResource")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function update(Request $request, Admin $admin)
    {
        try {
            // Validate request data
            $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($admin->id),
                ],
            ]);

            // Update the admin
            $admin->update($request->only(['first_name', 'last_name', 'email']));

            return ResponseHelper::success(data: new AdminResource($admin), message: 'Admin updated successfully');
        } catch (Exception $e) {
            Log::error($e);

            return ResponseHelper::error();
        }
    }

    /**
     * Change roles for the specified admin.
     *
     *
     * @OA\Patch(
     *      path="/admin/admins/{admin}/change-role",
     *      operationId="changeAdminRole",
     *      tags={"Admins"},
     *      summary="Change roles for the specified admin",
     *      description="Updates the roles assigned to a specific admin.",
     *
     *      @OA\Parameter(
     *          name="admin",
     *          in="path",
     *          required=true,
     *          description="ID of the admin",
     *
     *          @OA\Schema(
     *              type="string",
     *          )
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Role data",
     *
     *          @OA\JsonContent(
     *              required={"roles"},
     *
     *              @OA\Property(property="roles", type="array", @OA\Items(type="integer"), example={1, 2}, description="Array of role IDs to assign to the admin")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(
     *                  property="success",
     *                  type="boolean",
     *                  example=true,
     *                  description="Indicates if the request was successful"
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  description="Admin resource",
     *                  ref="#/components/schemas/AdminResource"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Role changed successfully",
     *                  description="A message indicating the result of the operation"
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="The given data was invalid.",
     *                  description="Error message indicating invalid data"
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Server Error",
     *                  description="Error message indicating internal server error"
     *              )
     *          )
     *      )
     * )
     */
    public function changeRole(Request $request, Admin $admin): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request data
            $this->validate($request, [
                'roles' => 'required|array',
                'roles.*' => 'required|exists:roles,id',
            ]);

            // Sync the admins roles
            $admin->roles()->sync($request->roles);

            return ResponseHelper::success(data: new AdminResource($admin), message: 'Role changed successfully');
        } catch (Exception $e) {
            Log::error($e);

            return ResponseHelper::error();
        }
    }
}
