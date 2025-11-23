<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstrumentSeeder extends Seeder
{
    public function run(): void
    {
        $instruments = [
            // Crypto
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'type' => 'crypto', 'exchange' => 'Binance', 'price' => 67500.00, 'change_24h' => 2.5, 'change_percent_24h' => 3.85, 'volume_24h' => 28500000000],
            ['symbol' => 'ETH', 'name' => 'Ethereum', 'type' => 'crypto', 'exchange' => 'Binance', 'price' => 3500.00, 'change_24h' => 1.8, 'change_percent_24h' => 5.26, 'volume_24h' => 15200000000],
            ['symbol' => 'BNB', 'name' => 'Binance Coin', 'type' => 'crypto', 'exchange' => 'Binance', 'price' => 580.00, 'change_24h' => -0.5, 'change_percent_24h' => -0.86, 'volume_24h' => 1200000000],
            ['symbol' => 'SOL', 'name' => 'Solana', 'type' => 'crypto', 'exchange' => 'Binance', 'price' => 145.50, 'change_24h' => 3.2, 'change_percent_24h' => 2.25, 'volume_24h' => 2800000000],
            
            // Stocks
            ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'type' => 'stock', 'exchange' => 'NASDAQ', 'sector' => 'Technology', 'price' => 182.50, 'change_24h' => 1.2, 'change_percent_24h' => 0.66, 'volume_24h' => 52000000],
            ['symbol' => 'MSFT', 'name' => 'Microsoft Corporation', 'type' => 'stock', 'exchange' => 'NASDAQ', 'sector' => 'Technology', 'price' => 415.30, 'change_24h' => -2.1, 'change_percent_24h' => -0.50, 'volume_24h' => 28000000],
            ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.', 'type' => 'stock', 'exchange' => 'NASDAQ', 'sector' => 'Technology', 'price' => 142.80, 'change_24h' => 0.8, 'change_percent_24h' => 0.56, 'volume_24h' => 25000000],
            ['symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'type' => 'stock', 'exchange' => 'NASDAQ', 'sector' => 'Automotive', 'price' => 242.60, 'change_24h' => 5.3, 'change_percent_24h' => 2.23, 'volume_24h' => 120000000],
            
            // Forex
            ['symbol' => 'EUR/USD', 'name' => 'Euro vs US Dollar', 'type' => 'forex', 'exchange' => 'FOREX', 'price' => 1.0875, 'change_24h' => 0.0012, 'change_percent_24h' => 0.11, 'volume_24h' => 125000000000],
            ['symbol' => 'GBP/USD', 'name' => 'British Pound vs US Dollar', 'type' => 'forex', 'exchange' => 'FOREX', 'price' => 1.2650, 'change_24h' => -0.0025, 'change_percent_24h' => -0.20, 'volume_24h' => 85000000000],
        ];

        foreach ($instruments as $instrument) {
            DB::table('instruments')->insert([
                'id' => Str::uuid(),
                'symbol' => $instrument['symbol'],
                'name' => $instrument['name'],
                'type' => $instrument['type'],
                'exchange' => $instrument['exchange'],
                'sector' => $instrument['sector'] ?? null,
                'price' => $instrument['price'],
                'change_24h' => $instrument['change_24h'],
                'change_percent_24h' => $instrument['change_percent_24h'],
                'volume_24h' => $instrument['volume_24h'],
                'market_cap' => $instrument['price'] * 1000000, // Simplified
                'is_active' => true,
                'metadata' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
