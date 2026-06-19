<?php

declare(strict_types=1);

namespace Modules\V1\AI\Tools;

use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;

final class NavigateTool implements AIToolHandler
{
    public function name(): string
    {
        return 'navigate';
    }

    public function definition(): AIToolDefinition
    {
        return new AIToolDefinition(
            name: $this->name(),
            description: 'Return a frontend navigation target for a known route key. Use only when the user asks to open or navigate somewhere.',
            inputSchema: [
                'properties' => [
                    'routeKey' => [
                        'type' => 'string',
                        'description' => 'Known route key such as home, dashboard, profile, or settings.',
                    ],
                ],
                'required' => ['routeKey'],
            ],
            requiresAuth: false,
        );
    }

    public function execute(array $arguments, ?User $user): AIToolResult
    {
        $routeKey = isset($arguments['routeKey']) && is_string($arguments['routeKey'])
            ? $arguments['routeKey']
            : '';
        $routes = config('ai.assistant.navigation.routes', []);
        $path = is_array($routes) ? ($routes[$routeKey] ?? null) : null;

        if ( ! is_string($path)) {
            return new AIToolResult(
                status: 'error',
                kind: 'navigation',
                summary: 'The requested route is not available.',
                data: [
                    'routeKey' => $routeKey,
                    'supportedRouteKeys' => is_array($routes) ? array_keys($routes) : [],
                ],
                display: ['type' => 'action', 'mode' => 'always'],
            );
        }

        return new AIToolResult(
            status: 'success',
            kind: 'navigation',
            summary: "Open {$routeKey}.",
            data: [
                'routeKey' => $routeKey,
                'path' => $path,
            ],
            display: ['type' => 'action', 'mode' => 'always'],
        );
    }
}
