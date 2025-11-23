<?php

namespace App\Http\Requests\Api\V1\Sentiment;

use Illuminate\Foundation\Http\FormRequest;

class SentimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // No query parameters for sentiment endpoint
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
    }
}
