<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIUsageLimitResult
{
    public function __construct(
        public bool $allowed,
        public ?string $message = null,
        public ?string $reason = null,
    ) {
    }
}
