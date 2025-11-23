<?php

namespace App\Domain\RateLimiter\DTOs;

class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $retryAfter
    ) {}

    /**
     * Check if the request is allowed.
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Check if the request is denied.
     */
    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    /**
     * Get retry after time in seconds.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'retry_after' => $this->retryAfter,
        ];
    }
}
