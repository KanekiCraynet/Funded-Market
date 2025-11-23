<?php

/**
 * Shared Helper Functions for Market Analysis Platform
 * 
 * These functions are globally available throughout the application.
 * They provide utility methods for number formatting, calculations, and common operations.
 */

if (!function_exists('format_number')) {
    /**
     * Format a number with specified decimal places
     */
    function format_number(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals);
    }
}

if (!function_exists('format_percent')) {
    /**
     * Format a number as a percentage
     */
    function format_percent(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals) . '%';
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency
     */
    function format_currency(float $amount, string $currency = 'USD', int $decimals = 2): string
    {
        return $currency . ' ' . number_format($amount, $decimals);
    }
}

if (!function_exists('calculate_percentage_change')) {
    /**
     * Calculate percentage change between two values
     */
    function calculate_percentage_change(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return (($new - $old) / abs($old)) * 100;
    }
}

if (!function_exists('safe_division')) {
    /**
     * Perform safe division with default value for division by zero
     */
    function safe_division(float $numerator, float $denominator, float $default = 0.0): float
    {
        return $denominator != 0 ? $numerator / $denominator : $default;
    }
}

if (!function_exists('tanh')) {
    /**
     * Hyperbolic tangent function (for normalization to [-1, 1])
     * PHP has this built-in, but we define it for consistency
     */
    function tanh(float $x): float
    {
        if (function_exists('\tanh')) {
            return \tanh($x);
        }
        return (exp($x) - exp(-$x)) / (exp($x) + exp(-$x));
    }
}

if (!function_exists('clamp')) {
    /**
     * Clamp a value between min and max
     */
    function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}

if (!function_exists('normalize_score')) {
    /**
     * Normalize a score to [-1, 1] range using tanh
     */
    function normalize_score(float $value, float $scalingFactor = 1.0): float
    {
        return tanh($value / $scalingFactor);
    }
}

if (!function_exists('market_status')) {
    /**
     * Get market status based on current time
     * This is a simplified version - in production, you'd want proper market hours
     */
    function market_status(): string
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        
        // Simple check: Mon-Fri 9am-5pm considered "open"
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 17) {
            return 'open';
        }
        return 'closed';
    }
}

if (!function_exists('is_market_open')) {
    /**
     * Check if market is currently open
     */
    function is_market_open(): bool
    {
        return market_status() === 'open';
    }
}

if (!function_exists('format_large_number')) {
    /**
     * Format large numbers with K, M, B, T suffixes
     */
    function format_large_number(float $number, int $decimals = 2): string
    {
        if ($number >= 1_000_000_000_000) {
            return number_format($number / 1_000_000_000_000, $decimals) . 'T';
        }
        if ($number >= 1_000_000_000) {
            return number_format($number / 1_000_000_000, $decimals) . 'B';
        }
        if ($number >= 1_000_000) {
            return number_format($number / 1_000_000, $decimals) . 'M';
        }
        if ($number >= 1_000) {
            return number_format($number / 1_000, $decimals) . 'K';
        }
        return number_format($number, $decimals);
    }
}

if (!function_exists('calculate_volatility')) {
    /**
     * Calculate simple volatility (standard deviation of returns)
     */
    function calculate_volatility(array $prices): float
    {
        if (count($prices) < 2) {
            return 0.0;
        }
        
        // Calculate returns
        $returns = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] != 0) {
                $returns[] = ($prices[$i] - $prices[$i - 1]) / $prices[$i - 1];
            }
        }
        
        if (empty($returns)) {
            return 0.0;
        }
        
        // Calculate standard deviation
        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(fn($r) => pow($r - $mean, 2), $returns)) / count($returns);
        
        return sqrt($variance);
    }
}

if (!function_exists('calculate_sharpe_ratio')) {
    /**
     * Calculate Sharpe ratio (return/risk ratio)
     */
    function calculate_sharpe_ratio(float $return, float $volatility, float $riskFreeRate = 0.02): float
    {
        if ($volatility == 0) {
            return 0.0;
        }
        return ($return - $riskFreeRate) / $volatility;
    }
}

if (!function_exists('round_to_significant')) {
    /**
     * Round to specified number of significant figures
     */
    function round_to_significant(float $number, int $significantFigures = 4): float
    {
        if ($number == 0) {
            return 0.0;
        }
        
        $magnitude = floor(log10(abs($number)));
        $decimals = $significantFigures - $magnitude - 1;
        
        return round($number, (int) $decimals);
    }
}
