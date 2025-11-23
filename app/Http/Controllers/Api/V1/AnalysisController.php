<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\LLM\Services\LLMOrchestrator;
use App\Domain\RateLimiter\Services\RateLimiterService;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Market\Services\InstrumentService;
use App\Http\Requests\Api\V1\GenerateAnalysisRequest;
use App\Http\Resources\Api\V1\AnalysisResource;
use App\Http\Resources\Api\V1\AnalysisCollection;
use App\Domain\History\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    public function __construct(
        private LLMOrchestrator $llmOrchestrator,
        private RateLimiterService $rateLimiter,
        private AuditService $audit,
        private InstrumentService $instrumentService
    ) {
        // Middleware is applied in routes/api.php
    }

    public function generate(GenerateAnalysisRequest $request): JsonResponse
    {
        $user = Auth::user();
        $symbol = strtoupper($request->input('symbol'));
        
        // Check if instrument exists (using cached service)
        $instrument = $this->instrumentService->findActiveBySymbol($symbol);
        
        if (!$instrument) {
            return response()->json([
                'success' => false,
                'message' => "Symbol '{$symbol}' is not supported or not found in our database.",
                'error' => 'symbol_not_found',
                'data' => null,
            ], 404);
        }
        
        $rateLimitKey = "user:{$user->id}:analysis:{$symbol}";

        // Check rate limit using Redis-based rate limiter
        $rateLimit = $this->rateLimiter->attempt($rateLimitKey, 60);
        
        if ($rateLimit->isDenied()) {
            // Log rate limit violation
            $this->audit->logRateLimitViolation(
                $user->id,
                "/api/v1/analysis/generate",
                $rateLimit->retryAfter
            );
            
            return response()->json([
                'success' => false,
                'message' => "Please wait {$rateLimit->retryAfter} seconds before generating another analysis for {$symbol}.",
                'retry_after' => $rateLimit->retryAfter,
                'error' => 'rate_limit_exceeded',
            ], 429);
        }

        $startTime = microtime(true);

        try {
            // Log user action
            $this->audit->logUserAction($user->id, 'analysis_generation_started', [
                'symbol' => $symbol,
            ]);

            // Generate the analysis
            $analysis = $this->llmOrchestrator->generateAnalysis($symbol, $user->id);
            
            $duration = microtime(true) - $startTime;
            
            // Log successful LLM request (will be done in LLMOrchestrator)
            // But we can log user action here
            $this->audit->logUserAction($user->id, 'analysis_generation_completed', [
                'symbol' => $symbol,
                'analysis_id' => $analysis->id,
                'duration_seconds' => round($duration, 3),
            ]);

            return response()->json([
                'success' => true,
                'data' => new AnalysisResource($analysis),
                'message' => 'Analysis generated successfully',
                'rate_limit_reset' => 60,
            ]);

        } catch (\Exception $e) {
            // Release rate limit on failure
            $this->rateLimiter->reset($rateLimitKey);
            
            // Log error
            $this->audit->logError('analysis_generation_failed', $e, 'error', $user->id);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analysis',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Analysis::where('user_id', $user->id)
            ->with('instrument')
            ->orderBy('created_at', 'desc');

        // Filter by symbol if provided
        if ($request->has('symbol')) {
            $query->whereHas('instrument', function ($q) use ($request) {
                $q->where('symbol', strtoupper($request->input('symbol')));
            });
        }

        // Filter by recommendation if provided
        if ($request->has('recommendation')) {
            $query->where('recommendation', $request->input('recommendation'));
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Pagination
        $perPage = min($request->input('per_page', 15), 50); // Max 50 per page
        $analyses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new AnalysisCollection($analyses),
            'pagination' => [
                'current_page' => $analyses->currentPage(),
                'last_page' => $analyses->lastPage(),
                'per_page' => $analyses->perPage(),
                'total' => $analyses->total(),
                'from' => $analyses->firstItem(),
                'to' => $analyses->lastItem(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $analysis = Analysis::where('id', $id)
            ->where('user_id', $user->id)
            ->with('instrument')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new AnalysisResource($analysis),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $analysis = Analysis::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $analysis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Analysis deleted successfully',
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Analysis::where('user_id', $user->id);

        // Overall statistics
        $totalAnalyses = $query->count();
        $avgConfidence = $query->avg('confidence');
        $avgScore = $query->avg('final_score');

        // Recommendation distribution
        $recommendationStats = Analysis::where('user_id', $user->id)
            ->selectRaw('recommendation, COUNT(*) as count')
            ->groupBy('recommendation')
            ->pluck('count', 'recommendation')
            ->toArray();

        // Risk level distribution
        $riskStats = Analysis::where('user_id', $user->id)
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        // Most analyzed symbols
        $topSymbols = Analysis::where('user_id', $user->id)
            ->join('instruments', 'analyses.instrument_id', '=', 'instruments.id')
            ->selectRaw('instruments.symbol, COUNT(*) as count')
            ->groupBy('instruments.symbol')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Recent performance (last 30 days)
        // OPTIMIZED: Select only needed columns (Phase 3 - Task 4)
        $recentAnalyses = Analysis::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(['id', 'final_score', 'recommendation', 'confidence', 'created_at'])
            ->get();

        $recentStats = [
            'count' => $recentAnalyses->count(),
            'avg_confidence' => $recentAnalyses->avg('confidence'),
            'avg_score' => $recentAnalyses->avg('final_score'),
            'buy_signals' => $recentAnalyses->where('recommendation', 'BUY')->count(),
            'sell_signals' => $recentAnalyses->where('recommendation', 'SELL')->count(),
            'hold_signals' => $recentAnalyses->where('recommendation', 'HOLD')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'overall' => [
                    'total_analyses' => $totalAnalyses,
                    'average_confidence' => round($avgConfidence, 3),
                    'average_score' => round($avgScore, 3),
                ],
                'recommendation_distribution' => $recommendationStats,
                'risk_level_distribution' => $riskStats,
                'top_analyzed_symbols' => $topSymbols,
                'recent_performance' => $recentStats,
            ],
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Analysis::where('user_id', $user->id)
            ->with('instrument');

        // Apply same filters as history method
        if ($request->has('symbol')) {
            $query->whereHas('instrument', function ($q) use ($request) {
                $q->where('symbol', strtoupper($request->input('symbol')));
            });
        }

        if ($request->has('recommendation')) {
            $query->where('recommendation', $request->input('recommendation'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // OPTIMIZED: Use chunking for large datasets (Phase 3 - Task 4)
        // Limit export size for performance
        $limit = min($request->input('limit', 1000), 5000);
        
        $exportData = [];
        
        // Use chunk() to avoid memory issues with large datasets
        $query->limit($limit)->chunk(200, function ($analyses) use (&$exportData) {
            foreach ($analyses as $analysis) {
                $exportData[] = [
                    'id' => $analysis->id,
                    'symbol' => $analysis->instrument->symbol,
                    'recommendation' => $analysis->recommendation,
                    'final_score' => $analysis->final_score,
                'confidence' => $analysis->confidence,
                'risk_level' => $analysis->risk_level,
                'time_horizon' => $analysis->time_horizon,
                'position_size_percent' => $analysis->position_size_recommendation['size_percent'] ?? null,
                'near_term_target' => $analysis->price_targets['near_term'] ?? null,
                'stop_loss' => $analysis->price_targets['stop_loss'] ?? null,
                    'created_at' => $analysis->created_at->toISOString(),
                ];
            }
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'meta' => [
                'exported_count' => $exportData->count(),
                'export_date' => now()->toISOString(),
                'filters_applied' => $request->only(['symbol', 'recommendation', 'date_from', 'date_to']),
            ],
        ]);
    }
}