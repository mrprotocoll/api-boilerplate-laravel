<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIToolValidationResult
{
    /** @param list<string> $errors */
    public function __construct(
        public bool $valid,
        public array $errors = [],
    ) {
    }
}
