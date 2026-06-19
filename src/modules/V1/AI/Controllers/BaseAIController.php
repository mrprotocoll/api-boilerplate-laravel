<?php

declare(strict_types=1);

namespace Modules\V1\AI\Controllers;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\V1\AI\DTO\AIActorContext;
use Modules\V1\AI\Models\AIMessage;
use Modules\V1\AI\Requests\CreateAISessionRequest;
use Modules\V1\AI\Requests\FlagAIMessageRequest;
use Modules\V1\AI\Requests\ListAISessionsRequest;
use Modules\V1\AI\Requests\StoreAIMessageRequest;
use Modules\V1\AI\Resources\AIMessageResource;
use Modules\V1\AI\Resources\AISessionResource;
use Modules\V1\AI\Services\AIConversationService;
use Shared\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

abstract class BaseAIController
{
    public function __construct(
        private readonly AIConversationService $conversationService,
    ) {
    }

    public function createSession(CreateAISessionRequest $request): JsonResponse
    {
        try {
            $actor = $this->authenticatedActor();
            $session = $this->conversationService->createOrResumeSession($request->validated(), $actor);

            return ResponseHelper::success([
                'session' => AISessionResource::make($session),
                ...$this->conversationService->capabilities($actor),
            ], 'AI session ready');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function latestSession(): JsonResponse
    {
        try {
            $actor = $this->authenticatedActor();
            $session = $this->conversationService->getLatestSession($actor);

            return ResponseHelper::success([
                'session' => null !== $session ? AISessionResource::make($session) : null,
                ...$this->conversationService->capabilities($actor),
            ], 'Latest AI session retrieved');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function sessions(ListAISessionsRequest $request): JsonResponse
    {
        try {
            $sessions = $this->conversationService->getSessionHistory(
                $this->authenticatedActor(),
                $request->integer('perPage', 20),
            );

            return ResponseHelper::success(AISessionResource::collection($sessions), 'AI sessions retrieved successfully');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function storeMessage(StoreAIMessageRequest $request): JsonResponse
    {
        try {
            $response = $this->conversationService->sendMessage($request->validated(), $this->authenticatedActor());

            return ResponseHelper::success([
                'session' => AISessionResource::make($response['session']),
                'userMessage' => AIMessageResource::make($response['userMessage']),
                'assistantMessage' => AIMessageResource::make($response['assistantMessage']),
            ], 'Message processed successfully');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function streamMessage(StoreAIMessageRequest $request): JsonResponse|StreamedResponse
    {
        try {
            $actor = $this->authenticatedActor();
        } catch (Throwable) {
            return ResponseHelper::unauthenticated();
        }

        $data = $request->validated();
        $service = $this->conversationService;

        return new StreamedResponse(function () use ($data, $actor, $service): void {
            $emit = static function (string $type, array $payload = []): void {
                echo 'data: ' . json_encode(['type' => $type, ...$payload], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            try {
                $response = $service->sendMessage(
                    $data,
                    $actor,
                    onDelta: static fn (string $delta): null => $emit('delta', ['content' => $delta]),
                    onUserMessage: static fn (AIMessage $message): null => $emit('user_message', ['message' => AIMessageResource::make($message)->resolve()]),
                    onToolStart: static fn (string $tool): null => $emit('tool_start', ['tool' => $tool]),
                    onToolDone: static fn (string $tool, array $audit): null => $emit('tool_done', [
                        'tool' => $tool,
                        'status' => $audit['status'] ?? null,
                        'durationMs' => $audit['duration_ms'] ?? null,
                    ]),
                );

                $emit('session', ['sessionToken' => $response['session']->session_token]);
                $emit('assistant_message', [
                    'message' => AIMessageResource::make($response['assistantMessage'])->resolve(),
                ]);
                $emit('done');
            } catch (Throwable) {
                $emit('error', ['message' => "I'm not reachable right now. Please try again in a moment."]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function messages(ListAISessionsRequest $request, string $sessionToken): JsonResponse
    {
        try {
            $messages = $this->conversationService->getMessages(
                $sessionToken,
                $this->authenticatedActor(),
                $request->integer('perPage', 50),
            );

            return ResponseHelper::success(AIMessageResource::collection($messages), 'AI messages retrieved successfully');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    public function flagMessage(FlagAIMessageRequest $request, string $messageId): JsonResponse
    {
        try {
            $message = $this->conversationService->flagMessage($messageId, $request->validated(), $this->authenticatedActor());

            return ResponseHelper::success(AIMessageResource::make($message), 'AI message flagged successfully');
        } catch (Throwable $throwable) {
            return $this->errorResponse($throwable);
        }
    }

    abstract protected function authenticatedActor(): AIActorContext;

    private function errorResponse(Throwable $throwable): JsonResponse
    {
        if ($throwable instanceof ModelNotFoundException) {
            return ResponseHelper::notFound('AI resource not found');
        }

        if ('Unauthenticated' === $throwable->getMessage()) {
            return ResponseHelper::unauthenticated();
        }

        return ResponseHelper::serverError($throwable instanceof Exception ? $throwable : new Exception($throwable->getMessage()));
    }
}
