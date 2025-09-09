<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Enums;

enum LogEventEnum: string {
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
    case EXPORT = 'export';
    case RESTORE = 'restore';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
}
