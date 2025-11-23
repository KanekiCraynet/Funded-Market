<?php

namespace App\Domain\History\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Users\Models\User;
use App\Domain\Market\Models\Instrument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'final_score',
        'recommendation',
        'confidence',
        'time_horizon',
        'risk_level',
        'position_size_recommendation',
        'price_targets',
        'top_drivers',
        'evidence_sentences',
        'explainability_text',
        'risk_notes',
        'key_levels',
        'catalysts',
        'technical_summary',
        'fundamental_summary',
        'sentiment_summary',
        'fusion_data',
        'llm_metadata',
    ];

    protected $casts = [
        'final_score' => 'decimal:4',
        'confidence' => 'decimal:4',
        'position_size_recommendation' => 'json',
        'price_targets' => 'json',
        'top_drivers' => 'json',
        'evidence_sentences' => 'json',
        'key_levels' => 'json',
        'catalysts' => 'json',
        'fusion_data' => 'json',
        'llm_metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByInstrument($query, $instrumentId)
    {
        return $query->where('instrument_id', $instrumentId);
    }

    public function scopeByRecommendation($query, string $recommendation)
    {
        return $query->where('recommendation', $recommendation);
    }

    public function scopeByRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getRecommendationColorAttribute(): string
    {
        return match($this->recommendation) {
            'BUY' => 'green',
            'SELL' => 'red',
            'HOLD' => 'yellow',
            default => 'gray',
        };
    }

    public function getRiskLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            'LOW' => 'green',
            'MEDIUM' => 'yellow',
            'HIGH' => 'red',
            default => 'gray',
        };
    }

    public function getConfidenceLevelAttribute(): string
    {
        return match(true) {
            $this->confidence >= 0.8 => 'Very High',
            $this->confidence >= 0.6 => 'High',
            $this->confidence >= 0.4 => 'Medium',
            $this->confidence >= 0.2 => 'Low',
            default => 'Very Low',
        };
    }

    public function getPositionSizePercentAttribute(): float
    {
        return $this->position_size_recommendation['size_percent'] ?? 0;
    }

    public function getNearTermTargetAttribute(): float
    {
        return $this->price_targets['near_term'] ?? 0;
    }

    public function getStopLossAttribute(): float
    {
        return $this->price_targets['stop_loss'] ?? 0;
    }

    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->diffInHours(now()) < 24;
    }

    public function getPerformanceAttribute(): ?array
    {
        // This would calculate actual performance if we had historical price data
        // For now, return null as placeholder
        return null;
    }

    public function getSummaryAttribute(): string
    {
        $strength = $this->confidence_level;
        $action = strtolower($this->recommendation);
        
        return "{$strength} confidence {$action} signal for {$this->instrument->symbol}";
    }
}