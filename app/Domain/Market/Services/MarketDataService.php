<?php

namespace App\Domain\Market\Services;

use App\Domain\Market\Models\Instrument;
use App\Domain\Market\Models\MarketData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MarketDataService
{
    private const CACHE_TTL = 60; // 1 minute
    private const HISTORICAL_CACHE_TTL = 3600; // 1 hour

    public function getRealTimeData(string $symbol): ?MarketData
    {
        $cacheKey = "market_data:realtime:{$symbol}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            $instrument = Instrument::where('symbol', $symbol)->first();
            if (!$instrument) {
                return null;
            }

            return $this->fetchRealTimeData($instrument);
        });
    }

    public function getHistoricalData(string $symbol, string $timeframe = '1h', int $limit = 100): Collection
    {
        $cacheKey = "market_data:historical:{$symbol}:{$timeframe}:{$limit}";
        
        return Cache::remember($cacheKey, self::HISTORICAL_CACHE_TTL, function () use ($symbol, $timeframe, $limit) {
            $instrument = Instrument::where('symbol', $symbol)->first();
            if (!$instrument) {
                return collect();
            }

            return MarketData::where('instrument_id', $instrument->id)
                ->where('timeframe', $timeframe)
                ->latest('timestamp')
                ->limit($limit)
                ->get()
                ->reverse(); // Return in chronological order
        });
    }

    public function getMarketOverview(): array
    {
        $cacheKey = 'market_overview';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'trending' => $this->getTrendingInstruments(),
                'top_gainers' => $this->getTopGainers(),
                'top_losers' => $this->getTopLosers(),
                'market_summary' => $this->getMarketSummary(),
                'sector_performance' => $this->getSectorPerformance(),
            ];
        });
    }

    private function fetchRealTimeData(Instrument $instrument): ?MarketData
    {
        try {
            $data = match($instrument->type) {
                'crypto' => $this->fetchCryptoData($instrument),
                'forex' => $this->fetchForexData($instrument),
                'stock' => $this->fetchStockData($instrument),
                default => null,
            };

            if ($data) {
                return $this->storeMarketData($instrument, $data, '1m');
            }

            return null;
        } catch (\Exception $e) {
            \Log::error("Failed to fetch real-time data for {$instrument->symbol}: " . $e->getMessage());
            return null;
        }
    }

    private function fetchCryptoData(Instrument $instrument): ?array
    {
        // Integration with crypto APIs (CoinGecko, CoinMarketCap, etc.)
        $response = Http::timeout(10)->get("https://api.coingecko.com/api/v3/simple/price", [
            'ids' => strtolower($instrument->symbol),
            'vs_currencies' => 'usd',
            'include_24hr_change' => 'true',
            'include_24hr_vol' => 'true',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $symbolKey = strtolower($instrument->symbol);
        
        if (!isset($data[$symbolKey])) {
            return null;
        }

        $cryptoData = $data[$symbolKey];
        
        return [
            'open' => $cryptoData['usd'] - ($cryptoData['usd_24h_change'] * $cryptoData['usd'] / 100),
            'high' => $cryptoData['usd'] * 1.02, // Estimate
            'low' => $cryptoData['usd'] * 0.98,  // Estimate
            'close' => $cryptoData['usd'],
            'volume' => $cryptoData['usd_24h_volume'] ?? 0,
        ];
    }

    private function fetchForexData(Instrument $instrument): ?array
    {
        // Integration with forex APIs
        $response = Http::timeout(10)->get("https://api.exchangerate-api.com/v4/latest/USD");

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $rate = $data['rates'][$instrument->symbol] ?? null;

        if (!$rate) {
            return null;
        }

        return [
            'open' => $rate * 0.999,
            'high' => $rate * 1.001,
            'low' => $rate * 0.998,
            'close' => $rate,
            'volume' => 1000000, // Standard forex lot
        ];
    }

    private function fetchStockData(Instrument $instrument): ?array
    {
        // Integration with stock APIs (Alpha Vantage, Yahoo Finance, etc.)
        // This is a placeholder implementation
        return [
            'open' => 100.0,
            'high' => 105.0,
            'low' => 95.0,
            'close' => 102.5,
            'volume' => 1000000,
        ];
    }

    private function storeMarketData(Instrument $instrument, array $data, string $timeframe): MarketData
    {
        return MarketData::create([
            'instrument_id' => $instrument->id,
            'timestamp' => now(),
            'open' => $data['open'],
            'high' => $data['high'],
            'low' => $data['low'],
            'close' => $data['close'],
            'volume' => $data['volume'],
            'timeframe' => $timeframe,
            'source' => $this->getSourceName($instrument->type),
        ]);
    }

    private function getSourceName(string $type): string
    {
        return match($type) {
            'crypto' => 'coingecko',
            'forex' => 'exchangerate_api',
            'stock' => 'alpha_vantage',
            default => 'unknown',
        };
    }

    private function getTrendingInstruments(): Collection
    {
        // OPTIMIZED: Select only needed columns (Phase 3 - Task 4)
        return Instrument::active()
            ->select(['id', 'symbol', 'name', 'type', 'price', 'change_percent_24h', 'volume_24h'])
            ->orderBy('volume_24h', 'desc')
            ->limit(10)
            ->get();
    }

    private function getTopGainers(): Collection
    {
        // OPTIMIZED: Select only needed columns (Phase 3 - Task 4)
        return Instrument::active()
            ->select(['id', 'symbol', 'name', 'type', 'price', 'change_percent_24h'])
            ->where('change_percent_24h', '>', 0)
            ->orderBy('change_percent_24h', 'desc')
            ->limit(10)
            ->get();
    }

    private function getTopLosers(): Collection
    {
        // OPTIMIZED: Select only needed columns (Phase 3 - Task 4)
        return Instrument::active()
            ->select(['id', 'symbol', 'name', 'type', 'price', 'change_percent_24h'])
            ->where('change_percent_24h', '<', 0)
            ->orderBy('change_percent_24h', 'asc')
            ->limit(10)
            ->get();
    }

    private function getMarketSummary(): array
    {
        // OPTIMIZED: Use single query with aggregates instead of multiple queries
        // BEFORE: 6 queries (count, where+count x3, sum x2)
        // AFTER: 1 query with selectRaw
        
        $summary = Instrument::active()
            ->selectRaw('
                COUNT(*) as total_instruments,
                SUM(CASE WHEN change_percent_24h > 0 THEN 1 ELSE 0 END) as gainers_count,
                SUM(CASE WHEN change_percent_24h < 0 THEN 1 ELSE 0 END) as losers_count,
                SUM(CASE WHEN change_percent_24h = 0 THEN 1 ELSE 0 END) as unchanged_count,
                COALESCE(SUM(market_cap), 0) as total_market_cap,
                COALESCE(SUM(volume_24h), 0) as total_volume_24h
            ')
            ->first();
        
        return [
            'total_instruments' => $summary->total_instruments ?? 0,
            'gainers_count' => $summary->gainers_count ?? 0,
            'losers_count' => $summary->losers_count ?? 0,
            'unchanged_count' => $summary->unchanged_count ?? 0,
            'total_market_cap' => $summary->total_market_cap ?? 0,
            'total_volume_24h' => $summary->total_volume_24h ?? 0,
        ];
    }

    private function getSectorPerformance(): array
    {
        return Instrument::active()
            ->selectRaw('sector, AVG(change_percent_24h) as avg_change, COUNT(*) as count')
            ->whereNotNull('sector')
            ->groupBy('sector')
            ->orderBy('avg_change', 'desc')
            ->get()
            ->toArray();
    }
}