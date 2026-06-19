<?php

declare(strict_types=1);

namespace Modules\V1\AI\Tools;

use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;

final class AuthenticatedUserTool implements AIToolHandler
{
    public function name(): string
    {
        return 'authenticated_user';
    }

    public function definition(): AIToolDefinition
    {
        return new AIToolDefinition(
            name: $this->name(),
            description: 'Get the safe profile fields for the authenticated API user.',
            inputSchema: [],
        );
    }

    public function execute(array $arguments, ?User $user): AIToolResult
    {
        if (null === $user) {
            return new AIToolResult(
                status: 'error',
                kind: 'authenticated_user',
                summary: 'No authenticated user is available.',
                display: ['type' => 'notice', 'mode' => 'always'],
            );
        }

        $data = [
            'id' => (string) $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name ?? null,
            'lastName' => $user->last_name ?? null,
            'roleId' => $user->role_id ?? null,
            'emailVerified' => null !== $user->email_verified_at,
        ];

        return new AIToolResult(
            status: 'success',
            kind: 'authenticated_user',
            summary: 'The authenticated user profile was retrieved.',
            data: $data,
        );
    }
}
