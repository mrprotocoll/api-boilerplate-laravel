<?php

declare(strict_types=1);

namespace Shared\Enums;

use Shared\Helpers\StringHelper;

enum RoleEnum: int
{
    case SUPER_ADMIN = 1;
    case ADMIN = 2;
    case USER = 3;

    public static function names(): array
    {
        return array_map(fn (RoleEnum $roles) => StringHelper::toTitleCase($roles->name), self::cases());
    }
}
