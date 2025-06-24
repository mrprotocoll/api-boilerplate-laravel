<?php

declare(strict_types=1);

namespace Modules\V1\User\Enums;

use Shared\Helpers\StringHelper;

enum RoleEnum: int
{
    case SUPER_ADMIN = 1;
    case ADMIN = 2;

    public static function names(): array
    {
        return array_map(fn (RoleEnum $roles) => strtolower($roles->name), self::cases());
    }

    public function name(): string
    {
        // Get the position of the case within the enum
        return StringHelper::toTitleCase($this->name);
    }
}
