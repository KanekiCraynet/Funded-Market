<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security headers for HTTP responses.
    |
    */

    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    */
    'csp' => [
        'enabled' => true,
        
        // API endpoints (strict policy)
        'api' => [
            'default-src' => "'none'",
            'frame-ancestors' => "'none'",
            'base-uri' => "'none'",
        ],
        
        // Web pages (more permissive for frontend)
        'web' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval'", // For Laravel Mix/Vite
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' data:",
            'connect-src' => "'self'",
            'frame-src' => "'none'",
            'frame-ancestors' => "'none'",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
            'upgrade-insecure-requests' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict-Transport-Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | Forces HTTPS connections for specified duration
    */
    'hsts' => [
        'enabled' => true,
        'max-age' => 31536000, // 1 year in seconds
        'include-subdomains' => true,
        'preload' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | X-Frame-Options
    |--------------------------------------------------------------------------
    |
    | Prevents clickjacking attacks
    | Options: DENY, SAMEORIGIN
    */
    'x-frame-options' => 'DENY',

    /*
    |--------------------------------------------------------------------------
    | X-Content-Type-Options
    |--------------------------------------------------------------------------
    |
    | Prevents MIME type sniffing
    */
    'x-content-type-options' => 'nosniff',

    /*
    |--------------------------------------------------------------------------
    | X-XSS-Protection
    |--------------------------------------------------------------------------
    |
    | Legacy XSS filter for older browsers
    | Modern browsers use CSP instead
    */
    'x-xss-protection' => [
        'enabled' => true,
        'mode' => 'block',
    ],

    /*
    |--------------------------------------------------------------------------
    | Referrer-Policy
    |--------------------------------------------------------------------------
    |
    | Controls referrer information sent with requests
    | Options: no-referrer, no-referrer-when-downgrade, origin,
    |          origin-when-cross-origin, same-origin,
    |          strict-origin, strict-origin-when-cross-origin, unsafe-url
    */
    'referrer-policy' => 'strict-origin-when-cross-origin',

    /*
    |--------------------------------------------------------------------------
    | Permissions-Policy
    |--------------------------------------------------------------------------
    |
    | Controls browser features that can be used
    */
    'permissions-policy' => [
        'accelerometer' => [],
        'ambient-light-sensor' => [],
        'autoplay' => [],
        'battery' => [],
        'camera' => [],
        'display-capture' => [],
        'document-domain' => [],
        'encrypted-media' => [],
        'execution-while-not-rendered' => [],
        'execution-while-out-of-viewport' => [],
        'fullscreen' => [],
        'geolocation' => [],
        'gyroscope' => [],
        'layout-animations' => [],
        'legacy-image-formats' => [],
        'magnetometer' => [],
        'microphone' => [],
        'midi' => [],
        'navigation-override' => [],
        'payment' => [],
        'picture-in-picture' => [],
        'publickey-credentials-get' => [],
        'speaker-selection' => [],
        'sync-xhr' => [],
        'usb' => [],
        'vr' => [],
        'wake-lock' => [],
        'web-share' => [],
        'xr-spatial-tracking' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Policies
    |--------------------------------------------------------------------------
    */
    'cross-origin' => [
        'embedder-policy' => 'require-corp',
        'opener-policy' => 'same-origin',
        'resource-policy' => 'same-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remove Headers
    |--------------------------------------------------------------------------
    |
    | Headers to remove (hide server information)
    */
    'remove-headers' => [
        'X-Powered-By',
        'Server',
    ],
];
