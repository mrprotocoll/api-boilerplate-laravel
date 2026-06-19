<?php

declare(strict_types=1);

namespace Modules\V1\AI\Contracts;

use Modules\V1\User\Models\User;

interface AIContextProvider
{
    public function key(): string;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function build(?User $user, array $input = []): array;
}
