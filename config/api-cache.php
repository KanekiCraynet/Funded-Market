<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Response Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching strategy for API responses to improve performance.
    | Different endpoint types have different cache TTLs based on data volatility.
    |
    */

    'enabled' => env('API_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL by Endpoint Type
    |--------------------------------------------------------------------------
    |
    | Define how long responses should be cached for different endpoint types.
    | Values are in seconds.
    |
    */
    'ttl' => [
        // Market data changes frequently
        'market' => env('API_CACHE_TTL_MARKET', 300),  // 5 minutes
        
        // Analysis results are expensive to generate, cache longer
        'analysis' => env('API_CACHE_TTL_ANALYSIS', 3600),  // 1 hour
        
        // User-specific data needs shorter cache
        'user' => env('API_CACHE_TTL_USER', 60),  // 1 minute
        
        // Static/reference data can be cached longer
        'static' => env('API_CACHE_TTL_STATIC', 86400),  // 24 hours
        
        // Quant calculations are expensive
        'quant' => env('API_CACHE_TTL_QUANT', 600),  // 10 minutes
        
        // Sentiment data from external APIs
        'sentiment' => env('API_CACHE_TTL_SENTIMENT', 900),  // 15 minutes
        
        // Default for unclassified endpoints
        'default' => env('API_CACHE_TTL_DEFAULT', 300),  // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Type Patterns
    |--------------------------------------------------------------------------
    |
    | Map URL patterns to endpoint types for automatic TTL selection.
    |
    */
    'patterns' => [
        'market' => [
            '/api/*/market/*',
        ],
        'analysis' => [
            '/api/*/analysis/*',
        ],
        'quant' => [
            '/api/*/quant/*',
        ],
        'sentiment' => [
            '/api/*/sentiment/*',
        ],
        'user' => [
            '/api/*/auth/user',
            '/api/*/auth/profile',
        ],
        'static' => [
            '/api/*/instruments',
            '/api/*/markets',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Control Headers
    |--------------------------------------------------------------------------
    |
    | Add Cache-Control headers to cached responses.
    |
    */
    'headers' => [
        'enabled' => true,
        'public' => false,  // Set to true for public caching (CDN)
        'must_revalidate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how cache keys are generated.
    |
    */
    'key' => [
        'prefix' => env('API_CACHE_PREFIX', 'api_response:'),
        'include_query_params' => true,
        'include_headers' => ['Accept', 'Accept-Language'],
        'include_user' => true,  // Include user ID in key for user-specific data
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Mechanisms
    |--------------------------------------------------------------------------
    |
    | Configure ways to bypass the cache.
    |
    */
    'bypass' => [
        // Bypass cache when these query parameters are present
        'query_params' => ['no-cache', 'refresh', 'bypass-cache'],
        
        // Bypass cache when these headers are present
        'headers' => ['Cache-Control' => 'no-cache'],
        
        // Bypass cache for authenticated admin users
        'admin_bypass' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Endpoints
    |--------------------------------------------------------------------------
    |
    | Endpoints that should never be cached (e.g., mutations, sensitive data).
    |
    */
    'excluded' => [
        '/api/*/auth/login',
        '/api/*/auth/register',
        '/api/*/auth/logout',
        '/api/*/auth/refresh',
        // POST/PUT/DELETE requests are excluded by default in middleware
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Configure automatic cache warming for frequently accessed endpoints.
    |
    */
    'warming' => [
        'enabled' => env('API_CACHE_WARMING_ENABLED', false),
        'schedule' => '*/5 * * * *',  // Every 5 minutes
        'endpoints' => [
            '/api/v1/market/overview',
            '/api/v1/market/tickers',
            // Add popular endpoints here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation
    |--------------------------------------------------------------------------
    |
    | Configure automatic cache invalidation on data updates.
    |
    */
    'invalidation' => [
        'enabled' => true,
        
        // Invalidate cache when these events occur
        'events' => [
            'analysis.created' => ['analysis/*'],
            'analysis.updated' => ['analysis/*'],
            'market.updated' => ['market/*'],
            'instrument.updated' => ['market/*', 'instruments'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Track cache performance metrics.
    |
    */
    'monitoring' => [
        'enabled' => true,
        'log_hits' => env('API_CACHE_LOG_HITS', false),
        'log_misses' => env('API_CACHE_LOG_MISSES', true),
        'metrics_ttl' => 3600,  // Keep metrics for 1 hour
    ],
];
