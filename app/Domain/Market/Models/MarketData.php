<?php

namespace App\Domain\Market\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketData extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'market_data';

    protected $fillable = [
        'instrument_id',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'timeframe',
        'metadata',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:2',
        'metadata' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
