<?php

declare(strict_types=1);

namespace Modules\V1\AI\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AISessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sessionToken' => $this->session_token,
            'status' => $this->status,
            'sourcePage' => $this->source_page,
            'lastActivityAt' => $this->last_activity_at,
            'metadata' => $this->metadata,
            'messagesCount' => $this->when(isset($this->messages_count), $this->messages_count),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
