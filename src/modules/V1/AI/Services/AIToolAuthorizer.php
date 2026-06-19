<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\User\Models\User;

final class AIToolAuthorizer
{
    public function authorize(AIToolDefinition $definition, ?User $user): bool
    {
        if ($definition->requiresAuth && null === $user) {
            return false;
        }

        // Applications can replace this service to enforce abilities/permissions.
        return ! $definition->mutatesState && ! $definition->requiresConfirmation;
    }
}
