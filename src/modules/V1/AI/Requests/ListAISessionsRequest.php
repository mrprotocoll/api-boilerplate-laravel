<?php

declare(strict_types=1);

namespace Modules\V1\AI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAISessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
