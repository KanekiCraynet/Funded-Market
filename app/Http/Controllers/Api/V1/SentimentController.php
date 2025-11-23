<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Sentiment\Services\SentimentEngine;
use App\Domain\Market\Services\InstrumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SentimentController - Sentiment Analysis Endpoints
 * 
 * Provides sentiment analysis and news aggregation
 */
class SentimentController extends Controller
{
    public function __construct(
        private SentimentEngine $sentimentEngine,
        private InstrumentService $instrumentService
    ) {
        // Rate limiting applied in routes
    }

    /**
     * GET /api/v1/sentiment/{symbol}
     * 
     * Get sentiment analysis for a symbol
     */
    public function show(string $symbol, Request $request): JsonResponse
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
            // Get sentiment analysis
            $sentiment = $this->sentimentEngine->analyzeSentiment($symbol);

            // Determine overall classification
            $overallScore = $sentiment['overall_score'] ?? 0;
            $classification = $this->classifySentiment($overallScore);
            
            // Determine trend
            $trend = $sentiment['trend'] ?? 'neutral';

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => strtoupper($symbol),
                    'timestamp' => now()->toIso8601String(),
                    'sentiment' => [
                        'overall_score' => round($overallScore, 4),
                        'classification' => $classification,
                        'confidence' => round($sentiment['confidence'] ?? 0, 4),
                        'sources' => [
                            'news' => round($sentiment['news_sentiment']['score'] ?? 0, 4),
                            'social' => round($sentiment['social_sentiment']['score'] ?? 0, 4),
                            'analyst' => round($sentiment['analyst_sentiment']['score'] ?? 0, 4),
                        ],
                        'source_counts' => [
                            'news_articles' => $sentiment['sources']['news_count'] ?? 0,
                            'social_mentions' => $sentiment['sources']['social_mentions'] ?? 0,
                            'analyst_ratings' => $sentiment['sources']['analyst_ratings'] ?? 0,
                        ],
                        'trend' => $trend,
                        'updated_at' => now()->toIso8601String(),
                    ],
                    'breakdown' => [
                        'news' => $sentiment['news_sentiment'] ?? null,
                        'social' => $sentiment['social_sentiment'] ?? null,
                        'analyst' => $sentiment['analyst_sentiment'] ?? null,
                    ],
                ],
                'meta' => [
                    'cached' => Cache::has("sentiment_analysis:{$symbol}"),
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze sentiment', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze sentiment',
                'error' => 'calculation_error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * GET /api/v1/sentiment/{symbol}/news
     * 
     * Get news sentiment for a symbol
     */
    public function news(string $symbol, Request $request): JsonResponse
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
            // Get limit from request (default: 20)
            $limit = min(max((int)$request->input('limit', 20), 1), 100);

            // Get news with sentiment
            $newsData = $this->fetchNewsWithSentiment($symbol, $limit);

            // Calculate aggregate sentiment
            $aggregateSentiment = $this->calculateAggregateSentiment($newsData);

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => strtoupper($symbol),
                    'timestamp' => now()->toIso8601String(),
                    'news_items' => $newsData,
                    'aggregate' => [
                        'sentiment_score' => round($aggregateSentiment, 4),
                        'classification' => $this->classifySentiment($aggregateSentiment),
                        'total_articles' => count($newsData),
                        'positive_count' => count(array_filter($newsData, fn($n) => $n['sentiment_score'] > 0.2)),
                        'negative_count' => count(array_filter($newsData, fn($n) => $n['sentiment_score'] < -0.2)),
                        'neutral_count' => count(array_filter($newsData, fn($n) => abs($n['sentiment_score']) <= 0.2)),
                    ],
                ],
                'meta' => [
                    'limit' => $limit,
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch news sentiment', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news sentiment',
                'error' => 'fetch_error',
                'data' => null,
            ], 500);
        }
    }

    /**
     * Classify sentiment score
     */
    private function classifySentiment(float $score): string
    {
        if ($score > 0.6) return 'very_bullish';
        if ($score > 0.2) return 'bullish';
        if ($score > -0.2) return 'neutral';
        if ($score > -0.6) return 'bearish';
        return 'very_bearish';
    }

    /**
     * Fetch news with sentiment analysis
     */
    private function fetchNewsWithSentiment(string $symbol, int $limit): array
    {
        $cacheKey = "news_sentiment:{$symbol}:{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($symbol, $limit) {
            try {
                // Get news from SentimentEngine
                $sentiment = $this->sentimentEngine->analyzeSentiment($symbol);
                $evidence = $sentiment['evidence'] ?? [];

                // Format news items
                $newsItems = [];
                foreach ($evidence as $item) {
                    if (count($newsItems) >= $limit) break;

                    $newsItems[] = [
                        'title' => $item['title'] ?? '',
                        'description' => $item['description'] ?? '',
                        'source' => $item['source'] ?? 'Unknown',
                        'url' => $item['url'] ?? null,
                        'sentiment_score' => round($item['sentiment_score'] ?? 0, 4),
                        'sentiment_label' => $this->classifySentiment($item['sentiment_score'] ?? 0),
                        'published_at' => $item['published_at'] ?? now()->toIso8601String(),
                    ];
                }

                return $newsItems;

            } catch (\Exception $e) {
                Log::error('Error fetching news with sentiment', [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    /**
     * Calculate aggregate sentiment from news items
     */
    private function calculateAggregateSentiment(array $newsItems): float
    {
        if (empty($newsItems)) {
            return 0.0;
        }

        $total = 0.0;
        $count = 0;

        foreach ($newsItems as $item) {
            if (isset($item['sentiment_score'])) {
                $total += $item['sentiment_score'];
                $count++;
            }
        }

        return $count > 0 ? $total / $count : 0.0;
    }
}
