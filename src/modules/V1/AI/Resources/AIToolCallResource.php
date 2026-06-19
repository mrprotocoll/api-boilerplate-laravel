<?php

declare(strict_types=1);

namespace Modules\V1\AI\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AIToolCallResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'tool' => $this->tool,
            'arguments' => $this->arguments,
            'status' => $this->status,
            'authorized' => $this->authorized,
            'durationMs' => $this->duration_ms,
            'errorMessage' => $this->error_message,
            'resultMeta' => $this->result_meta,
            'createdAt' => $this->created_at,
        ];
    }
}
