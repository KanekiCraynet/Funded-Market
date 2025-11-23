<?php

namespace App\Domain\LLM\Services;

class JsonSchemaValidator
{
    private array $analysisSchema;

    public function __construct()
    {
        $this->analysisSchema = [
            'final_score' => ['required' => true, 'type' => 'numeric', 'min' => -1, 'max' => 1],
            'recommendation' => ['required' => true, 'type' => 'string', 'enum' => ['BUY', 'SELL', 'HOLD']],
            'confidence' => ['required' => true, 'type' => 'numeric', 'min' => 0, 'max' => 1],
            'top_drivers' => ['required' => true, 'type' => 'array', 'min_items' => 1, 'max_items' => 5],
            'evidence_sentences' => ['required' => false, 'type' => 'array'],
            'explainability_text' => ['required' => false, 'type' => 'string'],
            'risk_notes' => ['required' => false, 'type' => 'string'],
            'position_size_recommendation' => ['required' => false, 'type' => 'array'],
        ];
    }

    /**
     * Validate data against the analysis schema.
     */
    public function validate(array $data): bool
    {
        $errors = $this->getErrors($data);
        return empty($errors);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(array $data): array
    {
        $errors = [];

        foreach ($this->analysisSchema as $field => $rules) {
            // Check required fields
            if ($rules['required'] && !isset($data[$field])) {
                $errors[] = "Field '{$field}' is required";
                continue;
            }

            // Skip validation if field is not required and not present
            if (!$rules['required'] && !isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            // Validate type
            switch ($rules['type']) {
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[] = "Field '{$field}' must be numeric";
                        continue 2;
                    }
                    
                    // Validate min/max for numeric values
                    if (isset($rules['min']) && $value < $rules['min']) {
                        $errors[] = "Field '{$field}' must be >= {$rules['min']}";
                    }
                    if (isset($rules['max']) && $value > $rules['max']) {
                        $errors[] = "Field '{$field}' must be <= {$rules['max']}";
                    }
                    break;

                case 'string':
                    if (!is_string($value)) {
                        $errors[] = "Field '{$field}' must be a string";
                        continue 2;
                    }
                    
                    // Validate enum
                    if (isset($rules['enum']) && !in_array($value, $rules['enum'])) {
                        $allowed = implode(', ', $rules['enum']);
                        $errors[] = "Field '{$field}' must be one of: {$allowed}";
                    }
                    break;

                case 'array':
                    if (!is_array($value)) {
                        $errors[] = "Field '{$field}' must be an array";
                        continue 2;
                    }
                    
                    // Validate array size
                    if (isset($rules['min_items']) && count($value) < $rules['min_items']) {
                        $errors[] = "Field '{$field}' must have at least {$rules['min_items']} items";
                    }
                    if (isset($rules['max_items']) && count($value) > $rules['max_items']) {
                        $errors[] = "Field '{$field}' must have at most {$rules['max_items']} items";
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Validate and sanitize data.
     */
    public function validateAndSanitize(array $data): array
    {
        if (!$this->validate($data)) {
            throw new \InvalidArgumentException(
                'Validation failed: ' . implode(', ', $this->getErrors($data))
            );
        }

        // Sanitize numeric values
        if (isset($data['final_score'])) {
            $data['final_score'] = (float) $data['final_score'];
            $data['final_score'] = max(-1, min(1, $data['final_score'])); // Clamp to [-1, 1]
        }

        if (isset($data['confidence'])) {
            $data['confidence'] = (float) $data['confidence'];
            $data['confidence'] = max(0, min(1, $data['confidence'])); // Clamp to [0, 1]
        }

        // Ensure recommendation is uppercase
        if (isset($data['recommendation'])) {
            $data['recommendation'] = strtoupper($data['recommendation']);
        }

        // Ensure arrays exist
        $data['evidence_sentences'] = $data['evidence_sentences'] ?? [];
        $data['top_drivers'] = $data['top_drivers'] ?? [];

        // Ensure strings exist
        $data['explainability_text'] = $data['explainability_text'] ?? '';
        $data['risk_notes'] = $data['risk_notes'] ?? '';

        return $data;
    }

    /**
     * Check if data has all required fields.
     */
    public function hasRequiredFields(array $data): bool
    {
        foreach ($this->analysisSchema as $field => $rules) {
            if ($rules['required'] && !isset($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get list of required fields.
     */
    public function getRequiredFields(): array
    {
        $required = [];
        foreach ($this->analysisSchema as $field => $rules) {
            if ($rules['required']) {
                $required[] = $field;
            }
        }
        return $required;
    }
}
