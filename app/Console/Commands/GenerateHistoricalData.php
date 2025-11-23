<?php

namespace App\Console\Commands;

use App\Domain\Market\Models\Instrument;
use App\Domain\Market\Models\MarketData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateHistoricalData extends Command
{
    protected $signature = 'market:generate-historical {days=30 : Number of days of historical data to generate}';
    protected $description = 'Generate sample historical market data for testing';

    public function handle(): int
    {
        $days = $this->argument('days');
        $this->info("Generating {$days} days of historical market data...");

        try {
            $instruments = Instrument::active()->get();
            
            foreach ($instruments as $instrument) {
                $this->generateInstrumentData($instrument, $days);
            }

            $this->info('Historical data generation completed!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to generate historical data: {$e->getMessage()}");
            Log::error("Historical data generation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function generateInstrumentData(Instrument $instrument, int $days): void
    {
        $this->line("Generating data for {$instrument->symbol}...");
        
        $basePrice = $instrument->price;
        $currentPrice = $basePrice;
        
        // Generate hourly data for the specified number of days
        $dataPoints = $days * 24;
        
        for ($i = $dataPoints; $i >= 0; $i--) {
            $timestamp = Carbon::now()->subHours($i);
            
            // Generate realistic price movement
            $priceChange = $this->generatePriceChange($instrument->type);
            $currentPrice = $currentPrice * (1 + $priceChange);
            
            // Generate OHLC data
            $open = $currentPrice;
            $close = $currentPrice * (1 + $this->generatePriceChange($instrument->type) * 0.5);
            $high = max($open, $close) * (1 + abs($this->generatePriceChange($instrument->type)) * 0.3);
            $low = min($open, $close) * (1 - abs($this->generatePriceChange($instrument->type)) * 0.3);
            
            // Generate volume
            $volume = $this->generateVolume($instrument->type);
            
            MarketData::create([
                'instrument_id' => $instrument->id,
                'timestamp' => $timestamp,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'volume' => $volume,
                'timeframe' => '1h',
                'source' => 'generated',
            ]);
        }
        
        $this->line("✓ Generated {$dataPoints} data points for {$instrument->symbol}");
    }

    private function generatePriceChange(string $type): float
    {
        // Different volatility for different instrument types
        $multiplier = match($type) {
            'crypto' => 0.02,    // 2% max change
            'forex' => 0.001,    // 0.1% max change
            'stock' => 0.005,    // 0.5% max change
            default => 0.01,     // 1% max change
        };
        
        return (rand(-1000, 1000) / 1000) * $multiplier;
    }

    private function generateVolume(string $type): float
    {
        return match($type) {
            'crypto' => rand(1000000, 50000000),
            'forex' => rand(100000, 10000000),
            'stock' => rand(10000, 5000000),
            default => rand(50000, 1000000),
        };
    }
}