<?php

declare(strict_types=1);

namespace Modules\V1\AI\Controllers;

use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\AI\DTO\AIActorContext;

final class AdminAIController extends BaseAIController
{
    protected function authenticatedActor(): AIActorContext
    {
        $admin = Auth::user();
        if ( ! $admin instanceof Admin) {
            throw new Exception('Unauthenticated');
        }

        return AIActorContext::forAdmin($admin);
    }
}
