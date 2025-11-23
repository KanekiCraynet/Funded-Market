<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register authentication middleware aliases
        $middleware->alias([
            // New: Enhanced Sanctum middleware (recommended)
            'sanctum.api' => \App\Http\Middleware\SanctumApiAuthentication::class,
            
            // Deprecated: Simple token auth (kept for backward compatibility)
            'simple.auth' => \App\Http\Middleware\SanitizeInput::class,
            
            // Input sanitization middleware
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            
            // Rate limiting middleware
            'throttle' => \App\Http\Middleware\CustomThrottleRequests::class,
            
            // API response caching middleware (Phase 3 - Task 2)
            'cache.api' => \App\Http\Middleware\CacheApiResponse::class,
            
            // Response compression middleware (Phase 3 - Task 5)
            'compress.response' => \App\Http\Middleware\CompressResponse::class,
        ]);
        
        // Configure API rate limiting
        $middleware->throttleApi();
        
        // Add security headers to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Prevent redirect to 'login' route for API requests
        $middleware->redirectGuestsTo(fn ($request) => 
            $request->expectsJson() ? null : route('login', [], false)
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data' => null
                ], 401);
            }
        });
    })->create();
