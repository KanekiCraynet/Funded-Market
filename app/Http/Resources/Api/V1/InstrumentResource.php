<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstrumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'symbol' => $this->symbol,
            'name' => $this->name,
            'type' => $this->type,
            'exchange' => $this->exchange,
            'sector' => $this->sector,
            
            // Price information
            'price' => round($this->price, $this->getPriceDecimals()),
            'formatted_price' => $this->formatted_price,
            
            // 24h changes
            'change_24h' => round($this->change_24h, $this->getPriceDecimals()),
            'change_percent_24h' => round($this->change_percent_24h, 2),
            'formatted_change' => $this->formatted_change,
            'formatted_change_percent' => $this->formatted_change_percent,
            
            // Volume and market cap
            'volume_24h' => round($this->volume_24h, 2),
            'market_cap' => round($this->market_cap, 2),
            
            // Status
            'is_active' => $this->is_active,
            
            // Additional metadata
            'description' => $this->when($request->has('include_details'), $this->description),
            'metadata' => $this->when($request->has('include_metadata'), $this->metadata),
            
            // Formatting helpers
            'price_color' => $this->change_percent_24h >= 0 ? 'positive' : 'negative',
            'change_arrow' => $this->change_percent_24h >= 0 ? 'up' : 'down',
            
            // Timestamps
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function getPriceDecimals(): int
    {
        return match($this->type) {
            'crypto' => 8,
            'forex' => 5,
            'stock' => 2,
            default => 4,
        };
    }
}