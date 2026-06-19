<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\DTO\AIUsageLimitResult;
use Modules\V1\AI\Models\AIMessage;

final class AIUsageLimiter
{
    public function check(?AIActorContext $actor): AIUsageLimitResult
    {
        if (null === $actor) {
            return new AIUsageLimitResult(true);
        }

        $messageLimit = config("ai.assistant.limits.daily_{$actor->scope}_message_limit");
        if (is_numeric($messageLimit)) {
            $count = AIMessage::query()
                ->where('actor_type', $actor->morphClass())
                ->where('actor_id', $actor->id())
                ->where('role', 'user')
                ->where('created_at', '>=', now()->startOfDay()->timestamp)
                ->count();

            if ($count >= (int) $messageLimit) {
                return new AIUsageLimitResult(false, 'Daily AI message limit reached.', "daily_{$actor->scope}_message_limit");
            }
        }

        $costLimit = config("ai.assistant.limits.daily_{$actor->scope}_cost_limit");
        if (is_numeric($costLimit)) {
            $cost = (float) AIMessage::query()
                ->where('actor_type', $actor->morphClass())
                ->where('actor_id', $actor->id())
                ->where('created_at', '>=', now()->startOfDay()->timestamp)
                ->sum('cost');

            if ($cost >= (float) $costLimit) {
                return new AIUsageLimitResult(false, 'Daily AI cost limit reached.', "daily_{$actor->scope}_cost_limit");
            }
        }

        return new AIUsageLimitResult(true);
    }
}
