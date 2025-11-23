<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 * 
 * Protects application from cascading failures by detecting service failures
 * and preventing continued attempts to a failing service.
 * 
 * States:
 * - CLOSED: Normal operation, requests go through
 * - OPEN: Service is failing, requests fail fast
 * - HALF_OPEN: Testing if service has recovered
 */
class CircuitBreakerService
{
    // Circuit states
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    // Configuration
    private const FAILURE_THRESHOLD = 5;        // Failures before opening circuit
    private const SUCCESS_THRESHOLD = 2;        // Successes to close circuit from half-open
    private const TIMEOUT = 60;                 // Seconds before trying half-open
    private const WINDOW_SIZE = 10;             // Seconds for failure counting
    
    /**
     * Execute a callable with circuit breaker protection
     *
     * @param string $serviceName Unique identifier for the service
     * @param callable $callable The function to execute
     * @param callable|null $fallback Optional fallback function
     * @return mixed
     * @throws \Exception
     */
    public function call(string $serviceName, callable $callable, ?callable $fallback = null)
    {
        $state = $this->getState($serviceName);

        // If circuit is OPEN, fail fast
        if ($state === self::STATE_OPEN) {
            $this->checkTimeout($serviceName);
            $state = $this->getState($serviceName);
            
            if ($state === self::STATE_OPEN) {
                Log::warning("Circuit breaker OPEN for {$serviceName}, failing fast");
                
                if ($fallback) {
                    return $fallback();
                }
                
                throw new \RuntimeException("Service '{$serviceName}' is currently unavailable (circuit breaker OPEN)");
            }
        }

        try {
            // Execute the callable
            $result = $callable();
            
            // Record success
            $this->recordSuccess($serviceName);
            
            return $result;

        } catch (\Exception $e) {
            // Record failure
            $this->recordFailure($serviceName);
            
            Log::error("Circuit breaker recorded failure for {$serviceName}", [
                'error' => $e->getMessage(),
                'failures' => $this->getFailureCount($serviceName),
            ]);

            // If fallback provided, use it
            if ($fallback) {
                return $fallback();
            }

            // Re-throw exception
            throw $e;
        }
    }

    /**
     * Get current circuit state
     */
    public function getState(string $serviceName): string
    {
        $key = $this->getStateKey($serviceName);
        return Cache::get($key, self::STATE_CLOSED);
    }

    /**
     * Get circuit breaker statistics
     */
    public function getStats(string $serviceName): array
    {
        $state = $this->getState($serviceName);
        $failures = $this->getFailureCount($serviceName);
        $successes = $this->getSuccessCount($serviceName);
        $lastFailure = Cache::get($this->getLastFailureKey($serviceName));
        $lastSuccess = Cache::get($this->getLastSuccessKey($serviceName));

        return [
            'service' => $serviceName,
            'state' => $state,
            'failures' => $failures,
            'successes' => $successes,
            'failure_threshold' => self::FAILURE_THRESHOLD,
            'success_threshold' => self::SUCCESS_THRESHOLD,
            'last_failure_at' => $lastFailure,
            'last_success_at' => $lastSuccess,
            'timeout' => self::TIMEOUT,
        ];
    }

    /**
     * Reset circuit breaker for a service
     */
    public function reset(string $serviceName): void
    {
        Cache::forget($this->getStateKey($serviceName));
        Cache::forget($this->getFailureCountKey($serviceName));
        Cache::forget($this->getSuccessCountKey($serviceName));
        Cache::forget($this->getOpenedAtKey($serviceName));
        Cache::forget($this->getLastFailureKey($serviceName));
        Cache::forget($this->getLastSuccessKey($serviceName));

        Log::info("Circuit breaker reset for {$serviceName}");
    }

    /**
     * Record a successful call
     */
    private function recordSuccess(string $serviceName): void
    {
        $state = $this->getState($serviceName);

        // Increment success counter
        $successKey = $this->getSuccessCountKey($serviceName);
        $successes = (int) Cache::get($successKey, 0) + 1;
        Cache::put($successKey, $successes, self::WINDOW_SIZE);

        // Record timestamp
        Cache::put($this->getLastSuccessKey($serviceName), now()->toIso8601String(), self::TIMEOUT * 2);

        // State transitions
        if ($state === self::STATE_HALF_OPEN) {
            // If enough successes, close the circuit
            if ($successes >= self::SUCCESS_THRESHOLD) {
                $this->transitionTo($serviceName, self::STATE_CLOSED);
                Cache::forget($this->getFailureCountKey($serviceName));
                Cache::forget($this->getSuccessCountKey($serviceName));
                
                Log::info("Circuit breaker transitioned to CLOSED for {$serviceName}");
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success
            $failureKey = $this->getFailureCountKey($serviceName);
            $failures = (int) Cache::get($failureKey, 0);
            
            if ($failures > 0) {
                Cache::decrement($failureKey);
            }
        }
    }

    /**
     * Record a failed call
     */
    private function recordFailure(string $serviceName): void
    {
        $state = $this->getState($serviceName);

        // Increment failure counter
        $failureKey = $this->getFailureCountKey($serviceName);
        $failures = (int) Cache::increment($failureKey);
        Cache::put($failureKey, $failures, self::WINDOW_SIZE);

        // Record timestamp
        Cache::put($this->getLastFailureKey($serviceName), now()->toIso8601String(), self::TIMEOUT * 2);

        // State transitions
        if ($state === self::STATE_HALF_OPEN) {
            // Single failure from half-open goes back to open
            $this->transitionTo($serviceName, self::STATE_OPEN);
            $this->recordOpenedAt($serviceName);
            
            Log::warning("Circuit breaker transitioned to OPEN from HALF_OPEN for {$serviceName}");

        } elseif ($state === self::STATE_CLOSED) {
            // If threshold reached, open the circuit
            if ($failures >= self::FAILURE_THRESHOLD) {
                $this->transitionTo($serviceName, self::STATE_OPEN);
                $this->recordOpenedAt($serviceName);
                
                Log::error("Circuit breaker transitioned to OPEN for {$serviceName} (failures: {$failures})");
            }
        }
    }

    /**
     * Check if timeout has elapsed and transition to HALF_OPEN
     */
    private function checkTimeout(string $serviceName): void
    {
        $openedAt = Cache::get($this->getOpenedAtKey($serviceName));
        
        if ($openedAt && now()->timestamp - $openedAt >= self::TIMEOUT) {
            $this->transitionTo($serviceName, self::STATE_HALF_OPEN);
            Cache::forget($this->getSuccessCountKey($serviceName));
            
            Log::info("Circuit breaker transitioned to HALF_OPEN for {$serviceName}");
        }
    }

    /**
     * Transition to a new state
     */
    private function transitionTo(string $serviceName, string $newState): void
    {
        $key = $this->getStateKey($serviceName);
        Cache::forever($key, $newState);
    }

    /**
     * Record when circuit was opened
     */
    private function recordOpenedAt(string $serviceName): void
    {
        $key = $this->getOpenedAtKey($serviceName);
        Cache::forever($key, now()->timestamp);
    }

    /**
     * Get failure count
     */
    private function getFailureCount(string $serviceName): int
    {
        return (int) Cache::get($this->getFailureCountKey($serviceName), 0);
    }

    /**
     * Get success count
     */
    private function getSuccessCount(string $serviceName): int
    {
        return (int) Cache::get($this->getSuccessCountKey($serviceName), 0);
    }

    // Cache key helpers
    private function getStateKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:state";
    }

    private function getFailureCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:failures";
    }

    private function getSuccessCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:successes";
    }

    private function getOpenedAtKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:opened_at";
    }

    private function getLastFailureKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:last_failure";
    }

    private function getLastSuccessKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:last_success";
    }
}
