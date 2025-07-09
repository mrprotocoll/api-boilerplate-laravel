<?php

namespace Modules\V1\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\V1\Admin\Models\Admin;

/**
 * @OA\Schema(
 *     schema="AdminInviteRequest",
 *     title="User Registration Request",
 *     description="Schema for the user registration request",
 *     type="object",
 *     required={"first_name", "last_name", "country_id", "email", "password"},
 *     @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
 *     @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
 *     @OA\Property(property="country_id", type="string", example="1"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="password", type="string", example="password123"),
 * )
 */
class AdminInviteRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.Admin::class],
            'password' => ['required', 'string'],
        ];
    }
}
