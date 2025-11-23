<?php

namespace App\Http\Requests\Api\V1\Quant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndicatorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'sometimes',
                'integer',
                'min:50',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period.integer' => 'Period must be a valid number',
            'period.min' => 'Period must be at least 50',
            'period.max' => 'Period cannot exceed 1000',
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

        // Sanitize period parameter
        if ($this->has('period')) {
            $this->merge([
                'period' => filter_var($this->period, FILTER_SANITIZE_NUMBER_INT),
            ]);
        }
    }
}
