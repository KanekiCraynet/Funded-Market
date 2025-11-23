<?php

namespace App\Domain\RateLimiter\Exceptions;

use Exception;

class RateLimitException extends Exception
{
    public function __construct(
        public readonly int $retryAfter,
        string $message = null
    ) {
        parent::__construct(
            $message ?? "Rate limit exceeded. Retry after {$retryAfter} seconds."
        );
    }

    /**
     * Get the retry after time in seconds.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'retry_after' => $this->retryAfter,
            'error' => 'rate_limit_exceeded',
        ], 429);
    }
}
