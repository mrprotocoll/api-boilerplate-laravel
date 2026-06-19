<?php

declare(strict_types=1);

namespace Modules\V1\AI\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AIMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sessionId' => (string) $this->session_id,
            'role' => $this->role,
            'content' => $this->content,
            'attachment' => $this->attachment,
            'suggestions' => $this->suggestions,
            'provider' => $this->provider,
            'model' => $this->model,
            'tokens' => [
                'prompt' => $this->tokens_prompt,
                'completion' => $this->tokens_completion,
                'total' => ((int) $this->tokens_prompt) + ((int) $this->tokens_completion),
            ],
            'cost' => $this->cost,
            'metadata' => $this->metadata,
            'isFlagged' => $this->is_flagged,
            'flagReason' => $this->flag_reason,
            'flaggedAt' => $this->flagged_at,
            'createdAt' => $this->created_at,
        ];
    }
}
