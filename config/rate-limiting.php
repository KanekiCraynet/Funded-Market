<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different user tiers and endpoints.
    |
    */

    'tiers' => [
        'anonymous' => [
            'requests_per_minute' => 10,
            'description' => 'Unauthenticated users (by IP)',
        ],
        'authenticated' => [
            'requests_per_minute' => 60,
            'description' => 'Authenticated users',
        ],
        'premium' => [
            'requests_per_minute' => 300,
            'description' => 'Premium users (future)',
        ],
    ],

    'endpoints' => [
        'auth' => [
            'login' => 5,           // 5 attempts per minute
            'register' => 5,        // 5 registrations per minute
            'refresh' => 10,        // 10 token refreshes per minute
            'profile' => 10,        // 10 profile updates per minute
        ],
        'analysis' => [
            'generate' => 5,        // 5 analysis per hour (60 minutes)
            'history' => 60,        // Standard rate
            'show' => 60,           // Standard rate
        ],
        'market' => [
            'overview' => 60,       // Standard rate
            'tickers' => 60,        // Standard rate
        ],
        'quant' => [
            'indicators' => 60,     // Standard rate
            'trends' => 60,         // Standard rate
            'volatility' => 60,     // Standard rate
        ],
        'sentiment' => [
            'show' => 30,           // 30 per minute (cached data)
            'news' => 30,           // 30 per minute (external API)
        ],
    ],

    'responses' => [
        'message' => 'Too many requests. Please slow down.',
        'error_code' => 'rate_limit_exceeded',
        'status_code' => 429,
    ],

    'headers' => [
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset' => 'X-RateLimit-Reset',
        'retry_after' => 'Retry-After',
    ],

    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'level' => 'warning',
    ],
];
