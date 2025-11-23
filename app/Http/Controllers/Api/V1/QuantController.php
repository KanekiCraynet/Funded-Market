<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Quant\Services\QuantEngine;
use App\Domain\Market\Services\InstrumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * QuantController - Quantitative Analysis Endpoints
 * 
 * Provides technical indicators, trend analysis, and volatility metrics
 */
class QuantController extends Controller
{
    public function __construct(
        private QuantEngine $quantEngine,
        private InstrumentService $instrumentService
    ) {
        // Rate limiting applied in routes
    }

    /**
     * GET /api/v1/quant/{symbol}/indicators
     * 
     * Get technical indicators for a symbol
     */
    public function indicators(string $symbol, Request $request): JsonResponse
    {
        // Validate symbol exists
        $instrument = $this->instrumentService->findActiveBySymbol($symbol);
        
        if (!$instrument) {
            return response()->json([
                'success' => false,
                'message' => "Symbol '{$symbol}' not found or inactive",
                'error' => 'symbol_not_found',
                'data' => null,
            ], 404);
        }

        try {
            // Get period from request (default: 200)
            $period = $request->input('period', 200);
            $period = min(max((int)$period, 50), 1000); // Clamp between 50-1000

            // Calculate indicators
            $indicators = $this->quantEngine->calculateIndicators($symbol, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => strtoupper($symbol),
                    'timestamp' => now()->toIso8601String(),
                    'period' => $period,
                    'indicators' => [
                        'trend' => $indicators['trend'] ?? [],
                        'momentum' => $indicators['momentum'] ?? [],
                        'volatility' => $indicators['volatility'] ?? [],
                        'volume' => $indicators['volume'] ?? [],
                    ],
                    'composite_score' => $indicators['composite'] ?? null,
                ],
                'meta' => [
                    'cached' => Cache::has("quant_indicators:{$symbol}:{$period}"),
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate indicators', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate indicators',
                'error' => 'calculation_error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * GET /api/v1/quant/{symbol}/trends
     * 
     * Get trend analysis for a symbol
     */
    public function trends(string $symbol, Request $request): JsonResponse
    {
        // Validate symbol
        $instrument = $this->instrumentService->findActiveBySymbol($symbol);
        
        if (!$instrument) {
            return response()->json([
                'success' => false,
                'message' => "Symbol '{$symbol}' not found or inactive",
                'error' => 'symbol_not_found',
                'data' => null,
            ], 404);
        }

        try {
            $period = min(max((int)$request->input('period', 200), 50), 1000);

            // Get indicators which include trend data
            $indicators = $this->quantEngine->calculateIndicators($symbol, $period);
            $trendData = $indicators['trend'] ?? [];

            // Determine trend direction and strength
            $direction = $trendData['direction'] ?? 'neutral';
            $strength = $trendData['trend_strength'] ?? 0;
            
            // Calculate support and resistance levels
            $supportResistance = $this->calculateSupportResistanceLevels($symbol, $period);

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => strtoupper($symbol),
                    'timestamp' => now()->toIso8601String(),
                    'period' => $period,
                    'trend' => [
                        'direction' => $direction,
                        'strength' => round($strength, 4),
                        'confidence' => $this->calculateTrendConfidence($trendData),
                        'classification' => $this->classifyTrend($direction, $strength),
                    ],
                    'levels' => [
                        'support' => $supportResistance['support'] ?? [],
                        'resistance' => $supportResistance['resistance'] ?? [],
                        'current_price' => $instrument->price,
                    ],
                    'moving_averages' => [
                        'ema_20' => $trendData['ema_20'] ?? null,
                        'ema_50' => $trendData['ema_50'] ?? null,
                        'sma_20' => $trendData['sma_20'] ?? null,
                        'sma_50' => $trendData['sma_50'] ?? null,
                    ],
                    'macd' => $trendData['macd'] ?? null,
                ],
                'meta' => [
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze trends', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze trends',
                'error' => 'calculation_error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * GET /api/v1/quant/{symbol}/volatility
     * 
     * Get volatility metrics for a symbol
     */
    public function volatility(string $symbol, Request $request): JsonResponse
    {
        // Validate symbol
        $instrument = $this->instrumentService->findActiveBySymbol($symbol);
        
        if (!$instrument) {
            return response()->json([
                'success' => false,
                'message' => "Symbol '{$symbol}' not found or inactive",
                'error' => 'symbol_not_found',
                'data' => null,
            ], 404);
        }

        try {
            $period = min(max((int)$request->input('period', 200), 50), 1000);

            // Get volatility indicators
            $indicators = $this->quantEngine->calculateIndicators($symbol, $period);
            $volData = $indicators['volatility'] ?? [];

            // Calculate historical volatilities
            $vol7d = $this->calculateHistoricalVolatility($symbol, 7);
            $vol30d = $this->calculateHistoricalVolatility($symbol, 30);
            $current = $volData['historical_volatility'] ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => strtoupper($symbol),
                    'timestamp' => now()->toIso8601String(),
                    'period' => $period,
                    'volatility' => [
                        'current' => round($current, 6),
                        'avg_7d' => round($vol7d, 6),
                        'avg_30d' => round($vol30d, 6),
                        'regime' => $volData['volatility_regime'] ?? 'normal',
                        'atr' => $volData['atr'] ?? null,
                        'percentile' => $this->calculateVolatilityPercentile($current, $vol30d),
                        'classification' => $this->classifyVolatility($current),
                    ],
                    'bollinger_bands' => $volData['bollinger_bands'] ?? null,
                    'volatility_ratio' => $volData['volatility_ratio'] ?? null,
                ],
                'meta' => [
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate volatility', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate volatility',
                'error' => 'calculation_error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * Calculate support and resistance levels
     */
    private function calculateSupportResistanceLevels(string $symbol, int $period = 100): array
    {
        // This is a simplified version
        // In production, you'd use more sophisticated algorithms
        
        $cacheKey = "support_resistance:{$symbol}:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($symbol) {
            $instrument = $this->instrumentService->findActiveBySymbol($symbol);
            
            if (!$instrument) {
                return ['support' => [], 'resistance' => []];
            }

            $currentPrice = $instrument->price;
            
            // Simple calculation based on price levels
            // In production, use pivot points, Fibonacci levels, etc.
            return [
                'support' => [
                    round($currentPrice * 0.95, 2),
                    round($currentPrice * 0.90, 2),
                    round($currentPrice * 0.85, 2),
                ],
                'resistance' => [
                    round($currentPrice * 1.05, 2),
                    round($currentPrice * 1.10, 2),
                    round($currentPrice * 1.15, 2),
                ],
            ];
        });
    }

    /**
     * Calculate trend confidence
     */
    private function calculateTrendConfidence(array $trendData): float
    {
        // Simple confidence calculation based on indicator alignment
        $confidence = 0.5; // Base confidence
        
        // Check EMA alignment
        if (isset($trendData['ema_20'], $trendData['ema_50'])) {
            if (abs($trendData['ema_20'] - $trendData['ema_50']) > 0) {
                $confidence += 0.2;
            }
        }
        
        // Check trend strength
        if (isset($trendData['trend_strength']) && $trendData['trend_strength'] > 0.5) {
            $confidence += 0.2;
        }
        
        // Check ADX
        if (isset($trendData['adx']) && $trendData['adx'] > 25) {
            $confidence += 0.1;
        }
        
        return round(min($confidence, 1.0), 2);
    }

    /**
     * Classify trend
     */
    private function classifyTrend(string $direction, float $strength): string
    {
        if ($strength < 0.3) {
            return 'weak_' . $direction;
        } elseif ($strength < 0.6) {
            return 'moderate_' . $direction;
        } else {
            return 'strong_' . $direction;
        }
    }

    /**
     * Calculate historical volatility for a given period
     */
    private function calculateHistoricalVolatility(string $symbol, int $days): float
    {
        $cacheKey = "historical_vol:{$symbol}:{$days}";
        
        return Cache::remember($cacheKey, 300, function () use ($symbol, $days) {
            // Get market data for the period
            $data = \App\Domain\Market\Models\MarketData::where('instrument_id', function($query) use ($symbol) {
                $query->select('id')
                    ->from('instruments')
                    ->where('symbol', strtoupper($symbol))
                    ->limit(1);
            })
            ->orderBy('timestamp', 'desc')
            ->limit($days)
            ->get();

            if ($data->count() < 2) {
                return 0.0;
            }

            // Calculate log returns
            $returns = [];
            for ($i = 1; $i < $data->count(); $i++) {
                $returns[] = log($data[$i-1]->close / $data[$i]->close);
            }

            // Calculate standard deviation (volatility)
            $mean = array_sum($returns) / count($returns);
            $variance = array_reduce($returns, function($carry, $return) use ($mean) {
                return $carry + pow($return - $mean, 2);
            }, 0) / count($returns);

            return sqrt($variance);
        });
    }

    /**
     * Calculate volatility percentile
     */
    private function calculateVolatilityPercentile(float $current, float $avg30d): int
    {
        if ($avg30d == 0) {
            return 50;
        }

        $ratio = $current / $avg30d;
        
        if ($ratio < 0.5) return 10;
        if ($ratio < 0.75) return 25;
        if ($ratio < 1.25) return 50;
        if ($ratio < 1.5) return 75;
        return 90;
    }

    /**
     * Classify volatility level
     */
    private function classifyVolatility(float $volatility): string
    {
        if ($volatility < 0.01) return 'very_low';
        if ($volatility < 0.02) return 'low';
        if ($volatility < 0.03) return 'moderate';
        if ($volatility < 0.05) return 'high';
        return 'very_high';
    }
}
