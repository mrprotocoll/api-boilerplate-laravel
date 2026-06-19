<?php

declare(strict_types=1);

namespace Modules\V1\AI\Contracts;

use Modules\V1\AI\DTO\AIActorContext;

interface AIContextProvider
{
    public function key(): string;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function build(?AIActorContext $actor, array $input = []): array;
}
