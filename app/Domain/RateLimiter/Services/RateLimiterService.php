<?php

namespace App\Domain\RateLimiter\Services;

use App\Domain\RateLimiter\DTOs\RateLimitResult;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RateLimiterService
{
    private const PREFIX = 'rate_limit:';

    /**
     * Attempt to acquire a rate limit lock.
     */
    public function attempt(string $key, int $ttlSeconds = 60): RateLimitResult
    {
        $lockKey = self::PREFIX . $key;
        
        try {
            // Try to set key with NX (only if not exists) and EX (expire)
            // Redis SET command with NX and EX options
            $acquired = Redis::set($lockKey, time(), 'EX', $ttlSeconds, 'NX');
            
            if ($acquired === null || $acquired === false) {
                // Key already exists, get TTL
                $ttl = Redis::ttl($lockKey);
                return new RateLimitResult(false, max($ttl, 0));
            }
            
            return new RateLimitResult(true, 0);
            
        } catch (\Exception $e) {
            Log::error('Rate limiter error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            // On Redis error, allow the request (fail open)
            return new RateLimitResult(true, 0);
        }
    }

    /**
     * Reset/release a rate limit lock.
     */
    public function reset(string $key): void
    {
        try {
            Redis::del(self::PREFIX . $key);
        } catch (\Exception $e) {
            Log::error('Rate limiter reset error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get remaining time until rate limit resets.
     */
    public function getRemainingTime(string $key): int
    {
        try {
            $ttl = Redis::ttl(self::PREFIX . $key);
            return max($ttl, 0);
        } catch (\Exception $e) {
            Log::error('Rate limiter TTL check error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Check if a key is currently locked.
     */
    public function isLocked(string $key): bool
    {
        try {
            return Redis::exists(self::PREFIX . $key) > 0;
        } catch (\Exception $e) {
            Log::error('Rate limiter lock check error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get information about a rate limit.
     */
    public function getInfo(string $key): array
    {
        return [
            'locked' => $this->isLocked($key),
            'remaining_time' => $this->getRemainingTime($key),
            'key' => self::PREFIX . $key,
        ];
    }

    /**
     * Increment a counter with TTL.
     * Useful for counting requests within a time window.
     */
    public function increment(string $key, int $ttlSeconds = 60): int
    {
        try {
            $lockKey = self::PREFIX . $key;
            $value = Redis::incr($lockKey);
            
            if ($value === 1) {
                // First increment, set TTL
                Redis::expire($lockKey, $ttlSeconds);
            }
            
            return $value;
        } catch (\Exception $e) {
            Log::error('Rate limiter increment error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Check if count exceeds limit within time window.
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $ttlSeconds = 60): bool
    {
        $count = $this->increment($key, $ttlSeconds);
        return $count > $maxAttempts;
    }

    /**
     * Clear all rate limit keys (use with caution!).
     */
    public function clearAll(): int
    {
        try {
            $keys = Redis::keys(self::PREFIX . '*');
            
            if (empty($keys)) {
                return 0;
            }
            
            return Redis::del(...$keys);
        } catch (\Exception $e) {
            Log::error('Rate limiter clear all error', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
