<?php

namespace Database\Factories\Domain\Market\Models;

use App\Domain\Market\Models\Instrument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Market\Models\Instrument>
 */
class InstrumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Instrument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['crypto', 'forex', 'stock'];
        $type = fake()->randomElement($types);
        
        $symbols = [
            'crypto' => ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'SOLUSDT'],
            'forex' => ['EURUSD', 'GBPUSD', 'USDJPY', 'AUDUSD', 'USDCAD'],
            'stock' => ['AAPL', 'GOOGL', 'MSFT', 'AMZN', 'TSLA'],
        ];
        
        $names = [
            'crypto' => ['Bitcoin', 'Ethereum', 'Binance Coin', 'Cardano', 'Solana'],
            'forex' => ['Euro/US Dollar', 'British Pound/US Dollar', 'US Dollar/Japanese Yen', 'Australian Dollar/US Dollar', 'US Dollar/Canadian Dollar'],
            'stock' => ['Apple Inc.', 'Alphabet Inc.', 'Microsoft Corporation', 'Amazon.com Inc.', 'Tesla Inc.'],
        ];
        
        $index = array_rand($symbols[$type]);
        
        return [
            'symbol' => $symbols[$type][$index],
            'name' => $names[$type][$index],
            'type' => $type,
            'exchange' => $type === 'crypto' ? 'Binance' : ($type === 'forex' ? 'FOREX' : 'NASDAQ'),
            'is_active' => true,
            'price' => fake()->randomFloat(2, 10, 50000),
            'change_percent_24h' => fake()->randomFloat(2, -10, 10),
            'volume_24h' => fake()->randomFloat(2, 1000000, 10000000000),
            'market_cap' => fake()->randomFloat(2, 100000000, 1000000000000),
            'sector' => $type === 'stock' ? fake()->randomElement(['Technology', 'Finance', 'Healthcare', 'Energy']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the instrument is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the instrument is a gainer.
     */
    public function gainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_percent_24h' => fake()->randomFloat(2, 5, 20),
        ]);
    }

    /**
     * Indicate that the instrument is a loser.
     */
    public function loser(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_percent_24h' => fake()->randomFloat(2, -20, -5),
        ]);
    }
}
