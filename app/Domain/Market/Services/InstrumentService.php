<?php

namespace App\Domain\Market\Services;

use App\Domain\Market\Models\Instrument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for instrument-related operations with caching
 * 
 * This service provides cached access to instrument data to avoid
 * repetitive database queries, especially during validation.
 */
class InstrumentService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'instrument:';
    
    // Cache hit rate tracking
    private static int $cacheHits = 0;
    private static int $cacheMisses = 0;
    private static int $logThreshold = 100; // Log every 100 requests

    /**
     * Find an active instrument by symbol (with caching)
     */
    public function findActiveBySymbol(string $symbol): ?Instrument
    {
        $cacheKey = self::CACHE_PREFIX . strtoupper($symbol);
        
        // Check if in cache (for metrics)
        $isHit = Cache::has($cacheKey);
        
        if ($isHit) {
            self::$cacheHits++;
            Log::debug('Instrument cache HIT', ['symbol' => $symbol]);
        } else {
            self::$cacheMisses++;
            Log::debug('Instrument cache MISS', ['symbol' => $symbol]);
        }
        
        // Log statistics periodically
        $this->logCacheStatistics();
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            return Instrument::where('symbol', strtoupper($symbol))
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Check if symbol exists and is active (cached)
     */
    public function symbolExists(string $symbol): bool
    {
        return $this->findActiveBySymbol($symbol) !== null;
    }

    /**
     * Get multiple instruments by symbols (batched, cached)
     */
    public function findManyBySymbols(array $symbols): Collection
    {
        $symbols = array_map('strtoupper', $symbols);
        $instruments = collect();
        
        foreach ($symbols as $symbol) {
            if ($instrument = $this->findActiveBySymbol($symbol)) {
                $instruments->push($instrument);
            }
        }
        
        return $instruments;
    }

    /**
     * Get all active instruments (cached)
     */
    public function getAllActive(): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'all_active';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Instrument::active()->get();
        });
    }

    /**
     * Get instruments by type (cached)
     */
    public function getByType(string $type): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "type:{$type}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            return Instrument::active()->where('type', $type)->get();
        });
    }

    /**
     * Get instruments by exchange (cached)
     */
    public function getByExchange(string $exchange): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "exchange:{$exchange}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($exchange) {
            return Instrument::active()->where('exchange', $exchange)->get();
        });
    }

    /**
     * Invalidate cache for a specific symbol
     */
    public function invalidateCache(string $symbol): void
    {
        $cacheKey = self::CACHE_PREFIX . strtoupper($symbol);
        Cache::forget($cacheKey);
        
        // Also invalidate aggregate caches
        Cache::forget(self::CACHE_PREFIX . 'all_active');
    }

    /**
     * Invalidate all instrument caches
     */
    public function invalidateAllCaches(): void
    {
        // This is a simple implementation
        // In production, you'd want to use cache tags for better invalidation
        Cache::forget(self::CACHE_PREFIX . 'all_active');
        
        Log::info('All instrument caches invalidated');
    }

    /**
     * Warm cache for popular symbols
     */
    public function warmCache(array $symbols = []): void
    {
        if (empty($symbols)) {
            // Get top 50 by volume if no symbols specified
            $symbols = Instrument::active()
                ->orderBy('volume_24h', 'desc')
                ->limit(50)
                ->pluck('symbol')
                ->toArray();
        }
        
        foreach ($symbols as $symbol) {
            $this->findActiveBySymbol($symbol);
        }
        
        Log::info('Instrument cache warmed', ['count' => count($symbols)]);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $total = self::$cacheHits + self::$cacheMisses;
        $hitRate = $total > 0 ? (self::$cacheHits / $total) * 100 : 0;
        
        return [
            'cache_ttl' => self::CACHE_TTL,
            'cache_prefix' => self::CACHE_PREFIX,
            'driver' => config('cache.default'),
            'cache_hits' => self::$cacheHits,
            'cache_misses' => self::$cacheMisses,
            'total_requests' => $total,
            'hit_rate' => round($hitRate, 2) . '%',
        ];
    }

    /**
     * Log cache statistics periodically
     */
    private function logCacheStatistics(): void
    {
        $total = self::$cacheHits + self::$cacheMisses;
        
        // Log every N requests
        if ($total % self::$logThreshold === 0 && $total > 0) {
            $hitRate = (self::$cacheHits / $total) * 100;
            
            Log::info('Instrument cache statistics', [
                'hits' => self::$cacheHits,
                'misses' => self::$cacheMisses,
                'total' => $total,
                'hit_rate' => round($hitRate, 2) . '%',
            ]);
        }
    }

    /**
     * Reset cache statistics
     */
    public function resetCacheStats(): void
    {
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
        
        Log::info('Instrument cache statistics reset');
    }
}
