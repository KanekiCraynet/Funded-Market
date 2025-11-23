<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Input Sanitization Service
 * 
 * Provides comprehensive input sanitization to prevent:
 * - Cross-Site Scripting (XSS)
 * - SQL Injection
 * - Command Injection
 * - Path Traversal
 * - HTML Injection
 * 
 * Use this service to sanitize user input before processing.
 */
class SanitizationService
{
    /**
     * Sanitize a string for safe display (XSS prevention)
     * 
     * @param string|null $value
     * @param bool $allowHtml Allow safe HTML tags
     * @return string|null
     */
    public function sanitizeString(?string $value, bool $allowHtml = false): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        if (!$allowHtml) {
            // Strip all HTML tags
            $value = strip_tags($value);
            
            // Convert special characters to HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            // Allow only safe HTML tags
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
            $value = strip_tags($value, $allowedTags);
            
            // Remove dangerous attributes
            $value = $this->removeDangerousAttributes($value);
        }

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        return $value;
    }

    /**
     * Sanitize an email address
     * 
     * @param string|null $email
     * @return string|null
     */
    public function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        // Remove whitespace and convert to lowercase
        $email = strtolower(trim($email));

        // Sanitize
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Validate - return null if invalid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Sanitize a URL
     * 
     * @param string|null $url
     * @return string|null
     */
    public function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        // Remove whitespace
        $url = trim($url);

        // Validate URL
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Only allow http and https schemes
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize an integer
     * 
     * @param mixed $value
     * @param int|null $min Minimum value
     * @param int|null $max Maximum value
     * @return int|null
     */
    public function sanitizeInt($value, ?int $min = null, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        $value = (int) $value;

        if ($min !== null && $value < $min) {
            $value = $min;
        }

        if ($max !== null && $value > $max) {
            $value = $max;
        }

        return $value;
    }

    /**
     * Sanitize a float
     * 
     * @param mixed $value
     * @param float|null $min Minimum value
     * @param float|null $max Maximum value
     * @return float|null
     */
    public function sanitizeFloat($value, ?float $min = null, ?float $max = null): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value = (float) $value;

        if ($min !== null && $value < $min) {
            $value = $min;
        }

        if ($max !== null && $value > $max) {
            $value = $max;
        }

        return $value;
    }

    /**
     * Sanitize a boolean
     * 
     * @param mixed $value
     * @return bool|null
     */
    public function sanitizeBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        // Handle string representations
        if (is_string($value)) {
            $value = strtolower(trim($value));
            
            if (in_array($value, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            
            if (in_array($value, ['false', '0', 'no', 'off'])) {
                return false;
            }
        }

        return (bool) $value;
    }

    /**
     * Sanitize an array recursively
     * 
     * @param array|null $array
     * @param bool $allowHtml
     * @return array|null
     */
    public function sanitizeArray(?array $array, bool $allowHtml = false): ?array
    {
        if ($array === null) {
            return null;
        }

        $sanitized = [];

        foreach ($array as $key => $value) {
            // Sanitize the key
            $sanitizedKey = $this->sanitizeString($key);

            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $allowHtml);
            } elseif (is_string($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeString($value, $allowHtml);
            } else {
                $sanitized[$sanitizedKey] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a filename
     * 
     * @param string|null $filename
     * @return string|null
     */
    public function sanitizeFilename(?string $filename): ?string
    {
        if ($filename === null) {
            return null;
        }

        // Remove path traversal attempts
        $filename = basename($filename);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }

        return $filename;
    }

    /**
     * Sanitize a path (prevent directory traversal)
     * 
     * @param string|null $path
     * @return string|null
     */
    public function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        // Remove null bytes
        $path = str_replace("\0", '', $path);

        // Remove directory traversal attempts
        $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);

        // Normalize slashes
        $path = str_replace('\\', '/', $path);

        // Remove multiple slashes
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }

    /**
     * Check if string contains SQL injection patterns
     * 
     * @param string $value
     * @return bool True if suspicious pattern detected
     */
    public function containsSqlInjection(string $value): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(--|#|\/\*|\*\/)/i',  // SQL comments
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(\'|\").*(\bOR\b|\bAND\b)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string contains XSS patterns
     * 
     * @param string $value
     * @return bool True if suspicious pattern detected
     */
    public function containsXss(string $value): bool
    {
        $patterns = [
            '/<script\b[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',  // Event handlers like onclick=
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<applet\b/i',
            '/\beval\s*\(/i',
            '/\balert\s*\(/i',
            '/\bdocument\s*\./i',
            '/\bwindow\s*\./i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string contains command injection patterns
     * 
     * @param string $value
     * @return bool True if suspicious pattern detected
     */
    public function containsCommandInjection(string $value): bool
    {
        $patterns = [
            '/[;&|`$]/',  // Command separators
            '/\$\(.*\)/',  // Command substitution
            '/`.*`/',      // Backtick execution
            '/>\s*\/dev\/null/i',
            '/\|\s*tee/i',
            '/\b(cat|ls|rm|mv|cp|chmod|chown|wget|curl|nc|netcat|bash|sh|perl|python|php)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove dangerous HTML attributes
     * 
     * @param string $html
     * @return string
     */
    private function removeDangerousAttributes(string $html): string
    {
        // Remove event handlers
        $html = preg_replace('/\s*on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $html);

        // Remove javascript: protocol
        $html = preg_replace('/\s*href\s*=\s*["\']?javascript:[^"\']*["\']?/i', '', $html);

        // Remove data: protocol
        $html = preg_replace('/\s*src\s*=\s*["\']?data:[^"\']*["\']?/i', '', $html);

        return $html;
    }

    /**
     * Sanitize input based on type
     * 
     * @param mixed $value
     * @param string $type Type: string, email, url, int, float, bool, array
     * @param array $options Additional options (min, max, allowHtml)
     * @return mixed
     */
    public function sanitize($value, string $type = 'string', array $options = [])
    {
        return match($type) {
            'string' => $this->sanitizeString($value, $options['allowHtml'] ?? false),
            'email' => $this->sanitizeEmail($value),
            'url' => $this->sanitizeUrl($value),
            'int', 'integer' => $this->sanitizeInt($value, $options['min'] ?? null, $options['max'] ?? null),
            'float', 'double' => $this->sanitizeFloat($value, $options['min'] ?? null, $options['max'] ?? null),
            'bool', 'boolean' => $this->sanitizeBool($value),
            'array' => $this->sanitizeArray($value, $options['allowHtml'] ?? false),
            'filename' => $this->sanitizeFilename($value),
            'path' => $this->sanitizePath($value),
            default => $value,
        };
    }

    /**
     * Validate that input doesn't contain malicious patterns
     * 
     * @param string $value
     * @param array $checks Checks to perform: ['sql', 'xss', 'command']
     * @return array ['valid' => bool, 'threats' => array]
     */
    public function validateInput(string $value, array $checks = ['sql', 'xss', 'command']): array
    {
        $threats = [];

        if (in_array('sql', $checks) && $this->containsSqlInjection($value)) {
            $threats[] = 'sql_injection';
        }

        if (in_array('xss', $checks) && $this->containsXss($value)) {
            $threats[] = 'xss';
        }

        if (in_array('command', $checks) && $this->containsCommandInjection($value)) {
            $threats[] = 'command_injection';
        }

        return [
            'valid' => empty($threats),
            'threats' => $threats,
        ];
    }
}
