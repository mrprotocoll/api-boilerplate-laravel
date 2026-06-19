<?php

declare(strict_types=1);

namespace Modules\V1\AI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FlagAIMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
