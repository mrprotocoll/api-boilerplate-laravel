<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\V1\Admin\Enums\NotificationTypeEnum;

final class AdminGeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected NotificationTypeEnum $pointsEarned
    ) {}
}
