<?php

namespace App\Domain\Sentiment\Services;

use App\Domain\Market\Models\Instrument;
use App\Domain\Sentiment\Models\SentimentData;
use App\Services\ApiKeyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentEngine
{
    private const CACHE_TTL = 600; // 10 minutes
    private const NEWS_CACHE_TTL = 1800; // 30 minutes
    
    private ApiKeyService $apiKeyService;
    
    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    private array $positiveWords = [
        'bullish', 'uptrend', 'growth', 'rally', 'surge', 'boom', 'expansion',
        'strong', 'positive', 'optimistic', 'confident', 'breakout', 'momentum',
        'rallying', 'soaring', 'climbing', 'advancing', 'gaining', 'outperforming',
        'excellent', 'outstanding', 'remarkable', 'exceptional', 'favorable'
    ];

    private array $negativeWords = [
        'bearish', 'downtrend', 'decline', 'crash', 'slump', 'recession', 'contraction',
        'weak', 'negative', 'pessimistic', 'concern', 'concerning', 'worry', 'drop',
        'falling', 'declining', 'plunging', 'tumbling', 'collapsing', 'underperforming',
        'terrible', 'awful', 'disastrous', 'devastating', 'unfavorable'
    ];

    public function analyzeSentiment(string $symbol): array
    {
        $cacheKey = "sentiment_analysis:{$symbol}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            $instrument = Instrument::where('symbol', $symbol)->first();
            if (!$instrument) {
                return $this->getEmptySentiment();
            }

            $news = $this->fetchNews($symbol);
            $socialMedia = $this->fetchSocialMediaSentiment($symbol);
            $analystRatings = $this->fetchAnalystRatings($symbol);
            
            return [
                'overall_score' => $this->calculateOverallSentiment($news, $socialMedia, $analystRatings),
                'news_sentiment' => $this->analyzeNewsSentiment($news),
                'social_sentiment' => $this->analyzeSocialSentiment($socialMedia),
                'analyst_sentiment' => $this->analyzeAnalystSentiment($analystRatings),
                'evidence' => $this->extractEvidence($news, $socialMedia),
                'confidence' => $this->calculateSentimentConfidence($news, $socialMedia, $analystRatings),
                'trend' => $this->analyzeSentimentTrend($symbol),
                'sources' => [
                    'news_count' => count($news),
                    'social_mentions' => $socialMedia['mention_count'] ?? 0,
                    'analyst_ratings' => count($analystRatings),
                ],
            ];
        });
    }

    private function fetchNews(string $symbol): array
    {
        try {
            // Integration with news APIs (NewsAPI, Alpha Vantage News, etc.)
            $response = Http::timeout(15)->get('https://newsapi.org/v2/everything', [
                'q' => $symbol . ' OR ' . $this->getSymbolAliases($symbol),
                'language' => 'en',
                'sortBy' => 'publishedAt',
                'pageSize' => 20,
                'apiKey' => $this->apiKeyService->get('newsapi'),
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to fetch news for {$symbol}: " . $response->status());
                return [];
            }

            $articles = $response->json()['articles'] ?? [];
            
            return array_map(function ($article) {
                return [
                    'title' => $article['title'] ?? '',
                    'description' => $article['description'] ?? '',
                    'content' => $article['content'] ?? '',
                    'source' => $article['source']['name'] ?? 'Unknown',
                    'published_at' => $article['publishedAt'] ?? now(),
                    'url' => $article['url'] ?? '',
                    'sentiment_score' => 0, // Will be calculated
                ];
            }, $articles);

        } catch (\Exception $e) {
            Log::error("Error fetching news for {$symbol}: " . $e->getMessage());
            return [];
        }
    }

    private function fetchSocialMediaSentiment(string $symbol): array
    {
        // Mock implementation - would integrate with Twitter API, Reddit API, etc.
        return [
            'mention_count' => rand(100, 1000),
            'positive_mentions' => rand(30, 60),
            'negative_mentions' => rand(20, 40),
            'neutral_mentions' => rand(10, 30),
            'sentiment_score' => (rand(-30, 40)) / 100,
            'top_mentions' => [
                ['text' => "Bullish on {$symbol}! Technical analysis looks strong.", 'sentiment' => 0.7],
                ['text' => "{$symbol} showing signs of weakness, be careful.", 'sentiment' => -0.5],
                ['text' => "{$symbol} volatility expected this week.", 'sentiment' => 0.1],
            ],
        ];
    }

    private function fetchAnalystRatings(string $symbol): array
    {
        // Mock implementation - would integrate with financial data providers
        return [
            ['analyst' => 'Goldman Sachs', 'rating' => 'BUY', 'price_target' => 150.0, 'confidence' => 0.8],
            ['analyst' => 'Morgan Stanley', 'rating' => 'HOLD', 'price_target' => 135.0, 'confidence' => 0.6],
            ['analyst' => 'JP Morgan', 'rating' => 'BUY', 'price_target' => 145.0, 'confidence' => 0.7],
        ];
    }

    private function analyzeNewsSentiment(array $news): array
    {
        if (empty($news)) {
            return [
                'score' => 0,
                'confidence' => 0,
                'articles_analyzed' => 0,
                'positive_articles' => 0,
                'negative_articles' => 0,
                'neutral_articles' => 0,
            ];
        }

        $scores = [];
        $positiveCount = 0;
        $negativeCount = 0;
        $neutralCount = 0;

        foreach ($news as $article) {
            $text = strtolower($article['title'] . ' ' . $article['description'] . ' ' . $article['content']);
            $score = $this->calculateTextSentiment($text);
            $article['sentiment_score'] = $score;
            $scores[] = $score;

            if ($score > 0.1) {
                $positiveCount++;
            } elseif ($score < -0.1) {
                $negativeCount++;
            } else {
                $neutralCount++;
            }
        }

        $averageScore = array_sum($scores) / count($scores);
        
        // Calculate confidence based on score consistency
        $maxScore = max($scores);
        $minScore = min($scores);
        $absMaxScore = max(abs($maxScore), abs($minScore));
        $confidence = $absMaxScore > 0 ? 1 - (($maxScore - $minScore) / (2 * $absMaxScore)) : 0.5;

        return [
            'score' => $averageScore,
            'confidence' => $confidence,
            'articles_analyzed' => count($news),
            'positive_articles' => $positiveCount,
            'negative_articles' => $negativeCount,
            'neutral_articles' => $neutralCount,
            'score_distribution' => $this->calculateScoreDistribution($scores),
        ];
    }

    private function analyzeSocialSentiment(array $socialData): array
    {
        $total = $socialData['mention_count'];
        if ($total === 0) {
            return [
                'score' => 0,
                'confidence' => 0,
                'mention_count' => 0,
                'sentiment_distribution' => ['positive' => 0, 'negative' => 0, 'neutral' => 0],
            ];
        }

        return [
            'score' => $socialData['sentiment_score'],
            'confidence' => min(1, $total / 500), // Higher confidence with more mentions
            'mention_count' => $total,
            'sentiment_distribution' => [
                'positive' => $socialData['positive_mentions'] / $total,
                'negative' => $socialData['negative_mentions'] / $total,
                'neutral' => $socialData['neutral_mentions'] / $total,
            ],
            'engagement_rate' => $this->calculateEngagementRate($socialData),
        ];
    }

    private function analyzeAnalystSentiment(array $ratings): array
    {
        if (empty($ratings)) {
            return [
                'score' => 0,
                'confidence' => 0,
                'buy_ratings' => 0,
                'hold_ratings' => 0,
                'sell_ratings' => 0,
                'average_price_target' => 0,
            ];
        }

        $buyCount = 0;
        $holdCount = 0;
        $sellCount = 0;
        $totalConfidence = 0;
        $priceTargets = [];

        foreach ($ratings as $rating) {
            switch (strtoupper($rating['rating'])) {
                case 'BUY':
                case 'STRONG BUY':
                case 'OUTPERFORM':
                    $buyCount++;
                    break;
                case 'SELL':
                case 'STRONG SELL':
                case 'UNDERPERFORM':
                    $sellCount++;
                    break;
                case 'HOLD':
                case 'NEUTRAL':
                case 'MARKET PERFORM':
                    $holdCount++;
                    break;
            }
            
            $totalConfidence += $rating['confidence'] ?? 0.5;
            if (isset($rating['price_target'])) {
                $priceTargets[] = $rating['price_target'];
            }
        }

        $total = count($ratings);
        $score = (($buyCount - $sellCount) / $total) * ($totalConfidence / $total);
        $confidence = $totalConfidence / $total;

        return [
            'score' => $score,
            'confidence' => $confidence,
            'buy_ratings' => $buyCount,
            'hold_ratings' => $holdCount,
            'sell_ratings' => $sellCount,
            'average_price_target' => count($priceTargets) > 0 ? array_sum($priceTargets) / count($priceTargets) : 0,
            'price_target_range' => [
                'min' => count($priceTargets) > 0 ? min($priceTargets) : 0,
                'max' => count($priceTargets) > 0 ? max($priceTargets) : 0,
            ],
        ];
    }

    private function calculateOverallSentiment(array $news, array $socialMedia, array $analystRatings): float
    {
        $newsSentiment = $this->analyzeNewsSentiment($news);
        $socialSentiment = $this->analyzeSocialSentiment($socialMedia);
        $analystSentiment = $this->analyzeAnalystSentiment($analystRatings);

        // Dynamic weighting based on data availability and confidence
        $weights = $this->calculateSentimentWeights(
            $newsSentiment['confidence'],
            $socialSentiment['confidence'],
            $analystSentiment['confidence']
        );

        return (
            $weights['news'] * $newsSentiment['score'] +
            $weights['social'] * $socialSentiment['score'] +
            $weights['analyst'] * $analystSentiment['score']
        );
    }

    private function calculateSentimentWeights(float $newsConfidence, float $socialConfidence, float $analystConfidence): array
    {
        $totalConfidence = $newsConfidence + $socialConfidence + $analystConfidence;
        
        if ($totalConfidence === 0) {
            return ['news' => 0.4, 'social' => 0.3, 'analyst' => 0.3];
        }

        return [
            'news' => $newsConfidence / $totalConfidence,
            'social' => $socialConfidence / $totalConfidence,
            'analyst' => $analystConfidence / $totalConfidence,
        ];
    }

    private function calculateTextSentiment(string $text): float
    {
        $words = str_word_count($text, 1);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($words as $word) {
            if (in_array($word, $this->positiveWords)) {
                $positiveCount++;
            } elseif (in_array($word, $this->negativeWords)) {
                $negativeCount++;
            }
        }

        $totalSentimentWords = $positiveCount + $negativeCount;
        if ($totalSentimentWords === 0) {
            return 0;
        }

        return ($positiveCount - $negativeCount) / sqrt($totalSentimentWords);
    }

    private function extractEvidence(array $news, array $socialMedia): array
    {
        $evidence = [];

        // Extract top news evidence
        foreach (array_slice($news, 0, 3) as $article) {
            if (abs($article['sentiment_score']) > 0.2) {
                $evidence[] = [
                    'type' => 'news',
                    'text' => $article['title'],
                    'source' => $article['source'],
                    'sentiment' => $article['sentiment_score'],
                    'timestamp' => $article['published_at'],
                ];
            }
        }

        // Extract top social media evidence
        if (isset($socialMedia['top_mentions'])) {
            foreach ($socialMedia['top_mentions'] as $mention) {
                if (abs($mention['sentiment']) > 0.2) {
                    $evidence[] = [
                        'type' => 'social',
                        'text' => $mention['text'],
                        'sentiment' => $mention['sentiment'],
                        'timestamp' => now()->toISOString(),
                    ];
                }
            }
        }

        return $evidence;
    }

    private function calculateSentimentConfidence(array $news, array $socialMedia, array $analystRatings): float
    {
        $newsConfidence = count($news) > 0 ? min(1, count($news) / 10) : 0;
        $socialConfidence = ($socialMedia['mention_count'] ?? 0) > 0 ? min(1, ($socialMedia['mention_count'] ?? 0) / 500) : 0;
        $analystConfidence = count($analystRatings) > 0 ? min(1, count($analystRatings) / 5) : 0;

        return ($newsConfidence + $socialConfidence + $analystConfidence) / 3;
    }

    private function analyzeSentimentTrend(string $symbol): array
    {
        // Analyze how sentiment has changed over time
        $historicalSentiments = Cache::get("sentiment_history:{$symbol}", []);
        
        if (count($historicalSentiments) < 2) {
            return [
                'direction' => 'neutral',
                'strength' => 0,
                'change_24h' => 0,
                'change_7d' => 0,
            ];
        }

        $current = end($historicalSentiments);
        $previous = $historicalSentiments[count($historicalSentiments) - 2];
        
        $change = $current - $previous;
        
        return [
            'direction' => $change > 0.05 ? 'improving' : ($change < -0.05 ? 'declining' : 'stable'),
            'strength' => abs($change),
            'change_24h' => $change,
            'change_7d' => count($historicalSentiments) >= 7 ? $current - $historicalSentiments[0] : 0,
        ];
    }

    private function calculateScoreDistribution(array $scores): array
    {
        $distribution = [
            'very_positive' => 0,
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
            'very_negative' => 0,
        ];

        foreach ($scores as $score) {
            if ($score > 0.5) {
                $distribution['very_positive']++;
            } elseif ($score > 0.1) {
                $distribution['positive']++;
            } elseif ($score > -0.1) {
                $distribution['neutral']++;
            } elseif ($score > -0.5) {
                $distribution['negative']++;
            } else {
                $distribution['very_negative']++;
            }
        }

        return $distribution;
    }

    private function calculateEngagementRate(array $socialData): float
    {
        // Mock calculation - would be based on actual engagement metrics
        return rand(1, 10) / 100;
    }

    private function getSymbolAliases(string $symbol): string
    {
        // Return common aliases or related terms for the symbol
        $aliases = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'AAPL' => 'apple',
            'GOOGL' => 'google alphabet',
        ];

        return $aliases[$symbol] ?? $symbol;
    }

    private function getEmptySentiment(): array
    {
        return [
            'overall_score' => 0,
            'news_sentiment' => [
                'score' => 0,
                'confidence' => 0,
                'articles_analyzed' => 0,
                'positive_articles' => 0,
                'negative_articles' => 0,
                'neutral_articles' => 0,
            ],
            'social_sentiment' => [
                'score' => 0,
                'confidence' => 0,
                'mention_count' => 0,
                'sentiment_distribution' => ['positive' => 0, 'negative' => 0, 'neutral' => 0],
            ],
            'analyst_sentiment' => [
                'score' => 0,
                'confidence' => 0,
                'buy_ratings' => 0,
                'hold_ratings' => 0,
                'sell_ratings' => 0,
                'average_price_target' => 0,
            ],
            'evidence' => [],
            'confidence' => 0,
            'trend' => [
                'direction' => 'neutral',
                'strength' => 0,
                'change_24h' => 0,
                'change_7d' => 0,
            ],
            'sources' => [
                'news_count' => 0,
                'social_mentions' => 0,
                'analyst_ratings' => 0,
            ],
        ];
    }

    public function storeSentimentData(string $symbol, array $sentimentData): void
    {
        $instrument = Instrument::where('symbol', $symbol)->first();
        if (!$instrument) {
            return;
        }

        SentimentData::create([
            'instrument_id' => $instrument->id,
            'overall_score' => $sentimentData['overall_score'],
            'news_score' => $sentimentData['news_sentiment']['score'],
            'social_score' => $sentimentData['social_sentiment']['score'],
            'analyst_score' => $sentimentData['analyst_sentiment']['score'],
            'confidence' => $sentimentData['confidence'],
            'evidence' => $sentimentData['evidence'],
            'metadata' => [
                'sources' => $sentimentData['sources'],
                'trend' => $sentimentData['trend'],
            ],
        ]);

        // Update sentiment history for trend analysis
        $history = Cache::get("sentiment_history:{$symbol}", []);
        $history[] = $sentimentData['overall_score'];
        
        // Keep only last 7 days of data
        if (count($history) > 7) {
            $history = array_slice($history, -7);
        }
        
        Cache::put("sentiment_history:{$symbol}", $history, now()->addDays(7));
    }
}