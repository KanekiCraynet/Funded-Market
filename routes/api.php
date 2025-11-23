<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Authentication routes - stricter rate limiting
    Route::middleware('throttle:5,1,auth')->group(function () {
        Route::post('/auth/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
        Route::post('/auth/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    });
    
    Route::post('/auth/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'throttle:10,1,auth']);
    Route::post('/auth/refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh'])
        ->middleware(['auth:sanctum', 'throttle:10,1,auth']);
    Route::get('/auth/user', [App\Http\Controllers\Api\V1\AuthController::class, 'user'])
        ->middleware(['auth:sanctum', 'throttle:30,1,auth']);
    Route::put('/auth/profile', [App\Http\Controllers\Api\V1\AuthController::class, 'updateProfile'])
        ->middleware(['auth:sanctum', 'throttle:10,1,auth']);

    // Protected routes - Using enhanced Sanctum middleware
    // Rate limiting: 60 requests/minute for authenticated users
    // API response caching + compression for GET requests (Phase 3)
    Route::middleware(['sanctum.api', 'throttle:60,1,api', 'cache.api', 'compress.response'])->group(function () {
        // Market overview
        Route::get('/market/overview', [App\Http\Controllers\Api\V1\MarketController::class, 'overview']);
        Route::get('/market/tickers', [App\Http\Controllers\Api\V1\MarketController::class, 'tickers']);
        Route::get('/market/instruments', [App\Http\Controllers\Api\V1\MarketController::class, 'instruments']);
        
        // Analysis generation - expensive operation, stricter limits
        Route::post('/analysis/generate', [App\Http\Controllers\Api\V1\AnalysisController::class, 'generate'])
            ->middleware('throttle:5,60,analysis'); // 5 per hour
        Route::get('/analysis/history', [App\Http\Controllers\Api\V1\AnalysisController::class, 'history']);
        Route::get('/analysis/stats', [App\Http\Controllers\Api\V1\AnalysisController::class, 'stats']);
        Route::get('/analysis/{id}', [App\Http\Controllers\Api\V1\AnalysisController::class, 'show']);
        
        // Quantitative data
        Route::get('/quant/{symbol}/indicators', [App\Http\Controllers\Api\V1\QuantController::class, 'indicators']);
        Route::get('/quant/{symbol}/trends', [App\Http\Controllers\Api\V1\QuantController::class, 'trends']);
        Route::get('/quant/{symbol}/volatility', [App\Http\Controllers\Api\V1\QuantController::class, 'volatility']);
        
        // Sentiment data
        Route::get('/sentiment/{symbol}', [App\Http\Controllers\Api\V1\SentimentController::class, 'show']);
        Route::get('/sentiment/{symbol}/news', [App\Http\Controllers\Api\V1\SentimentController::class, 'news']);
        
        // User favorites and settings - TODO: Implement these controllers
        // Route::get('/user/favorites', [App\Http\Controllers\Api\V1\UserController::class, 'favorites']);
        // Route::post('/user/favorites', [App\Http\Controllers\Api\V1\UserController::class, 'addFavorite']);
        // Route::delete('/user/favorites/{symbol}', [App\Http\Controllers\Api\V1\UserController::class, 'removeFavorite']);
        
        // Real-time data (WebSocket endpoints info) - TODO: Implement this controller
        // Route::get('/realtime/endpoints', [App\Http\Controllers\Api\V1\RealtimeController::class, 'endpoints']);
    });
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => config('app.env'),
    ]);
});

// TEMPORARY: Test endpoint without auth (for debugging PHP 8.4 compatibility)
Route::get('/test/market', function () {
    try {
        $service = app(\App\Domain\Market\Services\MarketDataService::class);
        $overview = $service->getMarketOverview();
        return response()->json([
            'success' => true,
            'data' => [
                'trending_count' => $overview['trending']->count(),
                'top_gainers_count' => $overview['top_gainers']->count(),
                'market_summary' => $overview['market_summary'],
            ],
            'note' => 'This is a test endpoint without authentication',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
});