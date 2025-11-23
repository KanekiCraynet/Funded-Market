<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'phone' => $request->input('phone'),
                'preferences' => [
                    'risk_level' => $request->input('risk_level', 'MEDIUM'),
                    'time_horizon' => $request->input('time_horizon', 'medium_term'),
                    'max_position_size' => $request->input('max_position_size', 15),
                    'notifications' => $request->input('notifications', true),
                ],
            ]);

            // Create API token with standard user abilities
            $tokenResult = $user->createApiToken('web-app');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'email_verified' => $user->email_verified,
                        'preferences' => $user->preferences,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // Rate limiting
        $rateLimitKey = 'auth:login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, 900); // 15 minutes

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user();
            
            if (!$user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated.'],
                ]);
            }

            // Revoke existing tokens
            $user->tokens()->delete();

            // Create new token with standard user abilities
            $tokenResult = $user->createApiToken('web-app');
            $token = $tokenResult->plainTextToken;

            // Clear rate limit on successful login
            RateLimiter::clear($rateLimitKey);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'email_verified' => $user->email_verified,
                        'preferences' => $user->preferences,
                        'analysis_count' => $user->analysis_count,
                        'last_analysis_at' => $user->last_analysis_at?->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['analyses' => function ($query) {
                $query->latest()->limit(5);
            }]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'email_verified' => $user->email_verified,
                        'preferences' => $user->preferences,
                        'analysis_count' => $user->analysis_count,
                        'last_analysis_at' => $user->last_analysis_at?->toISOString(),
                    ],
                    'recent_analyses' => $user->analyses->map(function ($analysis) {
                        return [
                            'id' => $analysis->id,
                            'symbol' => $analysis->instrument->symbol,
                            'recommendation' => $analysis->recommendation,
                            'final_score' => $analysis->final_score,
                            'confidence' => $analysis->confidence,
                            'created_at' => $analysis->created_at->toISOString(),
                        ];
                    }),
                    'subscription' => [
                        'plan' => 'free', // Would be determined by subscription system
                        'analyses_remaining' => $this->getRemainingAnalyses($user),
                        'reset_date' => now()->endOfDay()->toISOString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $user->currentAccessToken()->delete();

            // Create new token with standard user abilities
            $tokenResult = $user->createApiToken('web-app');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
                'preferences' => 'sometimes|array',
                'preferences.risk_level' => 'sometimes|in:LOW,MEDIUM,HIGH',
                'preferences.time_horizon' => 'sometimes|in:short_term,medium_term,long_term',
                'preferences.max_position_size' => 'sometimes|numeric|between:1,50',
                'preferences.notifications' => 'sometimes|boolean',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'preferences' => $user->preferences,
                        'updated_at' => $user->updated_at->toISOString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function getRemainingAnalyses(User $user): int
    {
        // For free tier, limit to 10 analyses per day
        $dailyLimit = 10;
        $usedToday = $user->analyses()
            ->whereDate('created_at', today())
            ->count();
        
        return max(0, $dailyLimit - $usedToday);
    }
}