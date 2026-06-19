<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\User\Models\User;

final class AuthenticatedUserContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'authenticated_user';
    }

    public function build(?User $user, array $input = []): array
    {
        if (null === $user) {
            return ['authenticated' => false];
        }

        return [
            'authenticated' => true,
            'id' => (string) $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name ?? null,
            'lastName' => $user->last_name ?? null,
        ];
    }
}
