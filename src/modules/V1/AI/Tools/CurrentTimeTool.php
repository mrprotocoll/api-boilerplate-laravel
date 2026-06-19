<?php

declare(strict_types=1);

namespace Modules\V1\AI\Tools;

use Illuminate\Support\Carbon;
use Modules\V1\AI\Contracts\AIToolHandler;
use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;

final class CurrentTimeTool implements AIToolHandler
{
    public function name(): string
    {
        return 'current_time';
    }

    public function definition(): AIToolDefinition
    {
        return new AIToolDefinition(
            name: $this->name(),
            description: 'Get the current application date and time.',
            inputSchema: [
                'properties' => [
                    'timezone' => [
                        'type' => 'string',
                        'description' => 'Optional timezone. Defaults to the application timezone.',
                    ],
                ],
            ],
            requiresAuth: false,
        );
    }

    public function execute(array $arguments, ?User $user): AIToolResult
    {
        $timezone = isset($arguments['timezone']) && is_string($arguments['timezone'])
            ? $arguments['timezone']
            : (string) config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);

        return new AIToolResult(
            status: 'success',
            kind: 'current_time',
            summary: 'The current application time is ' . $now->toDayDateTimeString() . " ({$timezone}).",
            data: [
                'timezone' => $timezone,
                'iso' => $now->toISOString(),
                'timestamp' => $now->timestamp,
                'formatted' => $now->toDayDateTimeString(),
            ],
        );
    }
}
