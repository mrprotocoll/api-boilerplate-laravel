<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Illuminate\Support\Facades\Log;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\DTO\AIResponse;

abstract class BaseAIService implements AIServiceInterface
{
    protected string $model;
    protected array $config;
    protected float $temperature = 0.7;
    protected int $maxTokens = 2000;
    protected bool $logRequests = true;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->model = $config['model'] ?? $this->getDefaultModel();
        $this->temperature = (float) ($config['temperature'] ?? $this->temperature);
        $this->maxTokens = (int) ($config['max_tokens'] ?? $this->maxTokens);
        $this->logRequests = (bool) ($config['log_requests'] ?? config('ai.logging.enabled', true));
    }

    abstract protected function getDefaultModel(): string;

    /** @param array<string, mixed> $data */
    abstract protected function makeRequest(string $endpoint, array $data, ?int $timeout = null): array;

    public function complete(string $prompt, array $options = []): AIResponse
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    public function chatWithTools(array $messages, array $tools, array $options = []): AIResponse
    {
        return $this->chat($messages, array_merge($options, ['ignored_tool_count' => count($tools)]));
    }

    public function streamChatWithTools(array $messages, array $tools, array $options, callable $onDelta): AIResponse
    {
        $response = $this->chatWithTools($messages, $tools, array_merge($options, ['streamed' => true]));

        if ('' !== $response->getContent()) {
            $onDelta($response->getContent());
        }

        return $response;
    }

    public function structuredOutput(string $prompt, array $schema, array $options = []): AIResponse
    {
        $schemaJson = json_encode($schema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $response = $this->chat([
            [
                'role' => 'user',
                'content' => $prompt . "\n\nReturn only valid JSON matching this schema:\n{$schemaJson}",
            ],
        ], $options);

        return new AIResponse(
            content: $response->getContent(),
            raw: $response->getRaw(),
            structured: $this->extractJSON($response->getContent()),
            provider: $response->getProvider(),
            model: $response->getModel(),
            promptTokens: $response->getPromptTokens(),
            completionTokens: $response->getCompletionTokens(),
            cost: $response->getCost(),
            metadata: array_merge($response->getMetadata(), ['schema' => $schema]),
            toolCalls: $response->getToolCalls(),
        );
    }

    public function analyzeSentiment(string $text, array $options = []): AIResponse
    {
        return $this->structuredOutput(
            "Analyze the sentiment of this text and return sentiment, score, and confidence:\n\n{$text}",
            [
                'type' => 'object',
                'properties' => [
                    'sentiment' => ['type' => 'string', 'enum' => ['positive', 'negative', 'neutral']],
                    'score' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                ],
                'required' => ['sentiment', 'score', 'confidence'],
                'additionalProperties' => false,
            ],
            $options,
        );
    }

    public function classify(string $text, array $categories, array $options = []): AIResponse
    {
        return $this->structuredOutput(
            'Classify this text into one of these categories: ' . implode(', ', $categories) . "\n\nText: {$text}",
            [
                'type' => 'object',
                'properties' => [
                    'category' => ['type' => 'string', 'enum' => $categories],
                    'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                ],
                'required' => ['category', 'confidence'],
                'additionalProperties' => false,
            ],
            $options,
        );
    }

    public function extractEntities(string $text, array $options = []): AIResponse
    {
        return $this->structuredOutput(
            "Extract named entities from this text:\n\n{$text}",
            [
                'type' => 'object',
                'properties' => [
                    'entities' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'text' => ['type' => 'string'],
                                'type' => ['type' => 'string'],
                                'confidence' => ['type' => 'number'],
                            ],
                            'required' => ['text', 'type', 'confidence'],
                        ],
                    ],
                ],
                'required' => ['entities'],
                'additionalProperties' => false,
            ],
            $options,
        );
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function setMaxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function supportsTools(): bool
    {
        return false;
    }

    public function supportsStreaming(): bool
    {
        return false;
    }

    public function supportsStructuredOutput(): bool
    {
        return true;
    }

    protected function logRequest(string $prompt, array $options, AIResponse $response): void
    {
        if ( ! $this->logRequests) {
            return;
        }

        Log::channel((string) config('ai.logging.channel', 'ai'))->info('AI request completed', [
            'provider' => $this->getProvider(),
            'model' => $this->model,
            'prompt_length' => mb_strlen($prompt),
            'tokens' => [
                'prompt' => $response->getPromptTokens(),
                'completion' => $response->getCompletionTokens(),
                'total' => $response->getTotalTokens(),
            ],
            'cost' => $response->getCost(),
            'options' => $options,
        ]);
    }

    /** @return array<string, mixed>|null */
    protected function extractJSON(string $content): ?array
    {
        // Try to find JSON in markdown code blocks first
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return is_array($json) ? $json : null;
            }
        }

        // Try to find JSON without code blocks
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (JSON_ERROR_NONE === json_last_error()) {
                return is_array($json) ? $json : null;
            }
        }

        // Try to decode the entire content
        $json = json_decode($content, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return is_array($json) ? $json : null;
        }

        return null;
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Override in specific providers with actual pricing
        return 0.0;
    }
}
