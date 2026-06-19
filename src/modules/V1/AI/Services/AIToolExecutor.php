<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Modules\V1\AI\DTO\AIToolCall;
use Modules\V1\AI\DTO\AIToolResult;
use Modules\V1\User\Models\User;
use Throwable;

final class AIToolExecutor
{
    public function __construct(
        private readonly AIToolRegistry $toolRegistry,
        private readonly AIToolAuthorizer $toolAuthorizer,
    ) {
    }

    /** @return array{result: AIToolResult, audit: array<string, mixed>} */
    public function execute(AIToolCall $toolCall, ?User $user): array
    {
        $startedAt = microtime(true);
        $handler = $this->toolRegistry->handler($toolCall->name);
        if (null === $handler) {
            return $this->failure($toolCall, $startedAt, 'tool_not_found', 'Unsupported tool requested.');
        }

        $definition = $handler->definition();
        $validation = $this->toolRegistry->validate($toolCall->name, $toolCall->arguments);
        if ( ! $validation->valid) {
            return $this->failure($toolCall, $startedAt, 'tool_validation_failed', implode(' ', $validation->errors));
        }

        $authorized = $this->toolAuthorizer->authorize($definition, $user);
        if ( ! $authorized) {
            return $this->failure($toolCall, $startedAt, 'tool_unauthorized', 'You are not authorized to use this tool.', false);
        }

        try {
            $result = $handler->execute($toolCall->arguments, $user);
            $result = $this->boundedResult($result, $definition->maxResultSize, $toolCall);

            return [
                'result' => $result,
                'audit' => [
                    'tool' => $toolCall->name,
                    'arguments' => $toolCall->arguments,
                    'status' => $result->status,
                    'authorized' => true,
                    'duration_ms' => $this->durationMs($startedAt),
                    'error_message' => null,
                    'result_meta' => [
                        'kind' => $result->kind,
                        'display' => $result->display,
                    ],
                ],
            ];
        } catch (Throwable $throwable) {
            return $this->failure($toolCall, $startedAt, 'tool_failed', $throwable->getMessage());
        }
    }

    private function boundedResult(AIToolResult $result, int $maxResultSize, AIToolCall $toolCall): AIToolResult
    {
        $encoded = json_encode($result->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false !== $encoded && mb_strlen($encoded) <= $maxResultSize) {
            return $result;
        }

        return new AIToolResult(
            status: 'error',
            kind: 'tool_result_too_large',
            summary: 'The tool result exceeded the maximum safe response size.',
            data: ['tool' => $toolCall->name],
            display: ['type' => 'notice', 'mode' => 'always'],
            metadata: ['maxResultSize' => $maxResultSize],
        );
    }

    /** @return array{result: AIToolResult, audit: array<string, mixed>} */
    private function failure(AIToolCall $toolCall, float $startedAt, string $status, string $message, bool $authorized = false): array
    {
        $result = new AIToolResult(
            status: 'error',
            kind: $status,
            summary: $message,
            data: ['tool' => $toolCall->name],
            display: ['type' => 'notice', 'mode' => 'always'],
        );

        return [
            'result' => $result,
            'audit' => [
                'tool' => $toolCall->name,
                'arguments' => $toolCall->arguments,
                'status' => $status,
                'authorized' => $authorized,
                'duration_ms' => $this->durationMs($startedAt),
                'error_message' => $message,
                'result_meta' => ['kind' => $status],
            ],
        ];
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
