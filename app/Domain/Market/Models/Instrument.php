<?php

namespace App\Domain\Market\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Instrument extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'symbol',
        'name',
        'type',
        'exchange',
        'sector',
        'price',
        'change_24h',
        'change_percent_24h',
        'volume_24h',
        'market_cap',
        'high_24h',
        'low_24h',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'change_24h' => 'decimal:8',
        'change_percent_24h' => 'decimal:4',
        'volume_24h' => 'decimal:2',
        'market_cap' => 'decimal:2',
        'high_24h' => 'decimal:8',
        'low_24h' => 'decimal:8',
        'is_active' => 'boolean',
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

        // Automatic cache invalidation on model changes
        static::saved(function ($model) {
            $model->invalidateCache();
        });

        static::deleted(function ($model) {
            $model->invalidateCache();
        });

        // Only register restored event if soft deletes are enabled
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->invalidateCache();
            });
        }
    }

    /**
     * Invalidate cache for this instrument
     */
    public function invalidateCache(): void
    {
        try {
            $instrumentService = app(\App\Domain\Market\Services\InstrumentService::class);
            $instrumentService->invalidateCache($this->symbol);
            
            \Log::debug('Instrument cache invalidated', [
                'symbol' => $this->symbol,
                'id' => $this->id,
            ]);
        } catch (\Exception $e) {
            // Don't fail the operation if cache invalidation fails
            \Log::warning('Failed to invalidate instrument cache', [
                'symbol' => $this->symbol,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function marketData(): HasMany
    {
        return $this->hasMany(MarketData::class, 'instrument_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(\App\Domain\History\Models\Analysis::class, 'instrument_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\App\Domain\Users\Models\UserFavorite::class, 'instrument_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByExchange($query, string $exchange)
    {
        return $query->where('exchange', $exchange);
    }

    public function scopeBySector($query, string $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('symbol', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    public function scopeTopGainers($query, int $limit = 10)
    {
        return $query->active()
            ->orderBy('change_percent_24h', 'desc')
            ->limit($limit);
    }

    public function scopeTopLosers($query, int $limit = 10)
    {
        return $query->active()
            ->orderBy('change_percent_24h', 'asc')
            ->limit($limit);
    }

    public function scopeTrending($query, int $limit = 10)
    {
        return $query->active()
            ->orderBy('volume_24h', 'desc')
            ->limit($limit);
    }

    // Accessors
    public function getChangeDirectionAttribute(): string
    {
        if ($this->change_percent_24h > 0) {
            return 'up';
        } elseif ($this->change_percent_24h < 0) {
            return 'down';
        }
        return 'neutral';
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getFormattedMarketCapAttribute(): string
    {
        if ($this->market_cap >= 1_000_000_000_000) {
            return '$' . number_format($this->market_cap / 1_000_000_000_000, 2) . 'T';
        } elseif ($this->market_cap >= 1_000_000_000) {
            return '$' . number_format($this->market_cap / 1_000_000_000, 2) . 'B';
        } elseif ($this->market_cap >= 1_000_000) {
            return '$' . number_format($this->market_cap / 1_000_000, 2) . 'M';
        }
        return '$' . number_format($this->market_cap, 2);
    }
}
