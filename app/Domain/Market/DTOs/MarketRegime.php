<?php

namespace App\Domain\Market\DTOs;

class MarketRegime
{
    public function __construct(
        public readonly string $regime,      // 'bull', 'bear', 'neutral', 'consolidation'
        public readonly float $strength,     // 0.0 - 1.0
        public readonly string $phase,       // 'accumulation', 'markup', 'distribution', 'markdown'
        public readonly array $characteristics,
        public readonly float $confidence
    ) {}

    /**
     * Check if market is bullish
     */
    public function isBullish(): bool
    {
        return $this->regime === 'bull';
    }

    /**
     * Check if market is bearish
     */
    public function isBearish(): bool
    {
        return $this->regime === 'bear';
    }

    /**
     * Check if market is neutral/ranging
     */
    public function isNeutral(): bool
    {
        return in_array($this->regime, ['neutral', 'consolidation']);
    }

    /**
     * Get regime color for UI
     */
    public function getColor(): string
    {
        return match($this->regime) {
            'bull' => '#48bb78',      // Green
            'bear' => '#f56565',      // Red
            'neutral' => '#a0aec0',   // Gray
            'consolidation' => '#ed8936', // Orange
            default => '#4a5568'
        };
    }

    /**
     * Get regime label
     */
    public function getLabel(): string
    {
        return match($this->regime) {
            'bull' => 'Bullish',
            'bear' => 'Bearish',
            'neutral' => 'Neutral',
            'consolidation' => 'Consolidating',
            default => 'Unknown'
        };
    }

    /**
     * Get strength label
     */
    public function getStrengthLabel(): string
    {
        return match(true) {
            $this->strength > 0.7 => 'Strong',
            $this->strength > 0.4 => 'Moderate',
            default => 'Weak'
        };
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'regime' => $this->regime,
            'strength' => round($this->strength, 3),
            'phase' => $this->phase,
            'characteristics' => $this->characteristics,
            'confidence' => round($this->confidence, 3),
            'label' => $this->getLabel(),
            'strength_label' => $this->getStrengthLabel(),
            'color' => $this->getColor(),
        ];
    }
}
