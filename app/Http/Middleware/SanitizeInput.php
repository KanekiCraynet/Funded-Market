<?php

namespace App\Http\Middleware;

use App\Services\SanitizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Input Sanitization Middleware
 * 
 * Automatically sanitizes all incoming request data to prevent:
 * - XSS attacks
 * - SQL injection
 * - Command injection
 * 
 * Can be applied globally or per-route.
 */
class SanitizeInput
{
    private SanitizationService $sanitizer;

    public function __construct(SanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $mode  Mode: 'strict' or 'lenient'
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $mode = 'strict')
    {
        // Skip sanitization for certain content types
        if ($this->shouldSkipSanitization($request)) {
            return $next($request);
        }

        // Get all input
        $input = $request->all();

        // Sanitize input
        $sanitized = $this->sanitizeRecursive($input, $mode);

        // Check for malicious patterns in strict mode
        if ($mode === 'strict') {
            $threats = $this->detectThreats($input);
            
            if (!empty($threats)) {
                Log::warning('Malicious input detected', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'threats' => $threats,
                    'user_id' => $request->user()?->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input detected',
                    'error' => 'malicious_input',
                    'data' => null,
                ], 400);
            }
        }

        // Replace request input with sanitized version
        $request->replace($sanitized);

        return $next($request);
    }

    /**
     * Check if sanitization should be skipped
     */
    private function shouldSkipSanitization(Request $request): bool
    {
        // Skip for JSON API requests (handled by FormRequests)
        if ($request->isJson() && $request->is('api/*')) {
            return true;
        }

        // Skip for file uploads
        if ($request->hasFile('*')) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize data recursively
     */
    private function sanitizeRecursive($data, string $mode)
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitizedKey = $this->sanitizer->sanitizeString($key);
                $sanitized[$sanitizedKey] = $this->sanitizeRecursive($value, $mode);
            }
            return $sanitized;
        }

        if (is_string($data)) {
            // In lenient mode, allow some HTML
            $allowHtml = ($mode === 'lenient');
            return $this->sanitizer->sanitizeString($data, $allowHtml);
        }

        return $data;
    }

    /**
     * Detect malicious patterns in input
     */
    private function detectThreats(array $input, string $prefix = ''): array
    {
        $threats = [];

        foreach ($input as $key => $value) {
            $fieldName = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $threats = array_merge($threats, $this->detectThreats($value, $fieldName));
            } elseif (is_string($value)) {
                $validation = $this->sanitizer->validateInput($value);
                
                if (!$validation['valid']) {
                    $threats[$fieldName] = $validation['threats'];
                }
            }
        }

        return $threats;
    }
}
