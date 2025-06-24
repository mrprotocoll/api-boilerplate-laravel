<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\V1\User\Enums\RoleEnum;
use Shared\Helpers\DateTimeHelper;
use Shared\Helpers\StringHelper;

/**
 * @OA\Schema(
 *     schema="AdminResource",
 *     title="Admin Resource",
 *     description="Schema for the admin resource",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 * )
 */
final class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstName' => StringHelper::toTitleCase($this->first_name),
            'lastName' => StringHelper::toTitleCase($this->last_name),
            'email' => $this->email,
//            'roles' => $this->role->name,
            'status' => $this->status,
            'isSuperAdmin' => $this->super_admin,
            'createdAt' => DateTimeHelper::dateTime($this->created_at),
        ];
    }
}
