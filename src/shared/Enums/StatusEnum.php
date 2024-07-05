<?php

declare(strict_types=1);

namespace Shared\Enums;

enum StatusEnum: string
{
    const PENDING = 'PENDING';

    const APPROVED = 'APPROVED';

    const FAILED = 'FAILED';

    const SUCCESS = 'SUCCESS';

    public static function values(): array
    {
        return array_map(fn (StatusEnum $status) => $status->value, self::cases());
    }
}
