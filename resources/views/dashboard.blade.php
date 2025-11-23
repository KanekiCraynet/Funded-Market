@extends('layouts.app')

@section('title', 'Dashboard - Market Analysis Platform')

@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-gray-600">Real-time market analysis and insights</p>
    </div>

    <!-- Market Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Trending -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Trending</h3>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
            <div class="space-y-3">
                <template x-for="instrument in marketOverview.trending.slice(0, 3)" :key="instrument.symbol">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-900" x-text="instrument.symbol"></span>
                        <span class="text-sm" :class="instrument.change_percent_24h >= 0 ? 'text-green-600' : 'text-red-600'" x-text="formatPercent(instrument.change_percent_24h)"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Gainers -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Top Gainers</h3>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                </svg>
            </div>
            <div class="space-y-3">
                <template x-for="instrument in marketOverview.top_gainers.slice(0, 3)" :key="instrument.symbol">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-900" x-text="instrument.symbol"></span>
                        <span class="text-sm text-green-600" x-text="'+' + formatPercent(instrument.change_percent_24h)"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Losers -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Top Losers</h3>
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                </svg>
            </div>
            <div class="space-y-3">
                <template x-for="instrument in marketOverview.top_losers.slice(0, 3)" :key="instrument.symbol">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-900" x-text="instrument.symbol"></span>
                        <span class="text-sm text-red-600" x-text="formatPercent(instrument.change_percent_24h)"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Market Summary -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Market Summary</h3>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Instruments</span>
                    <span class="font-medium" x-text="marketOverview.market_summary.total_instruments || 0"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Gainers</span>
                    <span class="font-medium text-green-600" x-text="marketOverview.market_summary.gainers_count || 0"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Losers</span>
                    <span class="font-medium text-red-600" x-text="marketOverview.market_summary.losers_count || 0"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Analysis Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Analysis Generator -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Generate Analysis</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="symbol" class="block text-sm font-medium text-gray-700 mb-2">
                            Symbol
                        </label>
                        <div class="flex space-x-2">
                            <input 
                                type="text" 
                                id="symbol" 
                                x-model="analysisSymbol"
                                placeholder="Enter symbol (e.g., BTC, AAPL, EURUSD)"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                            <button 
                                @click="generateAnalysis()"
                                :disabled="!analysisSymbol || isGenerating"
                                class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span x-show="!isGenerating">Analyze</span>
                                <span x-show="isGenerating">Generating...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Rate Limit Info -->
                    <div x-show="rateLimitReset > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-yellow-800">
                                Rate limit active. Next analysis available in <span x-text="rateLimitReset"></span> seconds.
                            </span>
                        </div>
                    </div>

                    <!-- Analysis Results -->
                    <div x-show="analysisResult" class="mt-6 p-6 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Analysis Results</h3>
                        
                        <div x-show="analysisResult" class="space-y-4">
                            <!-- Recommendation -->
                            <div class="flex items-center justify-between p-4 rounded-lg" :class="getRecommendationColor(analysisResult.recommendation)">
                                <div>
                                    <div class="text-2xl font-bold" x-text="analysisResult.recommendation"></div>
                                    <div class="text-sm opacity-90">Confidence: <span x-text="formatPercent(analysisResult.confidence * 100)"></span></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm opacity-90">Score</div>
                                    <div class="text-xl font-bold" x-text="analysisResult.final_score.toFixed(3)"></div>
                                </div>
                            </div>

                            <!-- Key Metrics -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Risk Level</div>
                                    <div class="text-lg font-semibold" x-text="analysisResult.risk_level"></div>
                                </div>
                                <div class="bg-white p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Position Size</div>
                                    <div class="text-lg font-semibold"><span x-text="analysisResult.position_size_recommendation.size_percent"></span>%</div>
                                </div>
                            </div>

                            <!-- Price Targets -->
                            <div class="bg-white p-4 rounded-lg">
                                <div class="text-sm text-gray-600 mb-2">Price Targets</div>
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <div class="text-gray-500">Near Term</div>
                                        <div class="font-semibold" x-text="formatNumber(analysisResult.price_targets.near_term)"></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Stop Loss</div>
                                        <div class="font-semibold text-red-600" x-text="formatNumber(analysisResult.price_targets.stop_loss)"></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Time Horizon</div>
                                        <div class="font-semibold" x-text="analysisResult.time_horizon"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="bg-white p-4 rounded-lg">
                                <div class="text-sm text-gray-600 mb-2">Summary</div>
                                <p class="text-gray-800" x-text="analysisResult.explainability_text"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Analyses -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Analyses</h2>
                
                <div class="space-y-4">
                    <template x-for="analysis in recentAnalyses" :key="analysis.id">
                        <div class="border-l-4 pl-4 py-2" :class="getRecommendationBorderColor(analysis.recommendation)">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-semibold text-gray-900" x-text="analysis.instrument.symbol"></div>
                                    <div class="text-sm text-gray-600" x-text="new Date(analysis.created_at).toLocaleDateString()"></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold" x-text="analysis.recommendation"></div>
                                    <div class="text-xs text-gray-500" x-text="analysis.confidence.toFixed(2)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="recentAnalyses.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p>No analyses yet</p>
                        <p class="text-sm">Generate your first analysis to see results here</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ route('history') }}" class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        View All Analyses
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function dashboard() {
    return {
        marketOverview: @json($marketOverview ?? []),
        recentAnalyses: @json($recentAnalyses->map(function($analysis) {
            return [
                'id' => $analysis->id,
                'recommendation' => $analysis->recommendation,
                'confidence' => $analysis->confidence,
                'created_at' => $analysis->created_at->toISOString(),
                'instrument' => [
                    'symbol' => $analysis->instrument->symbol,
                ]
            ];
        })),
        analysisSymbol: '',
        isGenerating: false,
        analysisResult: null,
        rateLimitReset: 0,

        init() {
            // Auto-refresh market data every 30 seconds
            setInterval(() => {
                this.refreshMarketData();
            }, 30000);
        },

        async generateAnalysis() {
            if (!this.analysisSymbol || this.isGenerating) return;

            this.isGenerating = true;
            this.analysisResult = null;

            try {
                const response = await fetch('/api/v1/analysis/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        symbol: this.analysisSymbol.toUpperCase()
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.analysisResult = data.data;
                    this.rateLimitReset = data.rate_limit_reset || 0;
                    app.showToast('Analysis generated successfully!', 'success');
                    
                    // Refresh recent analyses
                    setTimeout(() => {
                        this.refreshRecentAnalyses();
                    }, 1000);
                } else {
                    app.showToast(data.message || 'Failed to generate analysis', 'error');
                    if (data.rate_limit_reset) {
                        this.rateLimitReset = data.rate_limit_reset;
                        this.startRateLimitCountdown();
                    }
                }
            } catch (error) {
                console.error('Error generating analysis:', error);
                app.showToast('Failed to generate analysis', 'error');
            } finally {
                this.isGenerating = false;
            }
        },

        async refreshMarketData() {
            try {
                const response = await fetch('/api/v1/market/overview');
                const data = await response.json();
                
                if (data.success) {
                    this.marketOverview = data.data;
                }
            } catch (error) {
                console.error('Error refreshing market data:', error);
            }
        },

        async refreshRecentAnalyses() {
            try {
                const response = await fetch('/api/v1/analysis/history?per_page=5');
                const data = await response.json();
                
                if (data.success) {
                    this.recentAnalyses = data.data.data;
                }
            } catch (error) {
                console.error('Error refreshing recent analyses:', error);
            }
        },

        startRateLimitCountdown() {
            const countdown = setInterval(() => {
                this.rateLimitReset--;
                if (this.rateLimitReset <= 0) {
                    clearInterval(countdown);
                }
            }, 1000);
        },

        getRecommendationColor(recommendation) {
            const colors = {
                'BUY': 'bg-green-100 text-green-800 border-green-200',
                'SELL': 'bg-red-100 text-red-800 border-red-200',
                'HOLD': 'bg-yellow-100 text-yellow-800 border-yellow-200'
            };
            return colors[recommendation] || 'bg-gray-100 text-gray-800 border-gray-200';
        },

        getRecommendationBorderColor(recommendation) {
            const colors = {
                'BUY': 'border-green-500',
                'SELL': 'border-red-500',
                'HOLD': 'border-yellow-500'
            };
            return colors[recommendation] || 'border-gray-500';
        },

        formatNumber(num, decimals = 2) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        },

        formatPercent(num) {
            return new Intl.NumberFormat('en-US', {
                style: 'percent',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num / 100);
        }
    }
}
</script>
@endsection