<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIToolCall
{
    /** @param array<string, mixed> $arguments */
    public function __construct(
        public string $name,
        public array $arguments = [],
        public ?string $id = null,
    ) {
    }

    /** @return array{name: string, arguments: array<string, mixed>, id: string|null} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
            'id' => $this->id,
        ];
    }
}
