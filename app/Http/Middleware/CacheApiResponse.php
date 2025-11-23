<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Response Caching Middleware
 * 
 * Caches GET request responses to improve performance and reduce server load.
 * 
 * Features:
 * - Intelligent TTL based on endpoint type
 * - Cache key includes URL, query params, user context
 * - Bypass mechanisms (no-cache header, query param)
 * - Performance metrics tracking
 * - Automatic cache headers
 */
class CacheApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Check if caching is enabled
        if (!config('api-cache.enabled', true)) {
            return $next($request);
        }

        // Check if endpoint is excluded
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        // Check bypass mechanisms
        if ($this->shouldBypass($request)) {
            Log::debug('API cache bypassed', ['url' => $request->fullUrl()]);
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            
            // Track cache hit
            $this->trackMetrics('hit', $request);
            
            if (config('api-cache.monitoring.log_hits', false)) {
                Log::debug('API cache HIT', [
                    'url' => $request->fullUrl(),
                    'key' => $cacheKey,
                ]);
            }
            
            // Return cached response with headers
            return $this->createCachedResponse($cachedData);
        }

        // Track cache miss
        $this->trackMetrics('miss', $request);
        
        if (config('api-cache.monitoring.log_misses', true)) {
            Log::debug('API cache MISS', [
                'url' => $request->fullUrl(),
                'key' => $cacheKey,
            ]);
        }

        // Get fresh response
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            $ttl = $this->getTTL($request);
            
            // Store response data
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $this->getCacheableHeaders($response),
                'cached_at' => now()->toIso8601String(),
            ];
            
            Cache::put($cacheKey, $cacheData, $ttl);
            
            // Add cache headers
            $response = $this->addCacheHeaders($response, $ttl, false);
        }

        return $response;
    }

    /**
     * Check if endpoint should be excluded from caching
     */
    protected function isExcluded(Request $request): bool
    {
        $excluded = config('api-cache.excluded', []);
        $path = $request->path();

        foreach ($excluded as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if cache should be bypassed
     */
    protected function shouldBypass(Request $request): bool
    {
        // Check query parameters
        $bypassParams = config('api-cache.bypass.query_params', []);
        foreach ($bypassParams as $param) {
            if ($request->has($param)) {
                return true;
            }
        }

        // Check headers
        $bypassHeaders = config('api-cache.bypass.headers', []);
        foreach ($bypassHeaders as $header => $value) {
            if ($request->header($header) === $value) {
                return true;
            }
        }

        // Check if user is admin (if enabled)
        if (config('api-cache.bypass.admin_bypass', true)) {
            $user = $request->user();
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate cache key for request
     */
    protected function generateCacheKey(Request $request): string
    {
        $config = config('api-cache.key', []);
        $prefix = $config['prefix'] ?? 'api_response:';
        
        $parts = [
            $request->path(),
        ];

        // Include query parameters if configured
        if ($config['include_query_params'] ?? true) {
            $query = $request->query();
            ksort($query);
            $parts[] = http_build_query($query);
        }

        // Include user ID if configured and authenticated
        if ($config['include_user'] ?? true) {
            $user = $request->user();
            if ($user) {
                $parts[] = 'user:' . $user->id;
            }
        }

        // Include specific headers if configured
        if (isset($config['include_headers'])) {
            foreach ($config['include_headers'] as $header) {
                if ($value = $request->header($header)) {
                    $parts[] = strtolower($header) . ':' . $value;
                }
            }
        }

        return $prefix . md5(implode('|', $parts));
    }

    /**
     * Get TTL for request based on endpoint type
     */
    protected function getTTL(Request $request): int
    {
        $path = $request->path();
        $patterns = config('api-cache.patterns', []);
        $ttls = config('api-cache.ttl', []);

        // Find matching pattern
        foreach ($patterns as $type => $typePatterns) {
            foreach ($typePatterns as $pattern) {
                if ($this->matchesPattern($path, $pattern)) {
                    return $ttls[$type] ?? $ttls['default'] ?? 300;
                }
            }
        }

        return $ttls['default'] ?? 300;
    }

    /**
     * Check if path matches pattern
     */
    protected function matchesPattern(string $path, string $pattern): bool
    {
        // Normalize path
        $path = '/' . trim($path, '/');
        
        // Convert pattern to regex
        // Replace * with wildcard pattern before escaping
        $pattern = str_replace('*', '__WILDCARD__', $pattern);
        $regex = preg_quote($pattern, '#');
        $regex = str_replace('__WILDCARD__', '[^/]+', $regex);
        
        // Normalize regex
        $regex = '#^/' . trim($regex, '/') . '$#i';
        
        return preg_match($regex, $path) === 1;
    }

    /**
     * Create response from cached data
     */
    protected function createCachedResponse(array $cachedData): Response
    {
        $response = response($cachedData['content'], $cachedData['status']);

        // Restore cached headers
        if (isset($cachedData['headers'])) {
            foreach ($cachedData['headers'] as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        // Add cache indicators
        $response->headers->set('X-Cache', 'HIT');
        $response->headers->set('X-Cache-Date', $cachedData['cached_at']);

        // Add cache-control headers
        $ttl = config('api-cache.ttl.default', 300);
        $response = $this->addCacheHeaders($response, $ttl, true);

        return $response;
    }

    /**
     * Get headers that should be cached
     */
    protected function getCacheableHeaders(Response $response): array
    {
        $cacheable = ['Content-Type', 'Content-Encoding'];
        $headers = [];

        foreach ($cacheable as $header) {
            if ($value = $response->headers->get($header)) {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Add cache control headers to response
     */
    protected function addCacheHeaders(Response $response, int $ttl, bool $isCached): Response
    {
        if (!config('api-cache.headers.enabled', true)) {
            return $response;
        }

        $directives = [];

        // Public/private
        $isPublic = config('api-cache.headers.public', false);
        $directives[] = $isPublic ? 'public' : 'private';

        // Max age
        $directives[] = 'max-age=' . $ttl;

        // Must revalidate
        if (config('api-cache.headers.must_revalidate', true)) {
            $directives[] = 'must-revalidate';
        }

        $response->headers->set('Cache-Control', implode(', ', $directives));
        $response->headers->set('X-Cache-Status', $isCached ? 'HIT' : 'MISS');

        return $response;
    }

    /**
     * Track cache metrics
     */
    protected function trackMetrics(string $type, Request $request): void
    {
        if (!config('api-cache.monitoring.enabled', true)) {
            return;
        }

        $metricsKey = 'api_cache:metrics:' . now()->format('Y-m-d-H');
        $ttl = config('api-cache.monitoring.metrics_ttl', 3600);

        // Increment counter
        Cache::remember($metricsKey, $ttl, fn() => ['hits' => 0, 'misses' => 0]);
        
        $metrics = Cache::get($metricsKey);
        $metrics[$type . 's'] = ($metrics[$type . 's'] ?? 0) + 1;
        
        Cache::put($metricsKey, $metrics, $ttl);
    }
}
