<?php

declare(strict_types=1);

namespace Modules\V1\AI\Context;

use Modules\V1\AI\Contracts\AIContextProvider;
use Modules\V1\User\Models\User;

final class ApplicationContextProvider implements AIContextProvider
{
    public function key(): string
    {
        return 'application';
    }

    public function build(?User $user, array $input = []): array
    {
        return [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'apiVersion' => 'v1',
        ];
    }
}
