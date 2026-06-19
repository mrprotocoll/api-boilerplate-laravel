<?php

declare(strict_types=1);

namespace Shared\Enums;

enum StatusEnum: string
{
    public const PENDING = 'PENDING';

    public const APPROVED = 'APPROVED';

    public const FAILED = 'FAILED';
    public const ACTIVE = 'ACTIVE';

    public const SUCCESS = 'SUCCESS';

    public static function values(): array
    {
        return array_map(fn (StatusEnum $status) => $status->value, self::cases());
    }
}
