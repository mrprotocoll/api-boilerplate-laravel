<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers\Gemini;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\Services\BaseAIService;

final class GeminiService extends BaseAIService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected int $requestTimeout = 120;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = (string) ($config['api_key'] ?? config('services.gemini.key', ''));
        $this->baseUrl = (string) ($config['base_url'] ?? $this->baseUrl);
        $this->requestTimeout = (int) ($config['request_timeout'] ?? $this->requestTimeout);
    }

    protected function getDefaultModel(): string
    {
        return 'gemini-1.5-flash';
    }

    public function getProvider(): string
    {
        return 'gemini';
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $model = (string) ($options['model'] ?? $this->model);
        $prompt = collect($messages)
            ->map(static fn (array $message): string => mb_strtoupper((string) ($message['role'] ?? 'user')) . ': ' . (string) ($message['content'] ?? ''))
            ->implode("\n\n");

        $response = $this->makeRequest("models/{$model}:generateContent", [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->temperature,
                'maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens,
            ],
        ], $this->timeoutFromOptions($options));

        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $promptTokens = (int) ($response['usageMetadata']['promptTokenCount'] ?? 0);
        $completionTokens = (int) ($response['usageMetadata']['candidatesTokenCount'] ?? 0);

        $aiResponse = new AIResponse(
            content: is_string($content) ? $content : '',
            raw: $response,
            provider: $this->getProvider(),
            model: $model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            metadata: $options,
        );

        $this->logRequest($prompt, $options, $aiResponse);

        return $aiResponse;
    }

    protected function makeRequest(string $endpoint, array $data, ?int $timeout = null): array
    {
        if ('' === $this->apiKey) {
            throw new Exception('Gemini API key is not configured.');
        }

        $response = Http::timeout($timeout ?? $this->requestTimeout)
            ->post("{$this->baseUrl}/{$endpoint}?key={$this->apiKey}", $data);

        if ( ! $response->successful()) {
            throw new Exception('Gemini API request failed: ' . $response->body());
        }

        $json = $response->json();
        if ( ! is_array($json)) {
            throw new Exception('Gemini API returned an invalid JSON response.');
        }

        return $json;
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        return (($promptTokens / 1_000_000) * 0.075) + (($completionTokens / 1_000_000) * 0.30);
    }

    private function timeoutFromOptions(array $options): int
    {
        return isset($options['request_timeout']) && is_numeric($options['request_timeout'])
            ? (int) $options['request_timeout']
            : $this->requestTimeout;
    }
}
