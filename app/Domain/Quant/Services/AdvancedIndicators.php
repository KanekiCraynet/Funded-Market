<?php

namespace App\Domain\Quant\Services;

class AdvancedIndicators
{
    /**
     * Calculate Trend Stability Score using Linear Regression R²
     * 
     * R² measures how well the trend line fits the data
     * Higher R² (closer to 1) = more stable trend
     * Lower R² (closer to 0) = choppy/unstable trend
     */
    public function calculateTrendStabilityScore(array $closes): float
    {
        $n = count($closes);
        
        if ($n < 20) {
            return 0.5; // Not enough data
        }
        
        // Use last 50 periods for trend stability
        $closes = array_slice($closes, -50);
        $n = count($closes);
        
        // Linear regression: y = mx + b
        $x = range(0, $n - 1);
        $y = array_values($closes);
        
        // Calculate means
        $xMean = array_sum($x) / $n;
        $yMean = array_sum($y) / $n;
        
        // Calculate regression coefficients
        $numerator = 0;
        $denominatorX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $xMean) * ($y[$i] - $yMean);
            $denominatorX += pow($x[$i] - $xMean, 2);
        }
        
        if ($denominatorX == 0) {
            return 0.5;
        }
        
        $slope = $numerator / $denominatorX;
        $intercept = $yMean - ($slope * $xMean);
        
        // Calculate R² (coefficient of determination)
        $ssTotal = 0;  // Total sum of squares
        $ssResidual = 0;  // Residual sum of squares
        
        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $ssTotal += pow($y[$i] - $yMean, 2);
            $ssResidual += pow($y[$i] - $predicted, 2);
        }
        
        if ($ssTotal == 0) {
            return 0.5;
        }
        
        $rSquared = 1 - ($ssResidual / $ssTotal);
        
        // Clamp between 0 and 1
        return max(0, min(1, $rSquared));
    }

    /**
     * Calculate Breakout Probability using Bollinger Bandwidth Expansion
     * 
     * High bandwidth expansion = higher breakout probability
     * Bandwidth contraction followed by expansion = strong breakout signal
     */
    public function calculateBreakoutProbability(array $closes, array $highs, array $lows): float
    {
        $n = count($closes);
        
        if ($n < 40) {
            return 0.5;
        }
        
        // Calculate Bollinger Bands (20 period, 2 std dev)
        $period = 20;
        $stdDevMultiplier = 2;
        
        $recentCloses = array_slice($closes, -$period);
        $sma = array_sum($recentCloses) / $period;
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($recentCloses as $close) {
            $variance += pow($close - $sma, 2);
        }
        $stdDev = sqrt($variance / $period);
        
        $upperBand = $sma + ($stdDevMultiplier * $stdDev);
        $lowerBand = $sma - ($stdDevMultiplier * $stdDev);
        $bandwidth = ($upperBand - $lowerBand) / $sma;
        
        // Calculate historical bandwidth (last 40 periods)
        $bandwidthHistory = [];
        for ($i = $period; $i < min($n, 40); $i++) {
            $slice = array_slice($closes, $i - $period, $period);
            $sliceSma = array_sum($slice) / $period;
            
            $sliceVariance = 0;
            foreach ($slice as $close) {
                $sliceVariance += pow($close - $sliceSma, 2);
            }
            $sliceStdDev = sqrt($sliceVariance / $period);
            
            $sliceUpper = $sliceSma + ($stdDevMultiplier * $sliceStdDev);
            $sliceLower = $sliceSma - ($stdDevMultiplier * $sliceStdDev);
            $bandwidthHistory[] = ($sliceUpper - $sliceLower) / $sliceSma;
        }
        
        if (empty($bandwidthHistory)) {
            return 0.5;
        }
        
        $avgBandwidth = array_sum($bandwidthHistory) / count($bandwidthHistory);
        
        // Bandwidth expansion rate
        $expansionRate = $avgBandwidth > 0 ? ($bandwidth - $avgBandwidth) / $avgBandwidth : 0;
        
        // Check if price is near bands (potential breakout)
        $currentPrice = end($closes);
        $upperProximity = abs($currentPrice - $upperBand) / ($upperBand - $sma);
        $lowerProximity = abs($currentPrice - $lowerBand) / ($sma - $lowerBand);
        $nearBand = min($upperProximity, $lowerProximity) < 0.3;
        
        // Check recent volume surge (if available)
        $volumeSurge = $this->detectVolumeSurge($highs, $lows);
        
        // Calculate breakout probability
        // Higher expansion + near band + volume surge = higher probability
        $probability = tanh($expansionRate * 5); // Normalize expansion rate
        
        if ($nearBand) {
            $probability += 0.2;
        }
        
        if ($volumeSurge) {
            $probability += 0.15;
        }
        
        // Clamp between 0 and 1
        return max(0, min(1, $probability));
    }

    /**
     * Detect Volatility Clustering using GARCH-like approximation
     * 
     * Volatility clustering: high volatility periods tend to cluster together
     * Returns true if current volatility is elevated
     */
    public function detectVolatilityCluster(array $closes): bool
    {
        $n = count($closes);
        
        if ($n < 30) {
            return false;
        }
        
        // Calculate returns
        $returns = [];
        for ($i = 1; $i < $n; $i++) {
            $returns[] = ($closes[$i] - $closes[$i - 1]) / $closes[$i - 1];
        }
        
        // Calculate rolling volatility (5-period windows)
        $windowSize = 5;
        $volatilities = [];
        
        for ($i = 0; $i <= count($returns) - $windowSize; $i++) {
            $window = array_slice($returns, $i, $windowSize);
            $mean = array_sum($window) / $windowSize;
            
            $variance = 0;
            foreach ($window as $return) {
                $variance += pow($return - $mean, 2);
            }
            $volatilities[] = sqrt($variance / $windowSize);
        }
        
        if (count($volatilities) < 2) {
            return false;
        }
        
        // Get recent volatility (last 3 windows)
        $recentVol = array_slice($volatilities, -3);
        $recentAvg = array_sum($recentVol) / count($recentVol);
        
        // Get historical volatility (exclude recent)
        $historicalVol = array_slice($volatilities, 0, -3);
        $historicalAvg = count($historicalVol) > 0 
            ? array_sum($historicalVol) / count($historicalVol)
            : $recentAvg;
        
        // Volatility clustering detected if recent vol > 1.5x historical
        return $recentAvg > ($historicalAvg * 1.5);
    }

    /**
     * Normalize all indicators using tanh scaling
     * 
     * Maps any value to [-1, 1] range with smooth transition
     */
    public function normalizeIndicator(float $value, float $scalingFactor = 1.0): float
    {
        return tanh($value / $scalingFactor);
    }

    /**
     * Normalize RSI to [-1, 1] range
     */
    public function normalizeRSI(float $rsi): float
    {
        // RSI is [0, 100], center at 50
        // < 30 = oversold (negative)
        // > 70 = overbought (positive)
        return ($rsi - 50) / 50;
    }

    /**
     * Normalize MACD to [-1, 1] range
     */
    public function normalizeMACD(array $macdData, float $avgPrice): float
    {
        $macdValue = $macdData['macd'] ?? 0;
        $signalValue = $macdData['signal'] ?? 0;
        $histogram = $macdValue - $signalValue;
        
        // Scale by average price to make it relative
        $scalingFactor = $avgPrice * 0.01; // 1% of price
        
        return $scalingFactor > 0 ? tanh($histogram / $scalingFactor) : 0;
    }

    /**
     * Normalize ADX to [0, 1] range
     */
    public function normalizeADX(float $adx): float
    {
        // ADX is [0, 100]
        // < 25 = weak trend
        // > 50 = strong trend
        return min(1, $adx / 100);
    }

    /**
     * Normalize volatility to [0, 1] range
     */
    public function normalizeVolatility(float $volatility): float
    {
        // Historical volatility is typically 0-100% annualized
        // Scale to [0, 1]
        return min(1, $volatility / 1.0); // 100% vol = 1.0
    }

    /**
     * Detect volume surge (simple approximation)
     */
    private function detectVolumeSurge(array $highs, array $lows): bool
    {
        $n = count($highs);
        
        if ($n < 20) {
            return false;
        }
        
        // Calculate recent range expansion
        $recentRange = array_slice($highs, -5);
        $recentAvg = array_sum($recentRange) / count($recentRange);
        
        $historicalRange = array_slice($highs, -20, 15);
        $historicalAvg = array_sum($historicalRange) / count($historicalRange);
        
        // Volume surge if recent range > 1.3x historical
        return $historicalAvg > 0 && ($recentAvg / $historicalAvg) > 1.3;
    }

    /**
     * Calculate indicator confidence based on data quality
     */
    public function calculateIndicatorConfidence(array $closes, array $volumes): float
    {
        $n = count($closes);
        
        // More data = higher confidence
        $dataConfidence = min(1, $n / 200);
        
        // Consistent data = higher confidence (low variance in returns)
        $returns = [];
        for ($i = 1; $i < min($n, 50); $i++) {
            $returns[] = abs(($closes[$i] - $closes[$i - 1]) / $closes[$i - 1]);
        }
        
        $avgReturn = count($returns) > 0 ? array_sum($returns) / count($returns) : 0;
        $consistencyConfidence = 1 - min(1, $avgReturn * 10); // Lower volatility = higher confidence
        
        // Volume data quality
        $avgVolume = count($volumes) > 0 ? array_sum($volumes) / count($volumes) : 1;
        $volumeConfidence = $avgVolume > 0 ? 1.0 : 0.5;
        
        // Combined confidence
        return ($dataConfidence + $consistencyConfidence + $volumeConfidence) / 3;
    }
}
