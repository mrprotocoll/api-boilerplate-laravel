<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIRuntimeResult
{
    /**
     * @param list<array<string, mixed>> $toolAudits
     * @param array<string, mixed>|null $attachment
     * @param list<string> $suggestions
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $reply,
        public ?array $attachment,
        public array $suggestions,
        public ?AIResponse $response,
        public array $toolAudits,
        public array $metadata,
    ) {
    }
}
