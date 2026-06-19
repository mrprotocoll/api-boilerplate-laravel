<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers\Anthropic;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\Services\BaseAIService;

final class AnthropicService extends BaseAIService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.anthropic.com/v1';

    protected string $version = '2023-06-01';

    protected int $requestTimeout = 120;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = (string) ($config['api_key'] ?? config('services.anthropic.key', ''));
        $this->baseUrl = (string) ($config['base_url'] ?? $this->baseUrl);
        $this->version = (string) ($config['version'] ?? $this->version);
        $this->requestTimeout = (int) ($config['request_timeout'] ?? $this->requestTimeout);
    }

    protected function getDefaultModel(): string
    {
        return 'claude-3-5-sonnet-20241022';
    }

    public function getProvider(): string
    {
        return 'anthropic';
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $system = collect($messages)->where('role', 'system')->pluck('content')->implode("\n\n");
        $chatMessages = collect($messages)
            ->reject(static fn (array $message): bool => ($message['role'] ?? null) === 'system')
            ->map(static fn (array $message): array => [
                'role' => ($message['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($message['content'] ?? ''),
            ])
            ->values()
            ->all();

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'messages' => $chatMessages,
        ];

        if ('' !== $system) {
            $payload['system'] = $system;
        }

        $response = $this->makeRequest('messages', $payload, $this->timeoutFromOptions($options));
        $content = $response['content'][0]['text'] ?? '';
        $promptTokens = (int) ($response['usage']['input_tokens'] ?? 0);
        $completionTokens = (int) ($response['usage']['output_tokens'] ?? 0);

        $aiResponse = new AIResponse(
            content: is_string($content) ? $content : '',
            raw: $response,
            provider: $this->getProvider(),
            model: (string) ($response['model'] ?? $this->model),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            metadata: $options,
        );

        $this->logRequest(json_encode($messages, JSON_THROW_ON_ERROR), $options, $aiResponse);

        return $aiResponse;
    }

    protected function makeRequest(string $endpoint, array $data, ?int $timeout = null): array
    {
        if ('' === $this->apiKey) {
            throw new Exception('Anthropic API key is not configured.');
        }

        $response = Http::timeout($timeout ?? $this->requestTimeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
                'content-type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/{$endpoint}", $data);

        if ( ! $response->successful()) {
            throw new Exception('Anthropic API request failed: ' . $response->body());
        }

        $json = $response->json();
        if ( ! is_array($json)) {
            throw new Exception('Anthropic API returned an invalid JSON response.');
        }

        return $json;
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        return (($promptTokens / 1_000_000) * 3.00) + (($completionTokens / 1_000_000) * 15.00);
    }

    private function timeoutFromOptions(array $options): int
    {
        return isset($options['request_timeout']) && is_numeric($options['request_timeout'])
            ? (int) $options['request_timeout']
            : $this->requestTimeout;
    }
}
