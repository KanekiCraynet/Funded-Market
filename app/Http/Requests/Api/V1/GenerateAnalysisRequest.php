<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'min:1',
                'max:10',
                'regex:/^[A-Z0-9\.\-]+$/i', // Allow letters, numbers, dots, and hyphens
            ],
            // Optional parameters
            'time_horizon' => [
                'nullable',
                'string',
                'in:short_term,medium_term,long_term',
            ],
            'force_refresh' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.required' => 'Symbol is required',
            'symbol.string' => 'Symbol must be a string',
            'symbol.min' => 'Symbol must be at least 1 character',
            'symbol.max' => 'Symbol must not exceed 10 characters',
            'symbol.regex' => 'Symbol contains invalid characters',
            'time_horizon.in' => 'Time horizon must be one of: short_term, medium_term, long_term',
            'force_refresh.boolean' => 'Force refresh must be a boolean value',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert symbol to uppercase and trim whitespace
        if ($this->has('symbol')) {
            $this->merge([
                'symbol' => strtoupper(trim($this->input('symbol')))
            ]);
        }
    }

    /**
     * NOTE: Database existence check removed from validation layer
     * 
     * Previously, this checked if the symbol exists in the database,
     * which caused blocking I/O during validation.
     * 
     * This check has been moved to the controller where it can be:
     * - Cached properly using InstrumentService
     * - Handled with appropriate HTTP status codes (404)
     * - Tested more easily
     * 
     * See: AnalysisController@generate for the new implementation
     */
}