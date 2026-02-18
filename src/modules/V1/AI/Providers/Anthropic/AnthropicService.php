<?php

namespace Modules\V1\AI\Providers\Anthropic;

use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\Services\BaseAIService;

class AnthropicService extends BaseAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.anthropic.com/v1';
    protected string $version = '2023-06-01';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = $config['api_key'] ?? config('services.anthropic.key');
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->version = $config['version'] ?? $this->version;
    }

    protected function getDefaultModel(): string
    {
        return 'claude-3-5-sonnet-20241022';
    }

    public function getProvider(): string
    {
        return 'anthropic';
    }

    public function complete(string $prompt, array $options = []): AIResponse
    {
        $response = $this->makeRequest('messages', [
            'model' => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $content = $response['content'][0]['text'] ?? '';
        $promptTokens = $response['usage']['input_tokens'] ?? 0;
        $completionTokens = $response['usage']['output_tokens'] ?? 0;

        $aiResponse = new AIResponse(
            content: $content,
            raw: $response,
            structured: null,
            provider: $this->getProvider(),
            model: $response['model'] ?? $this->model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            metadata: $options
        );

        $this->logRequest($prompt, $options, $aiResponse);

        return $aiResponse;
    }

    public function structuredOutput(string $prompt, array $schema, array $options = []): AIResponse
    {
        // Add instruction to output JSON
        $enhancedPrompt = $prompt . "\n\nRespond ONLY with valid JSON matching this schema. Do not include any explanation or markdown formatting.";

        $response = $this->makeRequest('messages', [
            'model' => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'messages' => [
                ['role' => 'user', 'content' => $enhancedPrompt]
            ]
        ]);

        $content = $response['content'][0]['text'] ?? '';
        $structured = $this->extractJSON($content);
        $promptTokens = $response['usage']['input_tokens'] ?? 0;
        $completionTokens = $response['usage']['output_tokens'] ?? 0;

        $aiResponse = new AIResponse(
            content: $content,
            raw: $response,
            structured: $structured,
            provider: $this->getProvider(),
            model: $response['model'] ?? $this->model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            metadata: array_merge($options, ['schema' => $schema])
        );

        $this->logRequest($prompt, $options, $aiResponse);

        return $aiResponse;
    }

    public function analyzeSentiment(string $text, array $options = []): AIResponse
    {
        $prompt = "Analyze the sentiment of the following text and return ONLY a JSON object with 'sentiment' (positive/negative/neutral), 'score' (0-1), and 'confidence' (0-1). No explanation needed:\n\n{$text}";

        return $this->structuredOutput($prompt, [
            'type' => 'object',
            'properties' => [
                'sentiment' => ['type' => 'string'],
                'score' => ['type' => 'number'],
                'confidence' => ['type' => 'number']
            ]
        ], $options);
    }

    public function classify(string $text, array $categories, array $options = []): AIResponse
    {
        $categoriesList = implode(', ', $categories);
        $prompt = "Classify the following text into one of these categories: {$categoriesList}\n\nText: {$text}\n\nReturn ONLY a JSON object with 'category' and 'confidence' (0-1). No explanation needed.";

        return $this->structuredOutput($prompt, [
            'type' => 'object',
            'properties' => [
                'category' => ['type' => 'string'],
                'confidence' => ['type' => 'number']
            ]
        ], $options);
    }

    public function extractEntities(string $text, array $options = []): AIResponse
    {
        $prompt = "Extract named entities from the following text. Return ONLY a JSON array of entities with 'text', 'type' (person/organization/location/other), and 'confidence' (0-1). No explanation needed:\n\n{$text}";

        return $this->structuredOutput($prompt, [
            'type' => 'object',
            'properties' => [
                'entities' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'confidence' => ['type' => 'number']
                        ]
                    ]
                ]
            ]
        ], $options);
    }

    protected function makeRequest(string $endpoint, array $data): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->version,
            'content-type' => 'application/json',
        ])->post("{$this->baseUrl}/{$endpoint}", $data);

        if (!$response->successful()) {
            throw new \Exception("Anthropic API request failed: " . $response->body());
        }

        return $response->json();
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Pricing for Claude 3.5 Sonnet (as of 2024)
        $promptCostPer1M = 3.00;
        $completionCostPer1M = 15.00;

        $promptCost = ($promptTokens / 1_000_000) * $promptCostPer1M;
        $completionCost = ($completionTokens / 1_000_000) * $completionCostPer1M;

        return $promptCost + $completionCost;
    }
}
