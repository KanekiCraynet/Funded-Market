<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->instrument->symbol,
            'instrument_name' => $this->instrument->name,
            'instrument_type' => $this->instrument->type,
            
            // Core analysis results
            'final_score' => round($this->final_score, 4),
            'recommendation' => $this->recommendation,
            'confidence' => round($this->confidence, 3),
            'time_horizon' => $this->time_horizon,
            'risk_level' => $this->risk_level,
            
            // Position sizing and risk management
            'position_size_recommendation' => [
                'risk_level' => $this->position_size_recommendation['risk_level'] ?? 'MODERATE',
                'size_percent' => round($this->position_size_recommendation['size_percent'] ?? 10, 1),
                'rationale' => $this->position_size_recommendation['rationale'] ?? '',
            ],
            
            // Price targets
            'price_targets' => [
                'near_term' => round($this->price_targets['near_term'] ?? 0, 4),
                'medium_term' => round($this->price_targets['medium_term'] ?? 0, 4),
                'long_term' => round($this->price_targets['long_term'] ?? 0, 4),
                'stop_loss' => round($this->price_targets['stop_loss'] ?? 0, 4),
            ],
            
            // Key drivers and evidence
            'top_drivers' => $this->when(isset($this->top_drivers), function () {
                return collect($this->top_drivers)->map(function ($driver) {
                    return [
                        'factor' => $driver['factor'] ?? '',
                        'impact' => $driver['impact'] ?? '',
                        'weight' => round($driver['weight'] ?? 0, 3),
                    ];
                })->toArray();
            }),
            
            'evidence_sentences' => $this->evidence_sentences ?? [],
            
            // Explanations and risk notes
            'explainability_text' => $this->explainability_text,
            'risk_notes' => $this->risk_notes,
            
            // Technical levels
            'key_levels' => [
                'resistance' => $this->key_levels['resistance'] ?? [],
                'support' => $this->key_levels['support'] ?? [],
            ],
            
            // Catalysts
            'catalysts' => $this->when(isset($this->catalysts), function () {
                return collect($this->catalysts)->map(function ($catalyst) {
                    return [
                        'type' => $catalyst['type'] ?? '',
                        'description' => $catalyst['description'] ?? '',
                        'timeline' => $catalyst['timeline'] ?? '',
                        'probability' => $catalyst['probability'] ?? 'MEDIUM',
                    ];
                })->toArray();
            }),
            
            // Summaries
            'technical_summary' => $this->technical_summary,
            'fundamental_summary' => $this->fundamental_summary,
            'sentiment_summary' => $this->sentiment_summary,
            
            // Metadata
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Fusion data (optional, for detailed views)
            'fusion_data' => $this->when($request->has('include_fusion'), function () {
                return $this->fusion_data;
            }),
            
            // LLM metadata (optional, for debugging)
            'llm_metadata' => $this->when($request->has('debug'), function () {
                return $this->llm_metadata;
            }),
        ];
    }
}