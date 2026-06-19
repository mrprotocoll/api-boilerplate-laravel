<?php

declare(strict_types=1);

namespace Modules\V1\AI\Contracts;

use Modules\V1\AI\DTO\AIResponse;

interface AIServiceInterface
{
    /** @param array<string, mixed> $options */
    public function complete(string $prompt, array $options = []): AIResponse;

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed> $options
     */
    public function chat(array $messages, array $options = []): AIResponse;

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param list<array<string, mixed>> $tools
     * @param array<string, mixed> $options
     */
    public function chatWithTools(array $messages, array $tools, array $options = []): AIResponse;

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param list<array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @param callable(string): void $onDelta
     */
    public function streamChatWithTools(array $messages, array $tools, array $options, callable $onDelta): AIResponse;

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $options
     */
    public function structuredOutput(string $prompt, array $schema, array $options = []): AIResponse;

    /** @param array<string, mixed> $options */
    public function analyzeSentiment(string $text, array $options = []): AIResponse;

    /**
     * @param list<string> $categories
     * @param array<string, mixed> $options
     */
    public function classify(string $text, array $categories, array $options = []): AIResponse;

    /** @param array<string, mixed> $options */
    public function extractEntities(string $text, array $options = []): AIResponse;

    public function getProvider(): string;

    public function getModel(): string;

    public function setModel(string $model): self;

    public function supportsTools(): bool;

    public function supportsStreaming(): bool;

    public function supportsStructuredOutput(): bool;
}
