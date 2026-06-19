<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

final class AIResponse
{
    /** @param list<AIToolCall> $toolCalls */
    public function __construct(
        private string $content,
        private array $raw = [],
        private ?array $structured = null,
        private string $provider = '',
        private string $model = '',
        private int $promptTokens = 0,
        private int $completionTokens = 0,
        private float $cost = 0.0,
        private array $metadata = [],
        private array $toolCalls = [],
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function getStructured(): ?array
    {
        return $this->structured;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @return list<AIToolCall> */
    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    public function hasToolCalls(): bool
    {
        return [] !== $this->toolCalls;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'structured' => $this->structured,
            'provider' => $this->provider,
            'model' => $this->model,
            'tokens' => [
                'prompt' => $this->promptTokens,
                'completion' => $this->completionTokens,
                'total' => $this->getTotalTokens(),
            ],
            'cost' => $this->cost,
            'metadata' => $this->metadata,
            'toolCalls' => array_map(
                static fn (AIToolCall $toolCall): array => $toolCall->toArray(),
                $this->toolCalls,
            ),
        ];
    }
}
