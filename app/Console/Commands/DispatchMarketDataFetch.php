<?php

namespace App\Console\Commands;

use App\Jobs\FetchMarketDataJob;
use App\Domain\Market\Models\Instrument;
use Illuminate\Console\Command;

class DispatchMarketDataFetch extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'market:fetch 
                            {--symbols=* : Specific symbols to fetch (optional)}
                            {--all : Fetch all active instruments}';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch jobs to fetch market data for instruments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching market data fetch jobs...');

        // Get symbols to fetch
        if ($this->option('all')) {
            $symbols = Instrument::where('active', true)
                ->pluck('symbol')
                ->toArray();
        } elseif ($this->option('symbols')) {
            $symbols = $this->option('symbols');
        } else {
            // Default: fetch major symbols
            $symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'EURUSD', 'GBPUSD'];
        }

        if (empty($symbols)) {
            $this->error('No symbols to fetch');
            return self::FAILURE;
        }

        // Dispatch job in batches of 10
        $batches = array_chunk($symbols, 10);
        
        foreach ($batches as $batch) {
            FetchMarketDataJob::dispatch($batch);
            $this->info('Dispatched job for: ' . implode(', ', $batch));
        }

        $this->info("Total symbols queued: " . count($symbols));
        
        return self::SUCCESS;
    }
}
