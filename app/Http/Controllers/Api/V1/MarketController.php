<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Market\Services\MarketDataService;
use App\Http\Resources\Api\V1\InstrumentResource;
use App\Http\Resources\Api\V1\MarketDataResource;
use App\Domain\Market\Models\Instrument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    private MarketDataService $marketDataService;

    public function __construct(MarketDataService $marketDataService)
    {
        $this->marketDataService = $marketDataService;
    }

    public function overview(): JsonResponse
    {
        try {
            $overview = $this->marketDataService->getMarketOverview();

            return response()->json([
                'success' => true,
                'data' => [
                    'trending' => InstrumentResource::collection($overview['trending']),
                    'top_gainers' => InstrumentResource::collection($overview['top_gainers']),
                    'top_losers' => InstrumentResource::collection($overview['top_losers']),
                    'market_summary' => $overview['market_summary'],
                    'sector_performance' => $overview['sector_performance'],
                ],
                'last_updated' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch market overview',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function tickers(): JsonResponse
    {
        try {
            $tickers = Instrument::active()
                ->select(['symbol', 'name', 'type', 'price', 'change_percent_24h', 'volume_24h'])
                ->orderBy('volume_24h', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => InstrumentResource::collection($tickers),
                'count' => $tickers->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tickers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function instruments(Request $request): JsonResponse
    {
        try {
            $query = Instrument::active();

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            // Filter by exchange
            if ($request->has('exchange')) {
                $query->where('exchange', $request->input('exchange'));
            }

            // Search by symbol or name
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('symbol', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Filter by sector
            if ($request->has('sector')) {
                $query->where('sector', $request->input('sector'));
            }

            // Sort by
            $sortBy = $request->input('sort_by', 'volume_24h');
            $sortOrder = $request->input('sort_order', 'desc');
            
            if (in_array($sortBy, ['symbol', 'name', 'price', 'change_percent_24h', 'volume_24h', 'market_cap'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->input('per_page', 20), 100);
            $instruments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => InstrumentResource::collection($instruments),
                'pagination' => [
                    'current_page' => $instruments->currentPage(),
                    'last_page' => $instruments->lastPage(),
                    'per_page' => $instruments->perPage(),
                    'total' => $instruments->total(),
                    'from' => $instruments->firstItem(),
                    'to' => $instruments->lastItem(),
                ],
                'filters' => [
                    'types' => Instrument::active()->distinct()->pluck('type'),
                    'exchanges' => Instrument::active()->distinct()->pluck('exchange'),
                    'sectors' => Instrument::active()->whereNotNull('sector')->distinct()->pluck('sector'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch instruments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $symbol): JsonResponse
    {
        try {
            $instrument = Instrument::where('symbol', strtoupper($symbol))
                ->where('is_active', true)
                ->firstOrFail();

            // Get additional market data
            $realTimeData = $this->marketDataService->getRealTimeData($symbol);
            $historicalData = $this->marketDataService->getHistoricalData($symbol, '1h', 168); // 1 week of hourly data

            return response()->json([
                'success' => true,
                'data' => [
                    'instrument' => new InstrumentResource($instrument),
                    'real_time_data' => $realTimeData ? new MarketDataResource($realTimeData) : null,
                    'historical_data' => MarketDataResource::collection($historicalData),
                    'market_stats' => $this->getInstrumentMarketStats($instrument),
                ],
                'last_updated' => now()->toISOString(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Instrument '{$symbol}' not found",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch instrument data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function watchlist(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $watchlist = $user->favorites()->with('instrument')->get()->pluck('instrument');

            return response()->json([
                'success' => true,
                'data' => InstrumentResource::collection($watchlist),
                'count' => $watchlist->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch watchlist',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function sectors(): JsonResponse
    {
        try {
            $sectors = Instrument::active()
                ->whereNotNull('sector')
                ->selectRaw('sector, 
                    COUNT(*) as instrument_count,
                    AVG(change_percent_24h) as avg_change_24h,
                    SUM(market_cap) as total_market_cap,
                    SUM(volume_24h) as total_volume_24h')
                ->groupBy('sector')
                ->orderBy('total_market_cap', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sectors->map(function ($sector) {
                    return [
                        'name' => $sector->sector,
                        'instrument_count' => $sector->instrument_count,
                        'avg_change_24h' => round($sector->avg_change_24h, 2),
                        'total_market_cap' => $sector->total_market_cap,
                        'total_volume_24h' => $sector->total_volume_24h,
                        'performance' => $sector->avg_change_24h > 0 ? 'positive' : 'negative',
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sector data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function getInstrumentMarketStats(Instrument $instrument): array
    {
        $marketData = $instrument->marketData()->latest('timestamp')->limit(2)->get();
        
        if ($marketData->count() < 2) {
            return [
                'volume_ratio' => 1,
                'price_volatility' => 0,
                'trend_strength' => 0,
                'momentum' => 0,
            ];
        }

        $current = $marketData->first();
        $previous = $marketData->last();

        // Calculate basic stats
        $volumeRatio = $current->volume > 0 ? $current->volume / $previous->volume : 1;
        $priceChange = abs($current->close - $previous->close) / $previous->close;
        $momentum = ($current->close - $previous->close) / $previous->close;

        // Get more historical data for trend strength
        $historicalData = $instrument->marketData()
            ->latest('timestamp')
            ->limit(20)
            ->get()
            ->reverse();

        $trendStrength = $this->calculateTrendStrength($historicalData);

        return [
            'volume_ratio' => round($volumeRatio, 2),
            'price_volatility' => round($priceChange * 100, 2),
            'trend_strength' => round($trendStrength, 3),
            'momentum' => round($momentum, 4),
        ];
    }

    private function calculateTrendStrength($data): float
    {
        if ($data->count() < 10) {
            return 0;
        }

        $prices = $data->pluck('close')->toArray();
        $firstPrice = $prices[0];
        $lastPrice = end($prices);

        if ($firstPrice == 0) {
            return 0;
        }

        $totalReturn = ($lastPrice - $firstPrice) / $firstPrice;
        $periods = count($prices) - 1;
        
        // Annualized trend strength
        return $totalReturn / sqrt($periods);
    }
}