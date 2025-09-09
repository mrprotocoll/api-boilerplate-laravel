<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Resources;

use Illuminate\Http\Request;
use Modules\V1\Logging\Facades\Activity;
use Modules\V1\Logging\Model\ActivityLog;
use Modules\V1\User\Models\User;
use Shared\Helpers\ResponseHelper;

final class ActivityController
{
    public function getUserActivities(User $user): \Illuminate\Http\JsonResponse
    {
        // Get all activities by a user
        $activities = Activity::getActivitiesBy($user);

        return ResponseHelper::success($activities);
    }

    public function getModelActivities(Request $request): \Illuminate\Http\JsonResponse
    {
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        $model = $modelType::find($modelId);
        $activities = Activity::getActivitiesFor($model);

        return ResponseHelper::success($activities);
    }

    public function getActivityDashboard()
    {
        $data = [
            'recent_activities' => ActivityLog::with(['user', 'subject'])
                ->recent(7)
                ->limit(20)
                ->get(),

            'activity_summary' => [
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            ],

            'top_events' => ActivityLog::selectRaw('event, COUNT(*) as count')
                ->recent(30)
                ->groupBy('event')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            'most_active_users' => ActivityLog::selectRaw('user_id, COUNT(*) as count')
                ->recent(30)
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->with('user')
                ->limit(10)
                ->get(),
        ];

        return ResponseHelper::success($data);
    }
}
