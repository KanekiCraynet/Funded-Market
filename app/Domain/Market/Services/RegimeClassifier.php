<?php

namespace App\Domain\Market\Services;

use App\Domain\Market\DTOs\MarketRegime;
use Illuminate\Support\Facades\Log;

class RegimeClassifier
{
    /**
     * Classify market regime based on multiple factors
     */
    public function classify(array $quantData, array $sentimentData): MarketRegime
    {
        try {
            // Extract key metrics
            $trend = $quantData['trend'] ?? [];
            $momentum = $quantData['momentum'] ?? [];
            $volatility = $quantData['volatility'] ?? [];
            
            $trendDirection = $trend['direction'] ?? 'neutral';
            $trendStrength = $trend['trend_strength'] ?? 0.5;
            $adx = $trend['adx'] ?? 25;
            $rsi = $momentum['rsi'] ?? 50;
            $volatilityRegime = $volatility['volatility_regime'] ?? 'medium';
            
            $sentimentScore = $sentimentData['polarity'] ?? 0;
            $sentimentTrend = $sentimentData['trend'] ?? 'stable';
            
            // Calculate composite scores
            $bullScore = $this->calculateBullScore($trendDirection, $rsi, $sentimentScore, $adx);
            $bearScore = $this->calculateBearScore($trendDirection, $rsi, $sentimentScore, $adx);
            $neutralScore = $this->calculateNeutralScore($adx, $volatilityRegime, abs($sentimentScore));
            
            // Determine regime
            $scores = [
                'bull' => $bullScore,
                'bear' => $bearScore,
                'neutral' => $neutralScore,
            ];
            
            arsort($scores);
            $regime = array_key_first($scores);
            $strength = $scores[$regime];
            
            // Determine phase within the regime
            $phase = $this->determinePhase($regime, $trendStrength, $rsi, $sentimentTrend);
            
            // Build characteristics
            $characteristics = $this->buildCharacteristics(
                $regime,
                $trendDirection,
                $adx,
                $rsi,
                $volatilityRegime,
                $sentimentScore
            );
            
            // Calculate confidence
            $confidence = $this->calculateConfidence(
                $adx,
                abs($bullScore - $bearScore),
                $volatilityRegime,
                $sentimentData['confidence'] ?? 0.5
            );
            
            return new MarketRegime(
                $regime,
                $strength,
                $phase,
                $characteristics,
                $confidence
            );
            
        } catch (\Exception $e) {
            Log::error('Regime classification failed', [
                'error' => $e->getMessage(),
            ]);
            
            // Return neutral regime on error
            return new MarketRegime(
                'neutral',
                0.5,
                'unknown',
                ['Unable to classify market regime'],
                0.3
            );
        }
    }

    /**
     * Calculate bullish score
     */
    private function calculateBullScore(string $trendDirection, float $rsi, float $sentiment, float $adx): float
    {
        $score = 0;
        
        // Trend contribution (40%)
        if ($trendDirection === 'up') {
            $score += 0.4;
        } elseif ($trendDirection === 'neutral') {
            $score += 0.2;
        }
        
        // Momentum contribution (30%)
        if ($rsi > 50) {
            $score += 0.3 * (($rsi - 50) / 50); // Scale 0-0.3
        }
        
        // Sentiment contribution (20%)
        if ($sentiment > 0) {
            $score += 0.2 * $sentiment; // sentiment is [-1, 1]
        }
        
        // Trend strength contribution (10%)
        if ($adx > 25) {
            $score += 0.1 * min(1, ($adx - 25) / 50);
        }
        
        return min(1, max(0, $score));
    }

    /**
     * Calculate bearish score
     */
    private function calculateBearScore(string $trendDirection, float $rsi, float $sentiment, float $adx): float
    {
        $score = 0;
        
        // Trend contribution (40%)
        if ($trendDirection === 'down') {
            $score += 0.4;
        } elseif ($trendDirection === 'neutral') {
            $score += 0.2;
        }
        
        // Momentum contribution (30%)
        if ($rsi < 50) {
            $score += 0.3 * ((50 - $rsi) / 50); // Scale 0-0.3
        }
        
        // Sentiment contribution (20%)
        if ($sentiment < 0) {
            $score += 0.2 * abs($sentiment);
        }
        
        // Trend strength contribution (10%)
        if ($adx > 25) {
            $score += 0.1 * min(1, ($adx - 25) / 50);
        }
        
        return min(1, max(0, $score));
    }

    /**
     * Calculate neutral score
     */
    private function calculateNeutralScore(float $adx, string $volatilityRegime, float $absSentiment): float
    {
        $score = 0;
        
        // Weak trend contribution (50%)
        if ($adx < 25) {
            $score += 0.5 * (1 - ($adx / 25));
        }
        
        // Low volatility contribution (30%)
        if (in_array($volatilityRegime, ['low', 'ultra_low'])) {
            $score += 0.3;
        } elseif ($volatilityRegime === 'medium') {
            $score += 0.15;
        }
        
        // Neutral sentiment contribution (20%)
        if ($absSentiment < 0.3) {
            $score += 0.2 * (1 - ($absSentiment / 0.3));
        }
        
        return min(1, max(0, $score));
    }

    /**
     * Determine market phase within regime
     */
    private function determinePhase(string $regime, float $trendStrength, float $rsi, string $sentimentTrend): string
    {
        if ($regime === 'bull') {
            // Bullish phases
            if ($rsi < 40 && $sentimentTrend === 'improving') {
                return 'accumulation';
            } elseif ($rsi >= 40 && $rsi <= 70 && $trendStrength > 0.5) {
                return 'markup';
            } elseif ($rsi > 70 || $sentimentTrend === 'weakening') {
                return 'distribution';
            }
            return 'markup';
            
        } elseif ($regime === 'bear') {
            // Bearish phases
            if ($rsi > 60 && $sentimentTrend === 'weakening') {
                return 'distribution';
            } elseif ($rsi >= 30 && $rsi <= 60 && $trendStrength > 0.5) {
                return 'markdown';
            } elseif ($rsi < 30 || $sentimentTrend === 'improving') {
                return 'accumulation';
            }
            return 'markdown';
            
        } else {
            // Neutral/Consolidation phases
            if ($trendStrength < 0.3) {
                return 'consolidation';
            } elseif ($rsi < 45) {
                return 'accumulation';
            } elseif ($rsi > 55) {
                return 'distribution';
            }
            return 'ranging';
        }
    }

    /**
     * Build regime characteristics
     */
    private function buildCharacteristics(
        string $regime,
        string $trendDirection,
        float $adx,
        float $rsi,
        string $volatilityRegime,
        float $sentiment
    ): array {
        $characteristics = [];
        
        // Trend characteristics
        $characteristics[] = "Trend: " . ucfirst($trendDirection);
        $characteristics[] = "Trend Strength (ADX): " . round($adx, 1);
        
        // Momentum characteristics
        if ($rsi > 70) {
            $characteristics[] = "Overbought conditions (RSI: " . round($rsi, 1) . ")";
        } elseif ($rsi < 30) {
            $characteristics[] = "Oversold conditions (RSI: " . round($rsi, 1) . ")";
        } else {
            $characteristics[] = "Neutral momentum (RSI: " . round($rsi, 1) . ")";
        }
        
        // Volatility characteristics
        $characteristics[] = "Volatility: " . ucfirst($volatilityRegime);
        
        // Sentiment characteristics
        if ($sentiment > 0.3) {
            $characteristics[] = "Positive market sentiment";
        } elseif ($sentiment < -0.3) {
            $characteristics[] = "Negative market sentiment";
        } else {
            $characteristics[] = "Mixed market sentiment";
        }
        
        // Regime-specific characteristics
        if ($regime === 'bull') {
            $characteristics[] = "Favorable for long positions";
            $characteristics[] = "Watch for distribution signals";
        } elseif ($regime === 'bear') {
            $characteristics[] = "Favorable for short positions";
            $characteristics[] = "Watch for capitulation signs";
        } else {
            $characteristics[] = "Range-bound market";
            $characteristics[] = "Await directional breakout";
        }
        
        return $characteristics;
    }

    /**
     * Calculate regime classification confidence
     */
    private function calculateConfidence(
        float $adx,
        float $scoreDifference,
        string $volatilityRegime,
        float $sentimentConfidence
    ): float {
        $confidence = 0;
        
        // Strong ADX = higher confidence (30%)
        if ($adx > 25) {
            $confidence += 0.3 * min(1, $adx / 50);
        } else {
            $confidence += 0.1;
        }
        
        // Clear score difference = higher confidence (30%)
        $confidence += 0.3 * min(1, $scoreDifference);
        
        // Low volatility = higher confidence (20%)
        $volConfidence = match($volatilityRegime) {
            'ultra_low', 'low' => 0.2,
            'medium' => 0.15,
            'high' => 0.1,
            default => 0.05
        };
        $confidence += $volConfidence;
        
        // Sentiment confidence (20%)
        $confidence += 0.2 * $sentimentConfidence;
        
        return min(1, max(0, $confidence));
    }

    /**
     * Get actionable insights based on regime
     */
    public function getActionableInsights(MarketRegime $regime): array
    {
        $insights = [];
        
        if ($regime->isBullish()) {
            $insights[] = [
                'type' => 'opportunity',
                'message' => 'Consider long positions or holding existing longs',
                'priority' => 'high',
            ];
            
            if ($regime->strength > 0.7) {
                $insights[] = [
                    'type' => 'warning',
                    'message' => 'Strong bullish momentum - watch for exhaustion signals',
                    'priority' => 'medium',
                ];
            }
            
        } elseif ($regime->isBearish()) {
            $insights[] = [
                'type' => 'risk',
                'message' => 'Consider protective stops or reducing long exposure',
                'priority' => 'high',
            ];
            
            if ($regime->strength > 0.7) {
                $insights[] = [
                    'type' => 'opportunity',
                    'message' => 'Strong bearish momentum - potential short opportunities',
                    'priority' => 'medium',
                ];
            }
            
        } else {
            $insights[] = [
                'type' => 'neutral',
                'message' => 'Range-bound market - trade the range or wait for breakout',
                'priority' => 'medium',
            ];
            
            $insights[] = [
                'type' => 'strategy',
                'message' => 'Consider range trading strategies or wait for clearer trend',
                'priority' => 'low',
            ];
        }
        
        // Confidence-based insights
        if ($regime->confidence < 0.5) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Low classification confidence - use smaller position sizes',
                'priority' => 'high',
            ];
        }
        
        return $insights;
    }
}
