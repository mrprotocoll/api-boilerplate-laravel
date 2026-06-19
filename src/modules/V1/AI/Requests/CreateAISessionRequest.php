<?php

declare(strict_types=1);

namespace Modules\V1\AI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAISessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sessionToken' => ['nullable', 'string', 'max:100'],
            'sourcePage' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
