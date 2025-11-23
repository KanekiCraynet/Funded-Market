<?php

namespace App\Domain\Sentiment\Services;

use App\Services\ApiKeyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsAggregator
{
    private const CACHE_TTL = 300; // 5 minutes
    
    private ApiKeyService $apiKeyService;
    
    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Fetch news from multiple sources for a symbol
     */
    public function fetchNewsForSymbol(string $symbol): Collection
    {
        $cacheKey = "news:{$symbol}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            $allNews = collect();
            
            // Fetch from multiple sources
            try {
                $cryptoNews = $this->fetchFromCryptoNews($symbol);
                $allNews = $allNews->concat($cryptoNews);
            } catch (\Exception $e) {
                Log::warning('CryptoNews fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
            
            try {
                $newsApiData = $this->fetchFromNewsAPI($symbol);
                $allNews = $allNews->concat($newsApiData);
            } catch (\Exception $e) {
                Log::warning('NewsAPI fetch failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
            
            // Sort by published date (newest first)
            return $allNews->sortByDesc('published_at')->take(50);
        });
    }

    /**
     * Fetch from Crypto News API (for crypto symbols)
     */
    private function fetchFromCryptoNews(string $symbol): Collection
    {
        // Mock implementation - replace with actual API call
        // Example: CryptoPanic API
        
        $apiKey = $this->apiKeyService->get('cryptopanic');
        
        if (!$apiKey) {
            return collect();
        }
        
        try {
            $response = Http::timeout(10)->get('https://cryptopanic.com/api/v1/posts/', [
                'auth_token' => $apiKey,
                'currencies' => $this->normalizeCryptoSymbol($symbol),
                'filter' => 'important',
            ]);
            
            if (!$response->successful()) {
                return collect();
            }
            
            $results = $response->json('results', []);
            
            return collect($results)->map(function ($item) {
                return [
                    'title' => $item['title'] ?? '',
                    'content' => $item['title'] ?? '', // CryptoPanic doesn't provide full content
                    'url' => $item['url'] ?? '',
                    'source' => $item['source']['title'] ?? 'CryptoPanic',
                    'published_at' => $item['published_at'] ?? now()->toISOString(),
                    'sentiment_votes' => [
                        'positive' => $item['votes']['positive'] ?? 0,
                        'negative' => $item['votes']['negative'] ?? 0,
                    ],
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('CryptoPanic API error', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Fetch from NewsAPI (for stocks and general news)
     */
    private function fetchFromNewsAPI(string $symbol): Collection
    {
        $apiKey = $this->apiKeyService->get('newsapi');
        
        if (!$apiKey) {
            return collect();
        }
        
        try {
            $response = Http::timeout(10)->get('https://newsapi.org/v2/everything', [
                'apiKey' => $apiKey,
                'q' => $this->buildSearchQuery($symbol),
                'language' => 'en',
                'sortBy' => 'publishedAt',
                'pageSize' => 20,
            ]);
            
            if (!$response->successful()) {
                return collect();
            }
            
            $articles = $response->json('articles', []);
            
            return collect($articles)->map(function ($article) {
                return [
                    'title' => $article['title'] ?? '',
                    'content' => $article['description'] ?? $article['content'] ?? '',
                    'url' => $article['url'] ?? '',
                    'source' => $article['source']['name'] ?? 'NewsAPI',
                    'published_at' => $article['publishedAt'] ?? now()->toISOString(),
                    'image_url' => $article['urlToImage'] ?? null,
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('NewsAPI error', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Fetch from Twitter (optional - requires Twitter API access)
     */
    private function fetchFromTwitter(string $symbol): Collection
    {
        // This would require Twitter API v2 access
        // For now, return empty collection
        return collect();
    }

    /**
     * Normalize crypto symbol for API calls
     */
    private function normalizeCryptoSymbol(string $symbol): string
    {
        // Remove USDT, USD, BUSD suffixes
        $symbol = str_replace(['USDT', 'USD', 'BUSD', 'EUR'], '', $symbol);
        return strtoupper($symbol);
    }

    /**
     * Build search query for news APIs
     */
    private function buildSearchQuery(string $symbol): string
    {
        // Map common symbols to company/crypto names
        $symbolMap = [
            'BTCUSDT' => 'Bitcoin',
            'ETHUSDT' => 'Ethereum',
            'BNBUSDT' => 'Binance Coin',
            'AAPL' => 'Apple',
            'GOOGL' => 'Google',
            'TSLA' => 'Tesla',
            'MSFT' => 'Microsoft',
            'AMZN' => 'Amazon',
            // Add more mappings as needed
        ];
        
        return $symbolMap[$symbol] ?? $symbol;
    }

    /**
     * Get news statistics
     */
    public function getNewsStats(Collection $news): array
    {
        $total = $news->count();
        
        if ($total === 0) {
            return [
                'total' => 0,
                'sources' => 0,
                'avg_sentiment' => 0,
                'coverage_score' => 0,
            ];
        }
        
        $sources = $news->pluck('source')->unique()->count();
        
        // Calculate average sentiment from votes if available
        $avgSentiment = 0;
        $withVotes = $news->filter(fn($n) => isset($n['sentiment_votes']));
        
        if ($withVotes->count() > 0) {
            $totalPositive = $withVotes->sum('sentiment_votes.positive');
            $totalNegative = $withVotes->sum('sentiment_votes.negative');
            $totalVotes = $totalPositive + $totalNegative;
            
            if ($totalVotes > 0) {
                $avgSentiment = ($totalPositive - $totalNegative) / $totalVotes;
            }
        }
        
        // Coverage score (more articles = higher coverage)
        $coverageScore = min(1, $total / 50);
        
        return [
            'total' => $total,
            'sources' => $sources,
            'avg_sentiment' => round($avgSentiment, 3),
            'coverage_score' => round($coverageScore, 3),
        ];
    }
}
