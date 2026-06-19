<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Enums;

enum LogChannelEnums: string
{
    case AI = 'ai';
    case CONTENT_CALENDAR = 'content_calendar';
    case PAYMENTS = 'payments';

}
