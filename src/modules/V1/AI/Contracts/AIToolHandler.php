<?php

declare(strict_types=1);

namespace Modules\V1\AI\Contracts;

use Modules\V1\AI\DTO\AIToolDefinition;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;

interface AIToolHandler
{
    public function name(): string;

    public function definition(): AIToolDefinition;

    /** @param array<string, mixed> $arguments */
    public function execute(array $arguments, ?User $user): AIToolResult;
}
