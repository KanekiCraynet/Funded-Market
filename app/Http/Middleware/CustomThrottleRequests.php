<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom Rate Limiting Middleware
 * 
 * Provides enhanced rate limiting with:
 * - Per-user limits
 * - Per-IP limits
 * - Per-endpoint limits
 * - Custom responses
 * - Rate limit headers
 * - Logging of limit violations
 */
class CustomThrottleRequests
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts  Maximum attempts per decay period
     * @param  int  $decayMinutes  Decay period in minutes
     * @param  string  $prefix  Key prefix for identification
     * @return mixed
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1, string $prefix = 'api')
    {
        $key = $this->resolveRequestSignature($request, $prefix);

        // Check if limit exceeded
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($request, $key, $maxAttempts);
        }

        // Increment attempts
        $this->limiter->hit($key, $decayMinutes * 60);

        // Process request
        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, string $prefix): string
    {
        // Use user ID if authenticated, otherwise use IP
        if ($user = $request->user()) {
            return sprintf(
                '%s|user:%s|%s',
                $prefix,
                $user->id,
                $request->path()
            );
        }

        return sprintf(
            '%s|ip:%s|%s',
            $prefix,
            $request->ip(),
            $request->path()
        );
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = $this->limiter->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(Request $request, string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        // Log rate limit violation
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
            'method' => $request->method(),
            'key' => $key,
            'retry_after' => $retryAfter,
        ]);

        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'error' => 'rate_limit_exceeded',
            'retry_after' => $retryAfter,
            'data' => null,
        ], 429);

        return $this->addRateLimitHeaders($response, $maxAttempts, 0, $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts,
        ?int $retryAfter = null
    ): Response {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        if ($retryAfter !== null) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }

        return $response;
    }
}
