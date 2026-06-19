<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\DTO\AIToolDefinition;

final class AIToolAuthorizer
{
    public function authorize(AIToolDefinition $definition, ?AIActorContext $actor): bool
    {
        if ($definition->requiresAuth && null === $actor) {
            return false;
        }

        if (null !== $actor && [] !== $definition->scopes && ! in_array($actor->scope, $definition->scopes, true)) {
            return false;
        }

        // Applications can replace this service to enforce abilities/permissions.
        return ! $definition->mutatesState && ! $definition->requiresConfirmation;
    }
}
