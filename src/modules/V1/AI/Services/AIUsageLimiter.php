<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\DTO\AIUsageLimitResult;
use Modules\V1\AI\Models\AIMessage;
use Modules\V1\User\Models\User;

final class AIUsageLimiter
{
    public function check(?User $user): AIUsageLimitResult
    {
        if (null === $user) {
            return new AIUsageLimitResult(true);
        }

        $messageLimit = config('ai.assistant.limits.daily_user_message_limit');
        if (is_numeric($messageLimit)) {
            $count = AIMessage::query()
                ->where('user_id', $user->id)
                ->where('role', 'user')
                ->where('created_at', '>=', now()->startOfDay()->timestamp)
                ->count();

            if ($count >= (int) $messageLimit) {
                return new AIUsageLimitResult(false, 'Daily AI message limit reached.', 'daily_user_message_limit');
            }
        }

        $costLimit = config('ai.assistant.limits.daily_user_cost_limit');
        if (is_numeric($costLimit)) {
            $cost = (float) AIMessage::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfDay()->timestamp)
                ->sum('cost');

            if ($cost >= (float) $costLimit) {
                return new AIUsageLimitResult(false, 'Daily AI cost limit reached.', 'daily_user_cost_limit');
            }
        }

        return new AIUsageLimitResult(true);
    }
}
