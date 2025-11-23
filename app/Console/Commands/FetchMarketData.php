<?php

namespace App\Console\Commands;

use App\Domain\Market\Services\MarketDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchMarketData extends Command
{
    protected $signature = 'market:fetch {symbol? : Specific symbol to fetch}';
    protected $description = 'Fetch real-time market data for all or specific instruments';

    public function handle(MarketDataService $marketDataService): int
    {
        $this->info('Starting market data fetch...');

        try {
            if ($symbol = $this->argument('symbol')) {
                $this->fetchSymbolData($marketDataService, $symbol);
            } else {
                $this->fetchAllData($marketDataService);
            }

            $this->info('Market data fetch completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to fetch market data: {$e->getMessage()}");
            Log::error("Market data fetch failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function fetchSymbolData(MarketDataService $marketDataService, string $symbol): void
    {
        $this->line("Fetching data for {$symbol}...");
        
        $data = $marketDataService->getRealTimeData($symbol);
        
        if ($data) {
            $this->line("✓ Data fetched for {$symbol}");
        } else {
            $this->warn("✗ No data available for {$symbol}");
        }
    }

    private function fetchAllData(MarketDataService $marketDataService): void
    {
        $instruments = \App\Domain\Market\Models\Instrument::active()->get();
        
        $this->line("Fetching data for {$instruments->count()} instruments...");
        
        $progress = $this->output->createProgressBar($instruments->count());
        $progress->start();

        $successCount = 0;
        $failureCount = 0;

        foreach ($instruments as $instrument) {
            try {
                $data = $marketDataService->getRealTimeData($instrument->symbol);
                
                if ($data) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
                
                $progress->advance();
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
                
            } catch (\Exception $e) {
                $failureCount++;
                $progress->advance();
                Log::error("Failed to fetch data for {$instrument->symbol}: " . $e->getMessage());
            }
        }

        $progress->finish();
        $this->newLine();
        
        $this->info("✓ Successfully fetched: {$successCount}");
        $this->warn("✗ Failed to fetch: {$failureCount}");
    }
}