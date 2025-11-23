<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Domain\Market\Services\MarketDataService;
use App\Domain\History\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private MarketDataService $marketDataService;

    public function __construct(MarketDataService $marketDataService)
    {
        $this->marketDataService = $marketDataService;
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();
        
        // Get market overview
        $marketOverview = $this->marketDataService->getMarketOverview();
        
        // Get user's recent analyses
        $recentAnalyses = $user->analyses()
            ->with('instrument')
            ->latest()
            ->limit(5)
            ->get();
        
        // Get user's statistics
        $stats = [
            'total_analyses' => $user->analysis_count,
            'buy_signals' => $user->analyses()->where('recommendation', 'BUY')->count(),
            'sell_signals' => $user->analyses()->where('recommendation', 'SELL')->count(),
            'hold_signals' => $user->analyses()->where('recommendation', 'HOLD')->count(),
            'avg_confidence' => $user->analyses()->avg('confidence') ?? 0,
        ];

        return view('dashboard', compact('marketOverview', 'recentAnalyses', 'stats'));
    }

    public function marketData(Request $request): JsonResponse
    {
        try {
            $symbol = $request->input('symbol');
            $data = $this->marketDataService->getRealTimeData($symbol);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function watchlist(): JsonResponse
    {
        try {
            $user = auth()->user();
            $watchlist = $user->favorites()->with('instrument')->get()->pluck('instrument');
            
            return response()->json([
                'success' => true,
                'data' => $watchlist,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}