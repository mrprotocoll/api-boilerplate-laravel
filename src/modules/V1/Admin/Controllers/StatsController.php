<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Controllers;

use App\Http\Controllers\V1\Controller;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class StatsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/stats",
     *     summary="Get statistics",
     *     description="Returns overall statistics including total users, active subscribers, total referrals, and platform profit",
     *     operationId="getUserStats",
     *     tags={"Statistics"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="totalUsers",
     *                     type="integer",
     *                     example=1500,
     *                     description="Total number of users in the system"
     *                 ),
     *                 @OA\Property(
     *                     property="activeSubscribers",
     *                     type="integer",
     *                     example=850,
     *                     description="Count of active subscribers"
     *                 ),
     *                 @OA\Property(
     *                     property="totalReferrals",
     *                     type="integer",
     *                     example=320,
     *                     description="Total number of users who have successfully referred others"
     *                 ),
     *                 @OA\Property(
     *                     property="totalProfit",
     *                     type="number",
     *                     format="float",
     *                     example=12500.50,
     *                     description="Total platform profit generated from referrals"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Server Error")
     *         )
     *     )
     * )
     */
    public function stats(): \Illuminate\Http\JsonResponse
    {
        $stats = [
            'totalUsers' => User::count(),
            'activeSubscribers' => User::count(),
            'totalReferrals' => User::countUsersWithReferrals(),
            'totalProfit' => 0,
        ];

        return ResponseHelper::success($stats);
    }
}
