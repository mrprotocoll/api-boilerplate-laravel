<?php

declare(strict_types=1);

namespace Modules\V1\AI\Tools;

use Modules\V1\Admin\Models\Admin;
use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;

final class AuthenticatedAdminTool implements AIToolHandler
{
    public function name(): string
    {
        return 'authenticated_admin';
    }

    public function definition(): AIToolDefinition
    {
        return new AIToolDefinition(
            name: $this->name(),
            description: 'Get the safe profile fields for the authenticated internal admin.',
            inputSchema: [],
            scopes: [AIActorContext::SCOPE_ADMIN],
        );
    }

    public function execute(array $arguments, ?AIActorContext $actor): AIToolResult
    {
        if (null === $actor || ! $actor->model instanceof Admin) {
            return new AIToolResult(
                status: 'error',
                kind: 'authenticated_admin',
                summary: 'No authenticated admin is available.',
                display: ['type' => 'notice', 'mode' => 'always'],
            );
        }

        $admin = $actor->model;

        return new AIToolResult(
            status: 'success',
            kind: 'authenticated_admin',
            summary: 'The authenticated admin profile was retrieved.',
            data: [
                'id' => (string) $admin->id,
                'email' => $admin->email,
                'firstName' => $admin->first_name ?? null,
                'lastName' => $admin->last_name ?? null,
                'superAdmin' => (bool) ($admin->super_admin ?? false),
                'emailVerified' => null !== $admin->email_verified_at,
            ],
        );
    }
}
