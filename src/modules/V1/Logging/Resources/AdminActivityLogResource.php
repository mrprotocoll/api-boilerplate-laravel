<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\User\Resources\AdminResource;

/**
 * @OA\Schema(
 *     schema="AdminActivityLogResource",
 *     type="object",
 *     required={"id", "admin", "action", "model_type", "model_id", "meta", "created_at"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="The unique identifier for the activity log."
 *     ),
 *     @OA\Property(
 *         property="admin",
 *         ref="#/components/schemas/AdminResource",
 *         description="The admin who performed the action."
 *     ),
 *     @OA\Property(
 *         property="action",
 *         type="string",
 *         description="The action performed by the admin (e.g., 'approved_contract')."
 *     ),
 *     @OA\Property(
 *         property="model_type",
 *         type="string",
 *         description="The type of the model being acted upon (e.g., 'contract')."
 *     ),
 *     @OA\Property(
 *         property="model_id",
 *         type="integer",
 *         description="The ID of the model being acted upon (e.g., the contract ID)."
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         description="Additional metadata related to the action (e.g., contract name, user ID)."
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The timestamp when the activity log was created."
 *     )
 * )
 */
final class AdminActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin' => new AdminResource($this->admin),
            'action' => $this->action,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
