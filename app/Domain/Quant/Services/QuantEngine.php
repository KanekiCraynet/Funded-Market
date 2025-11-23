<?php

namespace App\Domain\Quant\Services;

use App\Domain\Market\Models\Instrument;
use App\Domain\Market\Models\MarketData;
use App\Domain\Quant\Models\QuantIndicator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuantEngine
{
    private const CACHE_TTL = 300; // 5 minutes

    public function calculateIndicators(string $symbol, int $period = 200): array
    {
        $cacheKey = "quant_indicators:{$symbol}:{$period}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol, $period) {
            $marketData = $this->getMarketData($symbol, $period);
            
            if ($marketData->count() < 50) {
                return $this->getEmptyIndicators();
            }

            return [
                'trend' => $this->calculateTrendIndicators($marketData),
                'momentum' => $this->calculateMomentumIndicators($marketData),
                'volatility' => $this->calculateVolatilityIndicators($marketData),
                'volume' => $this->calculateVolumeIndicators($marketData),
                'composite' => $this->calculateCompositeScore($marketData),
            ];
        });
    }

    private function calculateTrendIndicators(Collection $data): array
    {
        $closes = $data->pluck('close')->toArray();
        
        return [
            'ema_20' => $this->calculateEMA($closes, 20),
            'ema_50' => $this->calculateEMA($closes, 50),
            'ema_200' => $this->calculateEMA($closes, 200),
            'sma_20' => $this->calculateSMA($closes, 20),
            'sma_50' => $this->calculateSMA($closes, 50),
            'sma_200' => $this->calculateSMA($closes, 200),
            'adx' => $this->calculateADX($data, 14),
            'macd' => $this->calculateMACD($closes),
            'trend_strength' => $this->calculateTrendStrength($closes),
            'direction' => $this->getTrendDirection($closes),
        ];
    }

    private function calculateMomentumIndicators(Collection $data): array
    {
        $closes = $data->pluck('close')->toArray();
        
        return [
            'rsi' => $this->calculateRSI($closes, 14),
            'stochastic' => $this->calculateStochastic($data, 14),
            'williams_r' => $this->calculateWilliamsR($data, 14),
            'momentum' => $this->calculateMomentum($closes, 10),
            'rate_of_change' => $this->calculateROC($closes, 12),
            'commodity_channel_index' => $this->calculateCCI($data, 20),
        ];
    }

    private function calculateVolatilityIndicators(Collection $data): array
    {
        $closes = $data->pluck('close')->toArray();
        
        return [
            'atr' => $this->calculateATR($data, 14),
            'bollinger_bands' => $this->calculateBollingerBands($closes, 20, 2),
            'volatility_regime' => $this->classifyVolatilityRegime($closes),
            'historical_volatility' => $this->calculateHistoricalVolatility($closes, 30),
            'volatility_ratio' => $this->calculateVolatilityRatio($closes),
        ];
    }

    private function calculateVolumeIndicators(Collection $data): array
    {
        $volumes = $data->pluck('volume')->toArray();
        $closes = $data->pluck('close')->toArray();
        
        return [
            'volume_sma' => $this->calculateSMA($volumes, 20),
            'volume_ratio' => $this->calculateVolumeRatio($volumes),
            'on_balance_volume' => $this->calculateOBV($data),
            'volume_profile' => $this->calculateVolumeProfile($data),
            'vwap' => $this->calculateVWAP($data),
        ];
    }

    private function calculateCompositeScore(Collection $data): array
    {
        $trend = $this->calculateTrendIndicators($data);
        $momentum = $this->calculateMomentumIndicators($data);
        $volatility = $this->calculateVolatilityIndicators($data);
        
        // Normalize and weight different indicators
        $trendScore = $this->normalizeTrendScore($trend);
        $momentumScore = $this->normalizeMomentumScore($momentum);
        $volatilityScore = $this->normalizeVolatilityScore($volatility);
        
        // Dynamic weighting based on volatility regime
        $volatilityRegime = $volatility['volatility_regime'];
        $weights = $this->getDynamicWeights($volatilityRegime);
        
        $compositeScore = (
            $weights['trend'] * $trendScore +
            $weights['momentum'] * $momentumScore +
            $weights['volatility'] * $volatilityScore
        );

        return [
            'score' => $tanh($compositeScore), // Normalize to [-1, 1]
            'trend_score' => $trendScore,
            'momentum_score' => $momentumScore,
            'volatility_score' => $volatilityScore,
            'weights' => $weights,
            'confidence' => $this->calculateConfidence($data),
        ];
    }

    // Technical Indicator Calculations

    private function calculateEMA(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return end($prices) ?? 0;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($prices, 0, $period)) / $period;

        for ($i = $period; $i < count($prices); $i++) {
            $ema = ($prices[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }

        return $ema;
    }

    private function calculateSMA(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return 0;
        }

        return array_sum(array_slice($prices, -$period)) / $period;
    }

    private function calculateRSI(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 50;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    private function calculateADX(Collection $data, int $period = 14): float
    {
        if ($data->count() < $period + 1) {
            return 0;
        }

        $highs = $data->pluck('high')->toArray();
        $lows = $data->pluck('low')->toArray();
        $closes = $data->pluck('close')->toArray();

        $tr = [];
        $plusDM = [];
        $minusDM = [];

        for ($i = 1; $i < count($highs); $i++) {
            $hl = $highs[$i] - $lows[$i];
            $hc = abs($highs[$i] - $closes[$i - 1]);
            $lc = abs($lows[$i] - $closes[$i - 1]);
            
            $tr[] = max($hl, $hc, $lc);
            
            $upMove = $highs[$i] - $highs[$i - 1];
            $downMove = $lows[$i - 1] - $lows[$i];
            
            $plusDM[] = ($upMove > $downMove && $upMove > 0) ? $upMove : 0;
            $minusDM[] = ($downMove > $upMove && $downMove > 0) ? $downMove : 0;
        }

        $atr = $this->calculateEMA($tr, $period);
        $plusDI = $this->calculateEMA($plusDM, $period) / $atr * 100;
        $minusDI = $this->calculateEMA($minusDM, $period) / $atr * 100;

        $dx = abs($plusDI - $minusDI) / ($plusDI + $minusDI) * 100;
        return $this->calculateEMA(array_fill(0, $period, $dx), $period);
    }

    private function calculateMACD(array $prices, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $emaFast = $this->calculateEMA($prices, $fast);
        $emaSlow = $this->calculateEMA($prices, $slow);
        $macdLine = $emaFast - $emaSlow;

        // For signal line, we'd need historical MACD values
        // Simplified version here
        return [
            'macd' => $macdLine,
            'signal' => $macdLine * 0.9, // Simplified
            'histogram' => $macdLine * 0.1, // Simplified
        ];
    }

    private function calculateATR(Collection $data, int $period = 14): float
    {
        if ($data->count() < $period + 1) {
            return 0;
        }

        $tr = [];
        for ($i = 1; $i < $data->count(); $i++) {
            $current = $data->skip($i)->first();
            $previous = $data->skip($i - 1)->first();
            
            $hl = $current->high - $current->low;
            $hc = abs($current->high - $previous->close);
            $lc = abs($current->low - $previous->close);
            
            $tr[] = max($hl, $hc, $lc);
        }

        return $this->calculateEMA($tr, $period);
    }

    private function calculateBollingerBands(array $prices, int $period = 20, float $stdDev = 2): array
    {
        if (count($prices) < $period) {
            return [
                'upper' => 0,
                'middle' => 0,
                'lower' => 0,
                'bandwidth' => 0,
            ];
        }

        $recentPrices = array_slice($prices, -$period);
        $sma = array_sum($recentPrices) / $period;
        
        $variance = 0;
        foreach ($recentPrices as $price) {
            $variance += pow($price - $sma, 2);
        }
        $variance /= $period;
        $standardDeviation = sqrt($variance);

        $upper = $sma + ($standardDeviation * $stdDev);
        $lower = $sma - ($standardDeviation * $stdDev);
        $bandwidth = ($upper - $lower) / $sma * 100;

        return [
            'upper' => $upper,
            'middle' => $sma,
            'lower' => $lower,
            'bandwidth' => $bandwidth,
        ];
    }

    private function calculateStochastic(Collection $data, int $period = 14): array
    {
        if ($data->count() < $period) {
            return ['k' => 50, 'd' => 50];
        }

        $recent = $data->take(-$period);
        $highest = $recent->max('high');
        $lowest = $recent->min('low');
        $current = $recent->last()->close;

        $k = (($current - $lowest) / ($highest - $lowest)) * 100;
        $d = $k * 0.9; // Simplified smoothing

        return ['k' => $k, 'd' => $d];
    }

    private function calculateWilliamsR(Collection $data, int $period = 14): float
    {
        if ($data->count() < $period) {
            return -50;
        }

        $recent = $data->take(-$period);
        $highest = $recent->max('high');
        $lowest = $recent->min('low');
        $current = $recent->last()->close;

        return (($highest - $current) / ($highest - $lowest)) * -100;
    }

    private function calculateMomentum(array $prices, int $period = 10): float
    {
        if (count($prices) < $period + 1) {
            return 0;
        }

        $current = end($prices);
        $past = $prices[count($prices) - $period - 1];

        return (($current - $past) / $past) * 100;
    }

    private function calculateROC(array $prices, int $period = 12): float
    {
        return $this->calculateMomentum($prices, $period);
    }

    private function calculateCCI(Collection $data, int $period = 20): float
    {
        if ($data->count() < $period) {
            return 0;
        }

        $recent = $data->take(-$period);
        $typicalPrices = $recent->map(fn($d) => ($d->high + $d->low + $d->close) / 3);
        $sma = $typicalPrices->avg();
        
        $meanDeviation = $typicalPrices->map(fn($tp) => abs($tp - $sma))->avg();
        
        if ($meanDeviation == 0) {
            return 0;
        }

        $currentTP = $typicalPrices->last();
        return (($currentTP - $sma) / (0.015 * $meanDeviation));
    }

    private function classifyVolatilityRegime(array $prices): string
    {
        if (count($prices) < 50) {
            return 'medium';
        }

        $returns = [];
        for ($i = 1; $i < count($prices); $i++) {
            $returns[] = ($prices[$i] - $prices[$i - 1]) / $prices[$i - 1];
        }

        $volatility = sqrt(array_sum(array_map(fn($r) => $r * $r, $returns)) / count($returns)) * sqrt(252);

        if ($volatility < 0.15) {
            return 'low';
        } elseif ($volatility < 0.30) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    private function calculateHistoricalVolatility(array $prices, int $period = 30): float
    {
        if (count($prices) < $period + 1) {
            return 0;
        }

        $returns = [];
        for ($i = 1; $i <= $period; $i++) {
            $currentIdx = count($prices) - $i;
            $prevIdx = $currentIdx - 1;
            $returns[] = ($prices[$currentIdx] - $prices[$prevIdx]) / $prices[$prevIdx];
        }

        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(fn($r) => pow($r - $mean, 2), $returns)) / count($returns);
        
        return sqrt($variance) * sqrt(252); // Annualized
    }

    private function calculateVolatilityRatio(array $prices): float
    {
        if (count($prices) < 20) {
            return 1;
        }

        $shortVol = $this->calculateHistoricalVolatility(array_slice($prices, -10), 10);
        $longVol = $this->calculateHistoricalVolatility(array_slice($prices, -20), 20);

        return $longVol > 0 ? $shortVol / $longVol : 1;
    }

    private function calculateVolumeRatio(array $volumes): float
    {
        if (count($volumes) < 20) {
            return 1;
        }

        $current = end($volumes);
        $average = array_sum(array_slice($volumes, -20)) / 20;

        return $average > 0 ? $current / $average : 1;
    }

    private function calculateOBV(Collection $data): float
    {
        $obv = 0;
        $previousClose = null;

        foreach ($data as $candle) {
            if ($previousClose !== null) {
                if ($candle->close > $previousClose) {
                    $obv += $candle->volume;
                } elseif ($candle->close < $previousClose) {
                    $obv -= $candle->volume;
                }
            }
            $previousClose = $candle->close;
        }

        return $obv;
    }

    private function calculateVolumeProfile(Collection $data): array
    {
        $profile = [];
        $totalVolume = $data->sum('volume');

        foreach ($data as $candle) {
            $price = round($candle->close, 2);
            if (!isset($profile[$price])) {
                $profile[$price] = 0;
            }
            $profile[$price] += $candle->volume;
        }

        arsort($profile);
        $poc = array_key_first($profile); // Point of Control

        return [
            'poc' => $poc,
            'value_area' => $this->calculateValueArea($profile, $totalVolume),
            'profile' => array_slice($profile, 0, 20, true), // Top 20 price levels
        ];
    }

    private function calculateValueArea(array $profile, float $totalVolume): array
    {
        $targetVolume = $totalVolume * 0.7; // 70% of volume
        $currentVolume = 0;
        $valueArea = [];

        foreach ($profile as $price => $volume) {
            $currentVolume += $volume;
            $valueArea[] = $price;
            if ($currentVolume >= $targetVolume) {
                break;
            }
        }

        return [
            'high' => max($valueArea),
            'low' => min($valueArea),
        ];
    }

    private function calculateVWAP(Collection $data): float
    {
        $totalVolume = 0;
        $totalValue = 0;

        foreach ($data as $candle) {
            $typicalPrice = ($candle->high + $candle->low + $candle->close) / 3;
            $totalValue += $typicalPrice * $candle->volume;
            $totalVolume += $candle->volume;
        }

        return $totalVolume > 0 ? $totalValue / $totalVolume : 0;
    }

    private function calculateTrendStrength(array $closes): float
    {
        if (count($closes) < 50) {
            return 0;
        }

        $ema20 = $this->calculateEMA($closes, 20);
        $ema50 = $this->calculateEMA($closes, 50);
        $current = end($closes);

        $strength = 0;
        if ($current > $ema20 && $ema20 > $ema50) {
            $strength = min(1, ($current - $ema20) / $ema20 * 10);
        } elseif ($current < $ema20 && $ema20 < $ema50) {
            $strength = max(-1, ($current - $ema20) / $ema20 * 10);
        }

        return $strength;
    }

    private function getTrendDirection(array $closes): string
    {
        if (count($closes) < 50) {
            return 'neutral';
        }

        $ema20 = $this->calculateEMA($closes, 20);
        $ema50 = $this->calculateEMA($closes, 50);
        $current = end($closes);

        if ($current > $ema20 && $ema20 > $ema50) {
            return 'bullish';
        } elseif ($current < $ema20 && $ema20 < $ema50) {
            return 'bearish';
        } else {
            return 'neutral';
        }
    }

    private function normalizeTrendScore(array $trend): float
    {
        $score = 0;
        $weight = 1/6;

        // EMA alignment
        if ($trend['ema_20'] > $trend['ema_50'] && $trend['ema_50'] > $trend['ema_200']) {
            $score += $weight;
        } elseif ($trend['ema_20'] < $trend['ema_50'] && $trend['ema_50'] < $trend['ema_200']) {
            $score -= $weight;
        }

        // ADX strength
        $score += ($trend['adx'] / 100) * $weight;

        // MACD
        $score += $trend['macd']['macd'] > $trend['macd']['signal'] ? $weight : -$weight;

        // Trend strength
        $score += $trend['trend_strength'] * $weight;

        // Direction confirmation
        $score += match($trend['direction']) {
            'bullish' => $weight,
            'bearish' => -$weight,
            default => 0,
        };

        return $score;
    }

    private function normalizeMomentumScore(array $momentum): float
    {
        $score = 0;
        $weight = 1/6;

        // RSI
        $rsi = $momentum['rsi'];
        if ($rsi > 70) {
            $score += $weight; // Overbought (potential reversal)
        } elseif ($rsi < 30) {
            $score -= $weight; // Oversold (potential reversal)
        } else {
            $score += ($rsi - 50) / 50 * $weight; // Neutral zone
        }

        // Stochastic
        $stoch = $momentum['stochastic'];
        $score += ($stoch['k'] - 50) / 50 * $weight;

        // Williams %R
        $score += ($momentum['williams_r'] + 50) / 100 * $weight;

        // Momentum
        $score += tanh($momentum['momentum'] / 10) * $weight;

        // Rate of Change
        $score += tanh($momentum['rate_of_change'] / 10) * $weight;

        // CCI
        $score += tanh($momentum['commodity_channel_index'] / 200) * $weight;

        return $score;
    }

    private function normalizeVolatilityScore(array $volatility): float
    {
        $score = 0;
        $weight = 1/3;

        // Volatility regime
        $score += match($volatility['volatility_regime']) {
            'low' => $weight * 0.5,
            'medium' => 0,
            'high' => -$weight * 0.5,
            default => 0,
        };

        // Bollinger Band position
        $bb = $volatility['bollinger_bands'];
        $bbPosition = ($bb['upper'] - $bb['lower']) / $bb['middle'];
        $score += tanh($bbPosition - 1) * $weight;

        // Volatility ratio
        $score += tanh($volatility['volatility_ratio'] - 1) * $weight;

        return $score;
    }

    private function getDynamicWeights(string $volatilityRegime): array
    {
        return match($volatilityRegime) {
            'low' => [
                'trend' => 0.7,
                'momentum' => 0.2,
                'volatility' => 0.1,
            ],
            'medium' => [
                'trend' => 0.5,
                'momentum' => 0.3,
                'volatility' => 0.2,
            ],
            'high' => [
                'trend' => 0.3,
                'momentum' => 0.4,
                'volatility' => 0.3,
            ],
            default => [
                'trend' => 0.5,
                'momentum' => 0.3,
                'volatility' => 0.2,
            ],
        };
    }

    private function calculateConfidence(Collection $data): float
    {
        $dataPoints = $data->count();
        
        // Base confidence on data quality and quantity
        $dataQuality = min(1, $dataPoints / 200); // More data = higher confidence
        
        // Volume consistency
        $volumes = $data->pluck('volume')->toArray();
        $volumeStd = sqrt(array_sum(array_map(
            fn($v) => pow($v - array_sum($volumes) / count($volumes), 2),
            $volumes
        )) / count($volumes));
        $volumeConsistency = 1 - min(1, $volumeStd / (array_sum($volumes) / count($volumes)));
        
        return ($dataQuality + $volumeConsistency) / 2;
    }

    private function getMarketData(string $symbol, int $limit): Collection
    {
        return MarketData::whereHas('instrument', fn($q) => $q->where('symbol', $symbol))
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();
    }

    private function getEmptyIndicators(): array
    {
        return [
            'trend' => [
                'ema_20' => 0, 'ema_50' => 0, 'ema_200' => 0,
                'sma_20' => 0, 'sma_50' => 0, 'sma_200' => 0,
                'adx' => 0, 'trend_strength' => 0, 'direction' => 'neutral',
                'macd' => ['macd' => 0, 'signal' => 0, 'histogram' => 0],
            ],
            'momentum' => [
                'rsi' => 50, 'stochastic' => ['k' => 50, 'd' => 50],
                'williams_r' => -50, 'momentum' => 0, 'rate_of_change' => 0,
                'commodity_channel_index' => 0,
            ],
            'volatility' => [
                'atr' => 0, 'volatility_regime' => 'medium',
                'historical_volatility' => 0, 'volatility_ratio' => 1,
                'bollinger_bands' => ['upper' => 0, 'middle' => 0, 'lower' => 0, 'bandwidth' => 0],
            ],
            'volume' => [
                'volume_sma' => 0, 'volume_ratio' => 1,
                'on_balance_volume' => 0, 'vwap' => 0,
                'volume_profile' => ['poc' => 0, 'value_area' => ['high' => 0, 'low' => 0]],
            ],
            'composite' => [
                'score' => 0, 'trend_score' => 0, 'momentum_score' => 0,
                'volatility_score' => 0, 'confidence' => 0,
                'weights' => ['trend' => 0.5, 'momentum' => 0.3, 'volatility' => 0.2],
            ],
        ];
    }
}

if (!function_exists('tanh')) {
    function tanh(float $x): float
    {
        return (exp($x) - exp(-$x)) / (exp($x) + exp(-$x));
    }
}