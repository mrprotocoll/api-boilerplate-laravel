<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\DTO\AIResponse;
use Modules\V1\AI\DTO\AIRuntimeResult;
use Modules\V1\AI\DTO\AIToolCall;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\AI\Models\AIMessage;
use Throwable;

final class AIRuntime
{
    public function __construct(
        private readonly AIContextBuilder $contextBuilder,
        private readonly AIToolRegistry $toolRegistry,
        private readonly AIToolExecutor $toolExecutor,
        private readonly AIResponseSynthesizer $responseSynthesizer,
    ) {
    }

    /**
     * @param list<AIMessage> $history
     * @param array<string, mixed> $input
     * @param callable(string): void|null $onDelta
     * @param callable(string): void|null $onToolStart
     * @param callable(string, array<string, mixed>): void|null $onToolDone
     */
    public function run(
        string $message,
        array $history,
        ?AIActorContext $actor,
        array $input = [],
        ?callable $onDelta = null,
        ?callable $onToolStart = null,
        ?callable $onToolDone = null,
    ): AIRuntimeResult {
        $correlationId = (string) Str::uuid();
        $startedAt = microtime(true);
        $context = $this->contextBuilder->build($actor, $input);
        $messages = $this->buildMessages($history, $message, $context);
        $maxSteps = max(0, (int) config('ai.assistant.runtime.max_tool_steps', 3));
        $maxToolCalls = max(1, (int) config('ai.assistant.runtime.max_tool_calls_per_request', 5));
        $toolCallCount = 0;
        $toolAudits = [];
        $toolResults = [];
        $lastResponse = null;
        $failureType = null;

        /** @var AIServiceInterface $aiService */
        $aiService = app(AIServiceInterface::class);
        $providerOptions = [
            'temperature' => 0.2,
            'max_tokens' => 2000,
            'request_timeout' => (int) config('ai.assistant.runtime.request_timeout', 30),
        ];

        try {
            for ($step = 0; $step <= $maxSteps; $step++) {
                $lastResponse = $this->callProvider($aiService, $messages, $providerOptions, $onDelta, $actor);

                if ( ! $lastResponse->hasToolCalls()) {
                    break;
                }

                if ($step >= $maxSteps) {
                    $failureType = 'max_steps_exceeded';
                    break;
                }

                $messages[] = $this->assistantToolCallMessage($lastResponse);

                foreach ($lastResponse->getToolCalls() as $toolCall) {
                    if ($toolCallCount >= $maxToolCalls) {
                        $failureType = 'max_tool_calls_exceeded';
                        break 2;
                    }

                    $toolCallCount++;
                    if (is_callable($onToolStart)) {
                        $onToolStart($toolCall->name);
                    }

                    $execution = $this->toolExecutor->execute($toolCall, $actor);
                    $toolResult = $execution['result'];
                    $toolAudits[] = $execution['audit'];
                    $toolResults[] = $toolResult->toArray();
                    $messages[] = $this->toolResultMessage($toolCall, $toolResult);

                    if (is_callable($onToolDone)) {
                        $onToolDone($toolCall->name, $execution['audit']);
                    }
                }
            }

            $synthesisResponse = null === $failureType && [] !== $toolResults
                ? $this->safeSynthesis($aiService, $messages, $toolResults, $providerOptions, $onDelta)
                : null;
            $finalResponse = $synthesisResponse ?? $lastResponse;
            $reply = null !== $failureType
                ? $this->failureReply($failureType)
                : $this->replyFromResponse($finalResponse, $toolResults);
            $attachment = null !== $failureType
                ? $this->failureAttachment($failureType)
                : $this->attachmentFromToolResults($toolResults);

            return new AIRuntimeResult(
                reply: mb_substr($reply, 0, 8000),
                attachment: $attachment,
                suggestions: [],
                response: $this->combineResponses($lastResponse, $synthesisResponse),
                toolAudits: $toolAudits,
                metadata: [
                    'correlation_id' => $correlationId,
                    'response_mode' => null !== $failureType
                        ? 'tool_limit_failure'
                        : ([] !== $toolResults ? 'tool_result_synthesis' : 'direct'),
                    'tool_steps' => count($toolResults),
                    'tool_call_count' => $toolCallCount,
                    'failure_type' => $failureType,
                    'runtime_duration_ms' => $this->durationMs($startedAt),
                ],
            );
        } catch (Throwable $throwable) {
            Log::channel((string) config('ai.logging.channel', 'ai'))->warning('AI runtime failed', [
                'correlation_id' => $correlationId,
                'error_class' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return new AIRuntimeResult(
                reply: "I'm having trouble responding right now. Please try again in a moment.",
                attachment: [
                    'type' => 'notice',
                    'kind' => 'error',
                    'summary' => 'The AI provider did not respond successfully.',
                ],
                suggestions: [],
                response: $lastResponse,
                toolAudits: $toolAudits,
                metadata: [
                    'correlation_id' => $correlationId,
                    'response_mode' => 'error',
                    'failure_type' => 'provider_unavailable',
                    'error_class' => $throwable::class,
                    'runtime_duration_ms' => $this->durationMs($startedAt),
                ],
            );
        }
    }

    /**
     * @param list<AIMessage> $history
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function buildMessages(array $history, string $message, array $context): array
    {
        $messages = [[
            'role' => 'system',
            'content' => $this->contextBuilder->systemPrompt($context),
        ]];

        foreach ($history as $historyMessage) {
            $messages[] = [
                'role' => $historyMessage->role,
                'content' => $historyMessage->content,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    /** @param array<int, array<string, mixed>> $messages */
    private function callProvider(AIServiceInterface $aiService, array $messages, array $providerOptions, ?callable $onDelta, ?AIActorContext $actor): AIResponse
    {
        $tools = $aiService->supportsTools() ? $this->toolRegistry->nativeToolDefinitions($actor) : [];

        if (is_callable($onDelta) && $aiService->supportsStreaming()) {
            return $aiService->streamChatWithTools($messages, $tools, $providerOptions, $onDelta);
        }

        return [] !== $tools
            ? $aiService->chatWithTools($messages, $tools, $providerOptions)
            : $aiService->chat($messages, $providerOptions);
    }

    /** @return array<string, mixed> */
    private function assistantToolCallMessage(AIResponse $response): array
    {
        $toolCalls = [];
        foreach ($response->getToolCalls() as $toolCall) {
            $toolCallId = $toolCall->id ?? 'call_' . $toolCall->name;
            $toolCalls[] = [
                'id' => $toolCallId,
                'type' => 'function',
                'function' => [
                    'name' => $toolCall->name,
                    'arguments' => json_encode($toolCall->arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                ],
            ];
        }

        return [
            'role' => 'assistant',
            'content' => $response->getContent(),
            'tool_calls' => $toolCalls,
        ];
    }

    /** @return array<string, mixed> */
    private function toolResultMessage(AIToolCall $toolCall, AIToolResult $toolResult): array
    {
        return [
            'role' => 'tool',
            'tool_call_id' => $toolCall->id ?? 'call_' . $toolCall->name,
            'content' => json_encode($toolResult->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ];
    }

    /** @param list<array<string, mixed>> $toolResults */
    private function safeSynthesis(AIServiceInterface $aiService, array $messages, array $toolResults, array $providerOptions, ?callable $onDelta): ?AIResponse
    {
        try {
            $response = $this->responseSynthesizer->synthesize($aiService, $messages, $toolResults, $providerOptions);
            if ($response instanceof AIResponse && is_callable($onDelta) && '' !== $response->getContent()) {
                $onDelta($response->getContent());
            }

            return $response;
        } catch (Throwable) {
            return null;
        }
    }

    /** @param list<array<string, mixed>> $toolResults */
    private function attachmentFromToolResults(array $toolResults): ?array
    {
        $latest = $toolResults[array_key_last($toolResults)] ?? null;
        if ( ! is_array($latest)) {
            return null;
        }

        $display = isset($latest['display']) && is_array($latest['display']) ? $latest['display'] : [];
        if (($display['mode'] ?? 'auto') === 'never') {
            return null;
        }

        return [
            'type' => $display['type'] ?? 'card',
            'kind' => $latest['kind'] ?? 'tool_result',
            'summary' => $latest['summary'] ?? null,
            'data' => $latest['data'] ?? [],
            'display' => $display,
        ];
    }

    private function combineResponses(?AIResponse $first, ?AIResponse $second): ?AIResponse
    {
        if (null === $first) {
            return $second;
        }

        if (null === $second) {
            return $first;
        }

        return new AIResponse(
            content: $second->getContent(),
            raw: ['initial' => $first->getRaw(), 'synthesis' => $second->getRaw()],
            structured: $second->getStructured(),
            provider: $second->getProvider(),
            model: $second->getModel(),
            promptTokens: $first->getPromptTokens() + $second->getPromptTokens(),
            completionTokens: $first->getCompletionTokens() + $second->getCompletionTokens(),
            cost: $first->getCost() + $second->getCost(),
            metadata: array_merge($first->getMetadata(), ['synthesis' => $second->getMetadata()]),
            toolCalls: $first->getToolCalls(),
        );
    }

    /** @param list<array<string, mixed>> $toolResults */
    private function replyFromResponse(?AIResponse $response, array $toolResults): string
    {
        if ($response instanceof AIResponse && '' !== trim($response->getContent())) {
            return trim($response->getContent());
        }

        return $this->responseSynthesizer->fallbackReply($toolResults);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function failureReply(string $failureType): string
    {
        return match ($failureType) {
            'max_steps_exceeded' => 'I could not complete that request because it required too many tool steps.',
            'max_tool_calls_exceeded' => 'I could not complete that request because it required too many tool calls.',
            default => "I'm having trouble completing that request right now.",
        };
    }

    /** @return array<string, mixed> */
    private function failureAttachment(string $failureType): array
    {
        return [
            'type' => 'notice',
            'kind' => $failureType,
            'summary' => $this->failureReply($failureType),
            'display' => ['type' => 'notice', 'mode' => 'always'],
        ];
    }
}
