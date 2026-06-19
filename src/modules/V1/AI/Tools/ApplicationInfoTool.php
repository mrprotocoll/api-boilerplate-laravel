<?php

declare(strict_types=1);

namespace Modules\V1\AI\Tools;

use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;

final class ApplicationInfoTool implements AIToolHandler
{
    public function name(): string
    {
        return 'application_info';
    }

    public function definition(): AIToolDefinition
    {
        return new AIToolDefinition(
            name: $this->name(),
            description: 'Get non-sensitive application metadata such as app name, environment, timezone, and API version.',
            inputSchema: [],
            requiresAuth: false,
        );
    }

    public function execute(array $arguments, ?User $user): AIToolResult
    {
        return new AIToolResult(
            status: 'success',
            kind: 'application_info',
            summary: 'Application metadata was retrieved.',
            data: [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'timezone' => config('app.timezone'),
                'apiVersion' => 'v1',
            ],
        );
    }
}
