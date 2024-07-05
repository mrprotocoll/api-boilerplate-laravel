<?php

declare(strict_types=1);

namespace Modules\V1\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UserUpdateRequest",
 *     title="Profile Update Request",
 *     description="Request data for updating user profile.",
 *     type="object",
 *     required={"name", "job_title"},
 *
 *     @OA\Property(property="name", type="string", example="John", description="User's first name"),
 *     @OA\Property(property="job_title", type="string", example="Doe", description="User's job title"),
 * )
 */
final class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
