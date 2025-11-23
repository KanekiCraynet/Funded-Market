<?php

namespace App\Domain\Users\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Market\Models\Instrument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'notes',
        'alert_price_above',
        'alert_price_below',
        'alert_enabled',
    ];

    protected $casts = [
        'alert_price_above' => 'decimal:8',
        'alert_price_below' => 'decimal:8',
        'alert_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function scopeWithAlerts($query)
    {
        return $query->where('alert_enabled', true);
    }

    public function getPriceAlertsAttribute(): array
    {
        $alerts = [];
        
        if ($this->alert_price_above) {
            $alerts[] = [
                'type' => 'above',
                'price' => $this->alert_price_above,
                'condition' => 'price > ' . $this->alert_price_above,
            ];
        }
        
        if ($this->alert_price_below) {
            $alerts[] = [
                'type' => 'below',
                'price' => $this->alert_price_below,
                'condition' => 'price < ' . $this->alert_price_below,
            ];
        }
        
        return $alerts;
    }

    public function hasActiveAlerts(): bool
    {
        return $this->alert_enabled && (
            !is_null($this->alert_price_above) || 
            !is_null($this->alert_price_below)
        );
    }
}