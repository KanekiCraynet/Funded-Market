<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @deprecated Use SanctumApiAuthentication instead
 * 
 * This middleware is kept for backward compatibility.
 * Migrate to 'sanctum.api' middleware for better features.
 */
class SimpleTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated - No token provided.',
                'data' => null
            ], 401);
        }

        try {
            // Simple token lookup without complex middleware
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken || !$accessToken->tokenable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - Invalid token.',
                    'data' => null
                ], 401);
            }

            // Set the authenticated user
            $request->setUserResolver(function () use ($accessToken) {
                return $accessToken->tokenable;
            });

            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('Token auth error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication error.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'data' => null
            ], 500);
        }
    }
}
