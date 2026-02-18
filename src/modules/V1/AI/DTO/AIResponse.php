<?php

namespace Modules\V1\AI\DTO;

class AIResponse
{
    protected string $content;
    protected array $raw;
    protected ?array $structured;
    protected string $provider;
    protected string $model;
    protected int $promptTokens;
    protected int $completionTokens;
    protected float $cost;
    protected array $metadata;

    public function __construct(
        string $content,
        array $raw = [],
        ?array $structured = null,
        string $provider = '',
        string $model = '',
        int $promptTokens = 0,
        int $completionTokens = 0,
        float $cost = 0.0,
        array $metadata = []
    ) {
        $this->content = $content;
        $this->raw = $raw;
        $this->structured = $structured;
        $this->provider = $provider;
        $this->model = $model;
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->cost = $cost;
        $this->metadata = $metadata;
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
        ];
    }
}
