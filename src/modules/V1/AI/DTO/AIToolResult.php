<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIToolResult
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $display
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $status,
        public string $kind,
        public string $summary,
        public array $data = [],
        public array $display = ['type' => 'card', 'mode' => 'auto'],
        public array $metadata = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'kind' => $this->kind,
            'summary' => $this->summary,
            'data' => $this->data,
            'display' => $this->display,
            'metadata' => $this->metadata,
        ];
    }
}
