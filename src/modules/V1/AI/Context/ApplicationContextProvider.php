<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\AI\DTO\AIActorContext;

final class ApplicationContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'application';
    }

    public function build(?AIActorContext $actor, array $input = []): array
    {
        return [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'apiVersion' => 'v1',
        ];
    }
}
