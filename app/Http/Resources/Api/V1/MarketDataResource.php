<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'timestamp' => $this->timestamp->toISOString(),
            'timeframe' => $this->timeframe,
            'source' => $this->source,
            
            // OHLCV data
            'open' => round($this->open, $this->getPriceDecimals()),
            'high' => round($this->high, $this->getPriceDecimals()),
            'low' => round($this->low, $this->getPriceDecimals()),
            'close' => round($this->close, $this->getPriceDecimals()),
            'volume' => round($this->volume, 2),
            'adjusted_close' => $this->when(isset($this->adjusted_close), round($this->adjusted_close, $this->getPriceDecimals())),
            
            // Calculated fields
            'change' => round($this->change, $this->getPriceDecimals()),
            'change_percent' => round($this->change_percent, 2),
            'range' => round($this->range, $this->getPriceDecimals()),
            'body_size' => round($this->body_size, $this->getPriceDecimals()),
            
            // Candlestick analysis
            'is_bullish' => $this->isBullish(),
            'is_bearish' => $this->isBearish(),
            'upper_wick' => round($this->upper_wick, $this->getPriceDecimals()),
            'lower_wick' => round($this->lower_wick, $this->getPriceDecimals()),
            
            // Price levels
            'typical_price' => round($this->typical_price, $this->getPriceDecimals()),
            'weighted_price' => round($this->weighted_price, $this->getPriceDecimals()),
            
            // Visual helpers
            'color' => $this->change >= 0 ? 'green' : 'red',
            'direction' => $this->change >= 0 ? 'up' : 'down',
        ];
    }

    private function getPriceDecimals(): int
    {
        // Determine decimals based on instrument type if available
        if ($this->instrument) {
            return match($this->instrument->type) {
                'crypto' => 8,
                'forex' => 5,
                'stock' => 2,
                default => 4,
            };
        }
        
        // Default to 4 decimals
        return 4;
    }
}