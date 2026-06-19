<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\AI\DTO\AIActorContext;

final class RequestContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'request';
    }

    public function build(?AIActorContext $actor, array $input = []): array
    {
        return [
            'sourcePage' => isset($input['sourcePage']) && is_string($input['sourcePage']) ? $input['sourcePage'] : null,
            'metadata' => isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : [],
        ];
    }
}
