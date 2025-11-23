<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Enhanced Sanctum API Authentication Middleware
 * 
 * Provides better error messages and logging while using 
 * standard Sanctum authentication under the hood.
 */
class SanctumApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $ability  Optional token ability to check
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $ability = null)
    {
        $token = $request->bearerToken();
        
        // Check if token is provided
        if (!$token) {
            return $this->unauthorizedResponse('No authentication token provided');
        }

        try {
            // Find and validate the token
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                Log::warning('Invalid token attempted', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->path(),
                ]);
                
                return $this->unauthorizedResponse('Invalid or expired token');
            }

            // Check if token is expired
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                Log::info('Expired token used', [
                    'token_id' => $accessToken->id,
                    'user_id' => $accessToken->tokenable_id,
                    'expired_at' => $accessToken->expires_at,
                ]);
                
                return $this->unauthorizedResponse('Token has expired');
            }

            // Check token abilities/scopes if specified
            if ($ability && !$accessToken->can($ability)) {
                Log::warning('Insufficient token permissions', [
                    'token_id' => $accessToken->id,
                    'user_id' => $accessToken->tokenable_id,
                    'required_ability' => $ability,
                    'token_abilities' => $accessToken->abilities,
                ]);
                
                return $this->forbiddenResponse("Token lacks required permission: {$ability}");
            }

            // Get the user
            $user = $accessToken->tokenable;
            
            if (!$user) {
                return $this->unauthorizedResponse('User not found');
            }

            // Check if user is active
            if (method_exists($user, 'isActive') && !$user->isActive()) {
                Log::warning('Inactive user attempted access', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                
                return $this->forbiddenResponse('Account is inactive');
            }

            // Update last used timestamp
            $accessToken->forceFill(['last_used_at' => now()])->save();

            // Set the authenticated user
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            // Add token info to request for debugging
            if (config('app.debug')) {
                $request->attributes->set('sanctum_token_id', $accessToken->id);
                $request->attributes->set('sanctum_token_abilities', $accessToken->abilities);
            }

            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication system error',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'data' => null
            ], 500);
        }
    }

    /**
     * Return 401 Unauthorized response
     */
    private function unauthorizedResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'unauthorized',
            'data' => null
        ], 401);
    }

    /**
     * Return 403 Forbidden response
     */
    private function forbiddenResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'forbidden',
            'data' => null
        ], 403);
    }
}
