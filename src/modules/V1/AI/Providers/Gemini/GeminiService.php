<?php

namespace Modules\V1\AI\Providers\Gemini;

use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\Services\BaseAIService;

class GeminiService extends BaseAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = $config['api_key'] ?? config('services.gemini.key');
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
    }

    protected function getDefaultModel(): string
    {
        return 'gemini-1.5-flash';
    }

    public function getProvider(): string
    {
        return 'gemini';
    }

    public function complete(string $prompt, array $options = []): AIResponse
    {
        $model = $options['model'] ?? $this->model;

        $response = $this->makeRequest("models/{$model}:generateContent", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->temperature,
                'maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens,
            ]
        ]);

        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $promptTokens = $response['usageMetadata']['promptTokenCount'] ?? 0;
        $completionTokens = $response['usageMetadata']['candidatesTokenCount'] ?? 0;

        $aiResponse = new AIResponse(
            content: $content,
            raw: $response,
            structured: null,
            provider: $this->getProvider(),
            model: $model,
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
        $model = $options['model'] ?? $this->model;

        // Add instruction to output JSON
        $enhancedPrompt = $prompt . "\n\nRespond ONLY with valid JSON. Do not include any explanation or markdown formatting.";

        $response = $this->makeRequest("models/{$model}:generateContent", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $enhancedPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->temperature,
                'maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens,
                'responseMimeType' => 'application/json',
            ]
        ]);

        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $structured = $this->extractJSON($content);
        $promptTokens = $response['usageMetadata']['promptTokenCount'] ?? 0;
        $completionTokens = $response['usageMetadata']['candidatesTokenCount'] ?? 0;

        $aiResponse = new AIResponse(
            content: $content,
            raw: $response,
            structured: $structured,
            provider: $this->getProvider(),
            model: $model,
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
        $prompt = "Analyze the sentiment of the following text and return ONLY a JSON object with 'sentiment' (positive/negative/neutral), 'score' (0-1), and 'confidence' (0-1):\n\n{$text}";

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
        $prompt = "Classify the following text into one of these categories: {$categoriesList}\n\nText: {$text}\n\nReturn ONLY a JSON object with 'category' and 'confidence' (0-1).";

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
        $prompt = "Extract named entities from the following text. Return ONLY a JSON array of entities with 'text', 'type' (person/organization/location/other), and 'confidence' (0-1):\n\n{$text}";

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
        $response = Http::post(
            "{$this->baseUrl}/{$endpoint}?key={$this->apiKey}",
            $data
        );

        if (!$response->successful()) {
            throw new \Exception("Gemini API request failed: " . $response->body());
        }

        return $response->json();
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Pricing for Gemini 1.5 Flash (as of 2024)
        // Free tier available, paid pricing varies
        $promptCostPer1M = 0.075;
        $completionCostPer1M = 0.30;

        $promptCost = ($promptTokens / 1_000_000) * $promptCostPer1M;
        $completionCost = ($completionTokens / 1_000_000) * $completionCostPer1M;

        return $promptCost + $completionCost;
    }
}
