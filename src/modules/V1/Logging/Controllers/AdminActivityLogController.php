<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Modules\V1\Logging\Model\AdminActivityLog;
use Modules\V1\Logging\Resources\AdminActivityLogResource;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class AdminActivityLogController extends Controller
{
    public function activityLogs(): JsonResponse
    {
        $logs = AdminActivityLog::with('admin')->latest()->paginate();

        return ResponseHelper::success(data: AdminActivityLogResource::collection($logs));
    }

    public function userActivityLogs(User $user): JsonResponse
    {
        $logs = AdminActivityLog::with('admin')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->latest()
            ->paginate();

        return ResponseHelper::success(data: AdminActivityLogResource::collection($logs));
    }
}
