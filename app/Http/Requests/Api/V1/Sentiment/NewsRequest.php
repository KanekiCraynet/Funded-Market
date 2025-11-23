<?php

namespace App\Http\Requests\Api\V1\Sentiment;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => 'Limit must be a valid number',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 100',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Sanitize symbol from route
        if ($this->route('symbol')) {
            $this->merge([
                'symbol' => strtoupper(trim($this->route('symbol'))),
            ]);
        }

        // Sanitize limit parameter
        if ($this->has('limit')) {
            $this->merge([
                'limit' => filter_var($this->limit, FILTER_SANITIZE_NUMBER_INT),
            ]);
        }
    }
}
