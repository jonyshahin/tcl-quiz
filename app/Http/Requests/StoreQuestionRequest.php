<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'options' => ['required', 'array', 'size:4'],
            'options.*.label' => ['required', 'string', 'max:8'],
            'options.*.text' => ['required', 'string'],
            // Index (0–3) of the single correct option — guarantees exactly one correct.
            'correct_index' => ['required', 'integer', 'min:0', 'max:3'],
        ];
    }
}
