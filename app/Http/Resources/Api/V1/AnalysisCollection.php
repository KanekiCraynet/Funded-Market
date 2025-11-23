<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AnalysisCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($analysis) use ($request) {
                return [
                    'id' => $analysis->id,
                    'symbol' => $analysis->instrument->symbol,
                    'instrument_name' => $analysis->instrument->name,
                    'instrument_type' => $analysis->instrument->type,
                    
                    // Core results (simplified for list view)
                    'final_score' => round($analysis->final_score, 4),
                    'recommendation' => $analysis->recommendation,
                    'confidence' => round($analysis->confidence, 3),
                    'risk_level' => $analysis->risk_level,
                    
                    // Position sizing
                    'position_size_percent' => round($analysis->position_size_recommendation['size_percent'] ?? 10, 1),
                    
                    // Price targets (simplified)
                    'near_term_target' => round($analysis->price_targets['near_term'] ?? 0, 4),
                    'stop_loss' => round($analysis->price_targets['stop_loss'] ?? 0, 4),
                    
                    // Status indicators
                    'status' => $this->getAnalysisStatus($analysis),
                    'performance' => $this->getAnalysisPerformance($analysis),
                    
                    // Timestamps
                    'created_at' => $analysis->created_at->toISOString(),
                    'created_at_human' => $analysis->created_at->diffForHumans(),
                    
                    // Quick summary
                    'summary' => $this->generateQuickSummary($analysis),
                ];
            }),
        ];
    }

    private function getAnalysisStatus($analysis): string
    {
        $hoursOld = $analysis->created_at->diffInHours(now());
        
        if ($hoursOld < 1) {
            return 'fresh';
        } elseif ($hoursOld < 24) {
            return 'recent';
        } elseif ($hoursOld < 168) { // 1 week
            return 'moderate';
        } else {
            return 'stale';
        }
    }

    private function getAnalysisPerformance($analysis): array
    {
        // This would typically compare the analysis with actual market performance
        // For now, return placeholder data
        return [
            'accuracy_score' => null, // Would be calculated based on actual performance
            'profit_loss' => null,    // Would be calculated based on price movement
            'status' => 'pending',    // pending, profitable, loss, breakeven
        ];
    }

    private function generateQuickSummary($analysis): string
    {
        $score = $analysis->final_score;
        $rec = $analysis->recommendation;
        $confidence = $analysis->confidence;
        
        $strength = $confidence > 0.7 ? 'Strong' : ($confidence > 0.4 ? 'Moderate' : 'Weak');
        
        return match($rec) {
            'BUY' => "{$strength} buy signal with " . round($confidence * 100, 0) . "% confidence",
            'SELL' => "{$strength} sell signal with " . round($confidence * 100, 0) . "% confidence", 
            'HOLD' => "Neutral recommendation with " . round($confidence * 100, 0) . "% confidence",
            default => "Analysis with " . round($confidence * 100, 0) . "% confidence",
        };
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'count' => $this->collection->count(),
                'recommendation_summary' => $this->getRecommendationSummary(),
                'confidence_summary' => $this->getConfidenceSummary(),
                'risk_summary' => $this->getRiskSummary(),
            ],
        ];
    }

    private function getRecommendationSummary(): array
    {
        $summary = [
            'BUY' => 0,
            'SELL' => 0,
            'HOLD' => 0,
        ];

        foreach ($this->collection as $analysis) {
            $summary[$analysis->recommendation]++;
        }

        $total = array_sum($summary);
        
        return [
            'counts' => $summary,
            'percentages' => [
                'BUY' => $total > 0 ? round(($summary['BUY'] / $total) * 100, 1) : 0,
                'SELL' => $total > 0 ? round(($summary['SELL'] / $total) * 100, 1) : 0,
                'HOLD' => $total > 0 ? round(($summary['HOLD'] / $total) * 100, 1) : 0,
            ],
        ];
    }

    private function getConfidenceSummary(): array
    {
        $confidences = $this->collection->pluck('confidence');
        
        if ($confidences->isEmpty()) {
            return [
                'average' => 0,
                'highest' => 0,
                'lowest' => 0,
                'distribution' => ['high' => 0, 'medium' => 0, 'low' => 0],
            ];
        }

        $distribution = [
            'high' => $confidences->where('>', 0.7)->count(),
            'medium' => $confidences->between(0.4, 0.7)->count(),
            'low' => $confidences->where('<', 0.4)->count(),
        ];

        return [
            'average' => round($confidences->avg(), 3),
            'highest' => round($confidences->max(), 3),
            'lowest' => round($confidences->min(), 3),
            'distribution' => $distribution,
        ];
    }

    private function getRiskSummary(): array
    {
        $risks = [
            'LOW' => 0,
            'MEDIUM' => 0,
            'HIGH' => 0,
        ];

        foreach ($this->collection as $analysis) {
            $risks[$analysis->risk_level]++;
        }

        $total = array_sum($risks);
        
        return [
            'counts' => $risks,
            'percentages' => [
                'LOW' => $total > 0 ? round(($risks['LOW'] / $total) * 100, 1) : 0,
                'MEDIUM' => $total > 0 ? round(($risks['MEDIUM'] / $total) * 100, 1) : 0,
                'HIGH' => $total > 0 ? round(($risks['HIGH'] / $total) * 100, 1) : 0,
            ],
        ];
    }
}