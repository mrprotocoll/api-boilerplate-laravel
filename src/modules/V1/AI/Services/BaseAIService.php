<?php

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
        $this->temperature = $config['temperature'] ?? $this->temperature;
        $this->maxTokens = $config['max_tokens'] ?? $this->maxTokens;
        $this->logRequests = $config['log_requests'] ?? $this->logRequests;
    }

    abstract protected function getDefaultModel(): string;

    abstract protected function makeRequest(string $endpoint, array $data): array;

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

    protected function logRequest(string $prompt, array $options, AIResponse $response): void
    {
        if (!$this->logRequests) {
            return;
        }

        Log::channel('ai')->info('AI Request', [
            'provider' => $this->getProvider(),
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
            'tokens' => [
                'prompt' => $response->getPromptTokens(),
                'completion' => $response->getCompletionTokens(),
                'total' => $response->getTotalTokens(),
            ],
            'cost' => $response->getCost(),
            'options' => $options,
        ]);
    }

    protected function extractJSON(string $content): ?array
    {
        // Try to find JSON in markdown code blocks first
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try to find JSON without code blocks
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Try to decode the entire content
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Override in specific providers with actual pricing
        return 0.0;
    }
}
