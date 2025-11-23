<?php

namespace App\Http\Requests\Api\V1\Quant;

use Illuminate\Foundation\Http\FormRequest;

class TrendsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // No query parameters for trends endpoint
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
