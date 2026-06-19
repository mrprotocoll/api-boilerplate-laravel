<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\User\Models\User;

final class AuthenticatedUserContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'authenticated_user';
    }

    public function build(?AIActorContext $actor, array $input = []): array
    {
        if (null === $actor || ! $actor->model instanceof User) {
            return ['authenticated' => false];
        }

        $user = $actor->model;

        return [
            'authenticated' => true,
            'scope' => $actor->scope,
            'id' => (string) $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name ?? null,
            'lastName' => $user->last_name ?? null,
        ];
    }
}
