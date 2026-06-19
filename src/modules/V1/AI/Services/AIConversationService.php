<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\Models\AIMessage;
use Modules\V1\AI\Models\AISession;
use Modules\V1\AI\Models\AIToolCall;

final class AIConversationService
{
    private const SESSION_TTL_SECONDS = 86400;

    public function __construct(
        private readonly AIRuntime $runtime,
        private readonly AIUsageLimiter $usageLimiter,
        private readonly AIToolRegistry $toolRegistry,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function createOrResumeSession(array $data, AIActorContext $actor): AISession
    {
        $requestedSessionToken = isset($data['sessionToken']) && is_string($data['sessionToken'])
            ? trim($data['sessionToken'])
            : '';
        $sessionToken = '' !== $requestedSessionToken ? $requestedSessionToken : Str::uuid()->toString();

        $existingSession = AISession::query()->where('session_token', $sessionToken)->first();
        if ($existingSession instanceof AISession) {
            if ((string) $existingSession->actor_type !== $actor->morphClass() || (string) $existingSession->actor_id !== $actor->id()) {
                throw new ModelNotFoundException('AI session not found.');
            }

            if ($this->isSessionExpired($existingSession)) {
                $sessionToken = Str::uuid()->toString();
            } else {
                $existingSession->update([
                    'status' => 'active',
                    'source_page' => $this->nullableString($data['sourcePage'] ?? $existingSession->source_page),
                    'last_activity_at' => time(),
                ]);

                return $existingSession->refresh();
            }
        }

        return AISession::query()->create([
            'session_token' => $sessionToken,
            'actor_type' => $actor->morphClass(),
            'actor_id' => $actor->id(),
            'status' => 'active',
            'source_page' => $this->nullableString($data['sourcePage'] ?? null),
            'last_activity_at' => time(),
            'metadata' => isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
        ]);
    }

    public function getLatestSession(AIActorContext $actor): ?AISession
    {
        return AISession::query()
            ->where('actor_type', $actor->morphClass())
            ->where('actor_id', $actor->id())
            ->where('status', 'active')
            ->where('last_activity_at', '>=', time() - self::SESSION_TTL_SECONDS)
            ->has('messages')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /** @return LengthAwarePaginator<int, AISession> */
    public function getSessionHistory(AIActorContext $actor, int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 50));

        return AISession::query()
            ->where('actor_type', $actor->morphClass())
            ->where('actor_id', $actor->id())
            ->has('messages')
            ->withCount('messages')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     * @param callable(string): void|null $onDelta
     * @param callable(AIMessage): void|null $onUserMessage
     * @param callable(string): void|null $onToolStart
     * @param callable(string, array<string, mixed>): void|null $onToolDone
     * @return array{session: AISession, userMessage: AIMessage, assistantMessage: AIMessage}
     */
    public function sendMessage(
        array $data,
        AIActorContext $actor,
        ?callable $onDelta = null,
        ?callable $onUserMessage = null,
        ?callable $onToolStart = null,
        ?callable $onToolDone = null,
    ): array {
        $usageLimit = $this->usageLimiter->check($actor);
        if ( ! $usageLimit->allowed) {
            $session = $this->createOrResumeSession($data, $actor);
            $userMessage = AIMessage::query()->create([
                'session_id' => $session->id,
                'actor_type' => $actor->morphClass(),
                'actor_id' => $actor->id(),
                'role' => 'user',
                'content' => (string) ($data['message'] ?? ''),
                'metadata' => isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            ]);
            $assistantMessage = AIMessage::query()->create([
                'session_id' => $session->id,
                'actor_type' => $actor->morphClass(),
                'actor_id' => $actor->id(),
                'role' => 'assistant',
                'content' => $usageLimit->message ?? 'Usage limit reached.',
                'attachment' => ['type' => 'notice', 'kind' => 'usage_limit'],
                'metadata' => ['failure_type' => $usageLimit->reason],
            ]);

            if (is_callable($onUserMessage)) {
                $onUserMessage($userMessage);
            }

            return [
                'session' => $session,
                'userMessage' => $userMessage,
                'assistantMessage' => $assistantMessage,
            ];
        }

        $persisted = DB::transaction(function () use ($data, $actor): array {
            $session = $this->createOrResumeSession($data, $actor);
            $session->update([
                'source_page' => $this->nullableString($data['sourcePage'] ?? $session->source_page),
                'last_activity_at' => time(),
            ]);

            $history = AIMessage::query()
                ->where('session_id', $session->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->reverse()
                ->values()
                ->all();

            $userMessage = AIMessage::query()->create([
                'session_id' => $session->id,
                'actor_type' => $actor->morphClass(),
                'actor_id' => $actor->id(),
                'role' => 'user',
                'content' => (string) $data['message'],
                'metadata' => isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            ]);

            return compact('session', 'history', 'userMessage');
        });

        if (is_callable($onUserMessage)) {
            $onUserMessage($persisted['userMessage']);
        }

        $runtimeResult = $this->runtime->run(
            (string) $data['message'],
            $persisted['history'],
            $actor,
            $data,
            $onDelta,
            $onToolStart,
            $onToolDone,
        );

        $assistantMessage = AIMessage::query()->create([
            'session_id' => $persisted['session']->id,
            'actor_type' => $actor->morphClass(),
            'actor_id' => $actor->id(),
            'role' => 'assistant',
            'content' => $runtimeResult->reply,
            'attachment' => $runtimeResult->attachment,
            'suggestions' => $runtimeResult->suggestions,
            'provider' => $runtimeResult->response?->getProvider(),
            'model' => $runtimeResult->response?->getModel(),
            'tokens_prompt' => $runtimeResult->response?->getPromptTokens(),
            'tokens_completion' => $runtimeResult->response?->getCompletionTokens(),
            'cost' => $runtimeResult->response?->getCost(),
            'metadata' => $runtimeResult->metadata,
        ]);

        foreach ($runtimeResult->toolAudits as $audit) {
            AIToolCall::query()->create([
                'session_id' => $persisted['session']->id,
                'message_id' => $assistantMessage->id,
                'actor_type' => $actor->morphClass(),
                'actor_id' => $actor->id(),
                'tool' => (string) ($audit['tool'] ?? 'unknown'),
                'arguments' => isset($audit['arguments']) && is_array($audit['arguments']) ? $audit['arguments'] : [],
                'status' => (string) ($audit['status'] ?? 'unknown'),
                'authorized' => (bool) ($audit['authorized'] ?? false),
                'duration_ms' => isset($audit['duration_ms']) ? (int) $audit['duration_ms'] : null,
                'error_message' => isset($audit['error_message']) ? (string) $audit['error_message'] : null,
                'result_meta' => isset($audit['result_meta']) && is_array($audit['result_meta']) ? $audit['result_meta'] : [],
            ]);
        }

        return [
            'session' => $persisted['session']->refresh(),
            'userMessage' => $persisted['userMessage']->refresh(),
            'assistantMessage' => $assistantMessage->refresh(),
        ];
    }

    /** @return LengthAwarePaginator<int, AIMessage> */
    public function getMessages(string $sessionToken, AIActorContext $actor, int $perPage = 50): LengthAwarePaginator
    {
        $session = $this->resolveOwnedSession($sessionToken, $actor);

        return AIMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('created_at')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @param array<string, mixed> $data */
    public function flagMessage(string $messageId, array $data, AIActorContext $actor): AIMessage
    {
        $message = AIMessage::query()
            ->where('id', $messageId)
            ->where('actor_type', $actor->morphClass())
            ->where('actor_id', $actor->id())
            ->firstOrFail();

        $message->update([
            'is_flagged' => true,
            'flag_reason' => $this->nullableString($data['reason'] ?? null),
            'flagged_at' => time(),
        ]);

        return $message->refresh();
    }

    /** @return array<string, mixed> */
    public function capabilities(?AIActorContext $actor = null): array
    {
        return [
            'supportedTools' => $this->toolRegistry->supportedTools($actor),
            'limits' => [
                'maxToolSteps' => (int) config('ai.assistant.runtime.max_tool_steps', 3),
                'maxToolCallsPerRequest' => (int) config('ai.assistant.runtime.max_tool_calls_per_request', 5),
            ],
        ];
    }

    private function resolveOwnedSession(string $sessionToken, AIActorContext $actor): AISession
    {
        return AISession::query()
            ->where('session_token', $sessionToken)
            ->where('actor_type', $actor->morphClass())
            ->where('actor_id', $actor->id())
            ->firstOrFail();
    }

    private function isSessionExpired(AISession $session): bool
    {
        return null !== $session->last_activity_at && $session->last_activity_at < time() - self::SESSION_TTL_SECONDS;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }
}
