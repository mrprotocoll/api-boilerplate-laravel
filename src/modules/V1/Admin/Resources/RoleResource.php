<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Shared\Helpers\DateTimeHelper;

/**
 * @OA\Schema(
 *     schema="RoleResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/PermissionResource")),
 *     @OA\Property(property="createdAt", type="string", format="date-time")
 * )
 */
final class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'createdAt' => DateTimeHelper::dateTime($this->created_at),
        ];
    }
}
