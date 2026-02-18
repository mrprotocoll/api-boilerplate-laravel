<?php

namespace Modules\V1\AI\Providers\OpenAI;

use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\Services\BaseAIService;

class OpenAIService extends BaseAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = $config['api_key'] ?? config('services.openai.key');
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
    }

    protected function getDefaultModel(): string
    {
        return 'gpt-4o-mini';
    }

    public function getProvider(): string
    {
        return 'openai';
    }

    public function complete(string $prompt, array $options = []): AIResponse
    {
        $response = $this->makeRequest('chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';
        $promptTokens = $response['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $response['usage']['completion_tokens'] ?? 0;

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
        $response = $this->makeRequest('chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schema['name'] ?? 'response',
                    'strict' => true,
                    'schema' => $schema
                ]
            ],
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';
        $structured = $this->extractJSON($content);
        $promptTokens = $response['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $response['usage']['completion_tokens'] ?? 0;

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
        $prompt = "Analyze the sentiment of the following text and return a JSON object with 'sentiment' (positive/negative/neutral), 'score' (0-1), and 'confidence' (0-1):\n\n{$text}";

        return $this->structuredOutput($prompt, [
            'name' => 'sentiment_analysis',
            'type' => 'object',
            'properties' => [
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['positive', 'negative', 'neutral']
                ],
                'score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1
                ]
            ],
            'required' => ['sentiment', 'score', 'confidence'],
            'additionalProperties' => false
        ], $options);
    }

    public function classify(string $text, array $categories, array $options = []): AIResponse
    {
        $categoriesList = implode(', ', $categories);
        $prompt = "Classify the following text into one of these categories: {$categoriesList}\n\nText: {$text}\n\nReturn a JSON object with 'category' and 'confidence' (0-1).";

        return $this->structuredOutput($prompt, [
            'name' => 'classification',
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => $categories
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1
                ]
            ],
            'required' => ['category', 'confidence'],
            'additionalProperties' => false
        ], $options);
    }

    public function extractEntities(string $text, array $options = []): AIResponse
    {
        $prompt = "Extract named entities from the following text. Return a JSON array of entities with 'text', 'type' (person/organization/location/other), and 'confidence' (0-1):\n\n{$text}";

        return $this->structuredOutput($prompt, [
            'name' => 'entity_extraction',
            'type' => 'object',
            'properties' => [
                'entities' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => ['person', 'organization', 'location', 'other']
                            ],
                            'confidence' => [
                                'type' => 'number',
                                'minimum' => 0,
                                'maximum' => 1
                            ]
                        ],
                        'required' => ['text', 'type', 'confidence'],
                        'additionalProperties' => false
                    ]
                ]
            ],
            'required' => ['entities'],
            'additionalProperties' => false
        ], $options);
    }

    protected function makeRequest(string $endpoint, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/{$endpoint}", $data);

        if (!$response->successful()) {
            throw new \Exception("OpenAI API request failed: " . $response->body());
        }

        return $response->json();
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Pricing for GPT-4o-mini (as of 2024)
        $promptCostPer1M = 0.150;
        $completionCostPer1M = 0.600;

        $promptCost = ($promptTokens / 1_000_000) * $promptCostPer1M;
        $completionCost = ($completionTokens / 1_000_000) * $completionCostPer1M;

        return $promptCost + $completionCost;
    }
}
