<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Services;

use Illuminate\Support\Facades\Auth;
use Modules\V1\Logging\Model\AdminActivityLog;

trait LogActivityService
{
    public function logAdminAction(?string $adminId, $action, $model = null, array $meta = []): void
    {
        AdminActivityLog::create([
            'admin_id' => Auth::id() ?? $adminId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'meta' => $meta,
        ]);
    }

    public function logUserAction(?string $adminId, $action, $model = null, array $meta = []): void
    {
        ActivityLog::create([
            'admin_id' => Auth::id() ?? $adminId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'meta' => $meta,
        ]);
    }
}
