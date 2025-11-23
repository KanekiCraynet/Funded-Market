<?php

namespace App\Jobs;

use App\Domain\Market\Services\MarketDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $symbols
    ) {
        $this->onQueue('market-data');
    }

    /**
     * Execute the job.
     */
    public function handle(MarketDataService $marketDataService): void
    {
        Log::info('FetchMarketDataJob started', ['symbols' => $this->symbols]);

        $successCount = 0;
        $errorCount = 0;

        foreach ($this->symbols as $symbol) {
            try {
                $marketDataService->fetchAndStoreRealTimeData($symbol);
                $successCount++;
                
                Log::info("Market data fetched successfully", ['symbol' => $symbol]);
            } catch (\Exception $e) {
                $errorCount++;
                
                Log::error("Failed to fetch market data", [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('FetchMarketDataJob completed', [
            'total' => count($this->symbols),
            'success' => $successCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchMarketDataJob failed completely', [
            'symbols' => $this->symbols,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Could send notification to admin here
    }
}
