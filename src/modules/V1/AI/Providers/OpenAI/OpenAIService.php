<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers\OpenAI;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\DTO\AIToolCall;
use Modules\V1\AI\Services\BaseAIService;

final class OpenAIService extends BaseAIService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.openai.com/v1';

    protected int $requestTimeout = 120;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->apiKey = (string) ($config['api_key'] ?? config('services.openai.key', ''));
        $this->baseUrl = (string) ($config['base_url'] ?? $this->baseUrl);
        $this->requestTimeout = (int) ($config['request_timeout'] ?? $this->requestTimeout);
    }

    protected function getDefaultModel(): string
    {
        return 'gpt-4.1-mini';
    }

    public function getProvider(): string
    {
        return 'openai';
    }

    public function supportsTools(): bool
    {
        return true;
    }

    public function supportsStreaming(): bool
    {
        return false;
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $response = $this->makeRequest('chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ], $this->timeoutFromOptions($options));

        return $this->responseFromChatCompletion($response, $options, []);
    }

    public function chatWithTools(array $messages, array $tools, array $options = []): AIResponse
    {
        if ([] === $tools) {
            return $this->chat($messages, $options);
        }

        $response = $this->makeRequest('chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'tools' => $this->toOpenAITools($tools),
            'tool_choice' => $options['tool_choice'] ?? 'auto',
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ], $this->timeoutFromOptions($options));

        $message = $response['choices'][0]['message'] ?? [];

        return $this->responseFromChatCompletion(
            $response,
            array_merge($options, ['native_tools' => true]),
            is_array($message) ? $this->extractToolCalls($message) : [],
        );
    }

    protected function makeRequest(string $endpoint, array $data, ?int $timeout = null): array
    {
        if ('' === $this->apiKey) {
            throw new Exception('OpenAI API key is not configured.');
        }

        $response = Http::timeout($timeout ?? $this->requestTimeout)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/{$endpoint}", $data);

        if ( ! $response->successful()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        $json = $response->json();
        if ( ! is_array($json)) {
            throw new Exception('OpenAI API returned an invalid JSON response.');
        }

        return $json;
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        $promptCostPer1M = 0.15;
        $completionCostPer1M = 0.60;

        return (($promptTokens / 1_000_000) * $promptCostPer1M)
            + (($completionTokens / 1_000_000) * $completionCostPer1M);
    }

    /** @param array<string, mixed> $options */
    private function timeoutFromOptions(array $options): int
    {
        return isset($options['request_timeout']) && is_numeric($options['request_timeout'])
            ? (int) $options['request_timeout']
            : $this->requestTimeout;
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $options
     * @param list<AIToolCall> $toolCalls
     */
    private function responseFromChatCompletion(array $response, array $options, array $toolCalls): AIResponse
    {
        $message = $response['choices'][0]['message'] ?? [];
        $content = is_array($message) && isset($message['content']) && is_string($message['content'])
            ? $message['content']
            : '';
        $promptTokens = (int) ($response['usage']['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($response['usage']['completion_tokens'] ?? 0);

        $aiResponse = new AIResponse(
            content: $content,
            raw: $response,
            structured: null,
            provider: $this->getProvider(),
            model: (string) ($response['model'] ?? $this->model),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            cost: $this->calculateCost($promptTokens, $completionTokens),
            metadata: $options,
            toolCalls: $toolCalls,
        );

        $this->logRequest(json_encode($response['choices'][0]['message'] ?? [], JSON_THROW_ON_ERROR), $options, $aiResponse);

        return $aiResponse;
    }

    /**
     * @param list<array<string, mixed>> $tools
     * @return list<array<string, mixed>>
     */
    private function toOpenAITools(array $tools): array
    {
        return array_map(static function (array $tool): array {
            if (($tool['type'] ?? null) === 'function') {
                return $tool;
            }

            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['parameters'] ?? [
                        'type' => 'object',
                        'properties' => $tool['input_schema'] ?? [],
                    ],
                ],
            ];
        }, $tools);
    }

    /**
     * @param array<string, mixed> $message
     * @return list<AIToolCall>
     */
    private function extractToolCalls(array $message): array
    {
        $toolCalls = [];
        $nativeCalls = $message['tool_calls'] ?? [];
        if ( ! is_array($nativeCalls)) {
            return [];
        }

        foreach ($nativeCalls as $nativeCall) {
            if ( ! is_array($nativeCall)) {
                continue;
            }

            $function = $nativeCall['function'] ?? [];
            if ( ! is_array($function) || ! isset($function['name'])) {
                continue;
            }

            $arguments = [];
            if (isset($function['arguments']) && is_string($function['arguments']) && '' !== $function['arguments']) {
                $decoded = json_decode($function['arguments'], true);
                $arguments = is_array($decoded) ? $decoded : [];
            }

            $toolCalls[] = new AIToolCall(
                name: (string) $function['name'],
                arguments: $arguments,
                id: isset($nativeCall['id']) ? (string) $nativeCall['id'] : null,
            );
        }

        return $toolCalls;
    }
}
