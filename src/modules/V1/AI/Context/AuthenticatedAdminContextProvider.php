<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\Admin\Models\Admin;
use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\AI\DTO\AIActorContext;

final class AuthenticatedAdminContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'authenticated_admin';
    }

    public function build(?AIActorContext $actor, array $input = []): array
    {
        if (null === $actor || ! $actor->model instanceof Admin) {
            return ['authenticated' => false];
        }

        $admin = $actor->model;

        return [
            'authenticated' => true,
            'scope' => $actor->scope,
            'id' => (string) $admin->id,
            'email' => $admin->email,
            'firstName' => $admin->first_name ?? null,
            'lastName' => $admin->last_name ?? null,
            'superAdmin' => (bool) ($admin->super_admin ?? false),
        ];
    }
}
