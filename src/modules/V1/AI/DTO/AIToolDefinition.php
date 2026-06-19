<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final readonly class AIToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param list<string> $abilities
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema = [],
        public bool $requiresAuth = true,
        public bool $mutatesState = false,
        public bool $requiresConfirmation = false,
        public array $abilities = [],
        public int $maxResultSize = 12000,
    ) {
    }

    /** @return array<string, mixed> */
    public function toNativeTool(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->inputSchema['properties'] ?? $this->inputSchema,
                    'required' => $this->inputSchema['required'] ?? [],
                    'additionalProperties' => $this->inputSchema['additionalProperties'] ?? false,
                ],
            ],
        ];
    }
}
