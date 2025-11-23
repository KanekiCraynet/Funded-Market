<?php

namespace App\Domain\LLM\Services;

use App\Domain\Fusion\Services\FusionEngine;
use App\Domain\Market\Models\Instrument;
use App\Domain\History\Models\Analysis;
use App\Domain\Audit\Services\AuditService;
use App\Services\ApiKeyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LLMOrchestrator
{
    private FusionEngine $fusionEngine;
    private AuditService $auditService;
    private ApiKeyService $apiKeyService;
    private string $apiUrl;
    private int $maxRetries = 2;
    private float $baseTemperature = 0.1;

    public function __construct(
        FusionEngine $fusionEngine,
        AuditService $auditService,
        ApiKeyService $apiKeyService
    ) {
        $this->fusionEngine = $fusionEngine;
        $this->auditService = $auditService;
        $this->apiKeyService = $apiKeyService;
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    }
    
    /**
     * Get Gemini API key
     */
    private function getApiKey(): ?string
    {
        return $this->apiKeyService->get('gemini');
    }

    public function generateAnalysis(string $symbol, int $userId): Analysis
    {
        $startTime = microtime(true);
        $fusionData = null;
        
        try {
            // Get fusion analysis data
            $fusionData = $this->fusionEngine->generateFusionAnalysis($symbol);
            
            // Construct the prompt
            $prompt = $this->constructPrompt($symbol, $fusionData);
            
            // Try to get valid response with retry logic
            $validatedResponse = $this->attemptLLMCallWithRetry($prompt, $symbol);
            
            // Store analysis
            $analysis = $this->storeAnalysis($validatedResponse, $symbol, $userId, $fusionData);
            
            $duration = microtime(true) - $startTime;
            
            // Log successful LLM request
            $this->auditService->logLLMRequest(
                $userId,
                $symbol,
                ['prompt_length' => strlen($prompt)],
                $validatedResponse,
                $duration,
                $this->calculateCost($validatedResponse)
            );
            
            return $analysis;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            Log::error("LLM analysis failed for {$symbol}: " . $e->getMessage());
            
            // Log failed LLM request
            $this->auditService->logError(
                'llm_analysis_failed',
                $e,
                'error',
                $userId
            );
            
            // Generate fallback analysis
            return $this->generateFallbackAnalysis($symbol, $userId, $fusionData ?? []);
        }
    }

    /**
     * Attempt LLM call with retry logic.
     */
    private function attemptLLMCallWithRetry(string $prompt, string $symbol): array
    {
        $temperature = $this->baseTemperature;
        $lastException = null;
        
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::info("LLM attempt {$attempt} for {$symbol} with temperature {$temperature}");
                
                // Call Gemini API with current temperature
                $response = $this->callGeminiAPI($prompt, $temperature);
                
                // Validate and parse response
                $validatedResponse = $this->validateAndParseResponse($response, $symbol);
                
                // If validation passed, return success
                Log::info("LLM analysis successful for {$symbol} on attempt {$attempt}");
                return $validatedResponse;
                
            } catch (\Exception $e) {
                $lastException = $e;
                
                Log::warning("LLM attempt {$attempt} failed for {$symbol}: " . $e->getMessage());
                
                // Increase temperature for next retry (more creative)
                $temperature += 0.2;
                
                // Don't retry if this was the last attempt
                if ($attempt >= $this->maxRetries) {
                    break;
                }
                
                // Wait a bit before retrying
                sleep(1);
            }
        }
        
        // All retries exhausted
        throw new \Exception(
            "LLM analysis failed after {$this->maxRetries} retries. Last error: " . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Generate a deterministic fallback analysis when LLM fails.
     */
    private function generateFallbackAnalysis(string $symbol, int $userId, array $fusionData): Analysis
    {
        Log::warning("Generating fallback analysis for {$symbol}");
        
        // Extract basic data from fusion if available
        $score = $fusionData['fusion_score'] ?? 0;
        $quantScore = $fusionData['quant_score'] ?? 0;
        $sentimentScore = $fusionData['sentiment_score'] ?? 0;
        
        // Determine recommendation based on score
        $recommendation = match(true) {
            $score > 0.3 => 'BUY',
            $score < -0.3 => 'SELL',
            default => 'HOLD'
        };
        
        // Determine risk level
        $volatility = $fusionData['quant_summary']['volatility_status'] ?? 'MEDIUM';
        $riskLevel = match($volatility) {
            'LOW', 'VERY_LOW' => 'LOW',
            'HIGH', 'VERY_HIGH', 'EXTREME' => 'HIGH',
            default => 'MEDIUM'
        };
        
        // Calculate position size based on risk
        $positionSize = match($riskLevel) {
            'LOW' => 15.0,
            'MEDIUM' => 10.0,
            'HIGH' => 5.0,
            default => 8.0
        };
        
        $currentPrice = $this->getCurrentPrice($symbol);
        
        $fallbackData = [
            'final_score' => round($score, 3),
            'recommendation' => $recommendation,
            'confidence' => 0.5, // Low confidence for fallback
            'time_horizon' => 'medium_term',
            'risk_level' => $riskLevel,
            'position_size_recommendation' => [
                'risk_level' => 'CONSERVATIVE',
                'size_percent' => $positionSize,
                'rationale' => 'Conservative sizing due to fallback analysis mode'
            ],
            'price_targets' => [
                'near_term' => $currentPrice * ($recommendation === 'BUY' ? 1.05 : 0.95),
                'medium_term' => $currentPrice * ($recommendation === 'BUY' ? 1.10 : 0.90),
                'long_term' => $currentPrice * ($recommendation === 'BUY' ? 1.20 : 0.85),
                'stop_loss' => $currentPrice * ($recommendation === 'BUY' ? 0.95 : 1.05)
            ],
            'top_drivers' => [
                ['factor' => 'Quantitative Score', 'impact' => 'Moderate', 'weight' => abs($quantScore)],
                ['factor' => 'Sentiment Score', 'impact' => 'Moderate', 'weight' => abs($sentimentScore)]
            ],
            'evidence_sentences' => [
                'Analysis generated using deterministic fallback algorithm',
                'LLM analysis temporarily unavailable'
            ],
            'explainability_text' => 'This analysis was generated using a deterministic fallback algorithm due to LLM unavailability. It is based on quantitative indicators and sentiment data but lacks the nuanced interpretation of the AI model. Use with increased caution.',
            'risk_notes' => 'IMPORTANT: Fallback analysis has lower confidence. Consider waiting for full AI analysis or consult additional sources.',
            'key_levels' => [
                'resistance' => [$currentPrice * 1.05, $currentPrice * 1.10],
                'support' => [$currentPrice * 0.95, $currentPrice * 0.90]
            ],
            'catalysts' => [
                ['type' => 'Technical', 'description' => 'Key level breakout', 'timeline' => 'Short-term', 'probability' => 'MEDIUM']
            ],
            'technical_summary' => sprintf('Technical indicators show %s bias with score of %.2f', 
                $recommendation === 'BUY' ? 'bullish' : ($recommendation === 'SELL' ? 'bearish' : 'neutral'), 
                $score
            ),
            'fundamental_summary' => 'Fundamental analysis unavailable in fallback mode',
            'sentiment_summary' => sprintf('Sentiment analysis shows %s with score of %.2f', 
                $sentimentScore > 0 ? 'positive sentiment' : ($sentimentScore < 0 ? 'negative sentiment' : 'neutral sentiment'),
                $sentimentScore
            )
        ];
        
        return $this->storeAnalysis($fallbackData, $symbol, $userId, $fusionData);
    }

    private function constructPrompt(string $symbol, array $fusionData): string
    {
        $currentPrice = $this->getCurrentPrice($symbol);
        $timestamp = now()->toISOString();

        return <<<PROMPT
You are a deterministic financial analysis AI. Analyze the provided market data and generate a comprehensive investment recommendation.

SYMBOL: {$symbol}
CURRENT PRICE: {$currentPrice}
ANALYSIS TIMESTAMP: {$timestamp}

QUANTITATIVE ANALYSIS:
- Trend Status: {$fusionData['quant_summary']['trend_status']}
- Trend Strength: {$fusionData['quant_summary']['trend_strength']}
- Momentum Status: {$fusionData['quant_summary']['momentum_status']}
- Volatility Status: {$fusionData['quant_summary']['volatility_status']}
- Volume Status: {$fusionData['quant_summary']['volume_status']}
- Key Resistance: {$fusionData['key_levels']['resistance']['immediate']}
- Key Support: {$fusionData['key_levels']['support']['immediate']}

SENTIMENT ANALYSIS:
- Overall Sentiment: {$fusionData['sentiment_summary']['overall_sentiment']}
- Sentiment Trend: {$fusionData['sentiment_summary']['sentiment_trend']}
- News Coverage: {$fusionData['sentiment_summary']['news_coverage']}
- Social Engagement: {$fusionData['sentiment_summary']['social_engagement']}
- Analyst Consensus: {$fusionData['sentiment_summary']['analyst_consensus']}

FUSION ANALYSIS:
- Fusion Score: {$fusionData['fusion_score']}
- Recommendation: {$fusionData['recommendation']['action']}
- Confidence: {$fusionData['confidence']}
- Risk Level: {$fusionData['risk_assessment']['risk_level']}

MARKET CONDITIONS:
- Volatility Regime: {$fusionData['market_conditions']['regime']}
- Trend Phase: {$fusionData['market_conditions']['trend_phase']}
- Market Efficiency: {$fusionData['market_conditions']['market_efficiency']}

TOP DRIVERS:
{$this->formatTopDrivers($fusionData['top_drivers'])}

KEY CATALYSTS:
{$this->formatCatalysts($fusionData['catalysts'])}

RISK FACTORS:
{$this->formatRiskFactors($fusionData['risk_assessment']['risk_factors'])}

Based on the comprehensive analysis above, generate a structured JSON response with the following schema:

{
  "final_score": float (range: -1.0 to 1.0),
  "recommendation": "BUY" | "SELL" | "HOLD",
  "confidence": float (range: 0.0 to 1.0),
  "time_horizon": "short_term" | "medium_term" | "long_term",
  "risk_level": "LOW" | "MEDIUM" | "HIGH",
  "position_size_recommendation": {
    "risk_level": "CONSERVATIVE" | "MODERATE" | "AGGRESSIVE",
    "size_percent": float (range: 1.0 to 25.0),
    "rationale": "string"
  },
  "price_targets": {
    "near_term": float,
    "medium_term": float,
    "long_term": float,
    "stop_loss": float
  },
  "top_drivers": [
    {
      "factor": "string",
      "impact": "string",
      "weight": float
    }
  ],
  "evidence_sentences": [
    "string"
  ],
  "explainability_text": "string",
  "risk_notes": "string",
  "key_levels": {
    "resistance": [float],
    "support": [float]
  },
  "catalysts": [
    {
      "type": "string",
      "description": "string",
      "timeline": "string",
      "probability": "HIGH" | "MEDIUM" | "LOW"
    }
  ],
  "technical_summary": "string",
  "fundamental_summary": "string",
  "sentiment_summary": "string"
}

CRITICAL REQUIREMENTS:
1. Output MUST be valid JSON only - no additional text or explanations
2. All numeric values must be within specified ranges
3. Recommendation must align with the fusion score direction
4. Confidence should reflect data quality and signal consistency
5. Risk level must match the volatility regime and risk factors
6. Position size should be inversely proportional to risk level
7. Price targets must be realistic and technically justified
8. Evidence sentences must be supported by the provided data
9. All text fields must be concise and professional

Generate the analysis now:
PROMPT;
    }

    private function formatTopDrivers(array $drivers): string
    {
        $formatted = [];
        foreach ($drivers as $driver) {
            $formatted[] = "- {$driver['name']}: {$driver['value']} ({$driver['category']})";
        }
        return implode("\n", $formatted);
    }

    private function formatCatalysts(array $catalysts): string
    {
        $formatted = [];
        foreach ($catalysts as $catalyst) {
            $formatted[] = "- {$catalyst['type']}: {$catalyst['description']} (Impact: {$catalyst['impact']})";
        }
        return implode("\n", $formatted);
    }

    private function formatRiskFactors(array $factors): string
    {
        $formatted = [];
        foreach ($factors as $factor) {
            $formatted[] = "- {$factor['factor']}: Risk score {$factor['score']}";
        }
        return implode("\n", $formatted);
    }

    private function callGeminiAPI(string $prompt, float $temperature = 0.1): array
    {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            throw new \Exception('Gemini API key not configured');
        }
        
        $response = Http::timeout(60)->post($this->apiUrl . '?key=' . $apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,  // Adjustable temperature for retries
                'topP' => 0.8,
                'topK' => 40,
                'maxOutputTokens' => 2048,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("Gemini API request failed: " . $response->status() . " - " . $response->body());
        }

        $data = $response->json();
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception("Invalid response structure from Gemini API");
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Extract JSON from response
        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception("No JSON found in Gemini response");
        }

        $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in Gemini response: " . json_last_error_msg());
        }

        return $decoded;
    }

    private function validateAndParseResponse(array $response, string $symbol): array
    {
        $schema = [
            'final_score' => 'required|numeric|between:-1,1',
            'recommendation' => 'required|in:BUY,SELL,HOLD',
            'confidence' => 'required|numeric|between:0,1',
            'time_horizon' => 'required|in:short_term,medium_term,long_term',
            'risk_level' => 'required|in:LOW,MEDIUM,HIGH',
            'position_size_recommendation' => 'required|array',
            'position_size_recommendation.risk_level' => 'required|in:CONSERVATIVE,MODERATE,AGGRESSIVE',
            'position_size_recommendation.size_percent' => 'required|numeric|between:1,25',
            'position_size_recommendation.rationale' => 'required|string',
            'price_targets' => 'required|array',
            'price_targets.near_term' => 'required|numeric|min:0',
            'price_targets.medium_term' => 'required|numeric|min:0',
            'price_targets.long_term' => 'required|numeric|min:0',
            'price_targets.stop_loss' => 'required|numeric|min:0',
            'top_drivers' => 'required|array|max:5',
            'evidence_sentences' => 'required|array|max:10',
            'explainability_text' => 'required|string|max:1000',
            'risk_notes' => 'required|string|max:500',
            'key_levels' => 'required|array',
            'key_levels.resistance' => 'required|array|max:3',
            'key_levels.support' => 'required|array|max:3',
            'catalysts' => 'required|array|max:5',
            'technical_summary' => 'required|string|max:500',
            'fundamental_summary' => 'required|string|max:500',
            'sentiment_summary' => 'required|string|max:500',
        ];

        $validator = Validator::make($response, $schema);

        if ($validator->fails()) {
            Log::warning("LLM response validation failed for {$symbol}: " . $validator->errors()->toJson());
            
            // Apply corrections and retry once
            return $this->applyCorrectionsAndRetry($response, $symbol);
        }

        // Additional business logic validation
        $this->validateBusinessLogic($response, $symbol);

        return $response;
    }

    private function validateBusinessLogic(array $response, string $symbol): void
    {
        // Check recommendation alignment with score
        $score = $response['final_score'];
        $recommendation = $response['recommendation'];
        
        if ($score > 0.3 && $recommendation === 'SELL') {
            throw new \Exception("Recommendation misalignment: positive score but SELL recommendation");
        }
        
        if ($score < -0.3 && $recommendation === 'BUY') {
            throw new \Exception("Recommendation misalignment: negative score but BUY recommendation");
        }

        // Check price target logic
        $currentPrice = $this->getCurrentPrice($symbol);
        $nearTerm = $response['price_targets']['near_term'];
        $stopLoss = $response['price_targets']['stop_loss'];
        
        if ($recommendation === 'BUY' && $nearTerm <= $currentPrice) {
            throw new \Exception("Price target logic error: BUY recommendation but target <= current price");
        }
        
        if ($recommendation === 'SELL' && $nearTerm >= $currentPrice) {
            throw new \Exception("Price target logic error: SELL recommendation but target >= current price");
        }

        // Check position size vs risk alignment
        $riskLevel = $response['risk_level'];
        $positionSize = $response['position_size_recommendation']['size_percent'];
        
        if ($riskLevel === 'HIGH' && $positionSize > 15) {
            throw new \Exception("Risk management error: HIGH risk but large position size");
        }
        
        if ($riskLevel === 'LOW' && $positionSize < 5) {
            Log::warning("Unusual: LOW risk but very small position size for {$symbol}");
        }
    }

    private function applyCorrectionsAndRetry(array $response, string $symbol): array
    {
        // Apply automatic corrections
        $corrected = $response;

        // Clamp final_score to valid range
        $corrected['final_score'] = max(-1, min(1, $response['final_score'] ?? 0));

        // Set default recommendation based on score
        if (!in_array($response['recommendation'] ?? '', ['BUY', 'SELL', 'HOLD'])) {
            $score = $corrected['final_score'];
            if ($score > 0.2) {
                $corrected['recommendation'] = 'BUY';
            } elseif ($score < -0.2) {
                $corrected['recommendation'] = 'SELL';
            } else {
                $corrected['recommendation'] = 'HOLD';
            }
        }

        // Set default confidence
        if (!isset($response['confidence']) || $response['confidence'] < 0 || $response['confidence'] > 1) {
            $corrected['confidence'] = 0.6;
        }

        // Set default risk level
        if (!in_array($response['risk_level'] ?? '', ['LOW', 'MEDIUM', 'HIGH'])) {
            $corrected['risk_level'] = 'MEDIUM';
        }

        // Ensure position size is reasonable
        $positionSize = $response['position_size_recommendation']['size_percent'] ?? 10;
        $corrected['position_size_recommendation']['size_percent'] = max(1, min(25, $positionSize));

        // Set default time horizon
        if (!in_array($response['time_horizon'] ?? '', ['short_term', 'medium_term', 'long_term'])) {
            $corrected['time_horizon'] = 'medium_term';
        }

        // Validate the corrected response
        $validator = Validator::make($corrected, [
            'final_score' => 'required|numeric|between:-1,1',
            'recommendation' => 'required|in:BUY,SELL,HOLD',
            'confidence' => 'required|numeric|between:0,1',
            'time_horizon' => 'required|in:short_term,medium_term,long_term',
            'risk_level' => 'required|in:LOW,MEDIUM,HIGH',
        ]);

        if ($validator->fails()) {
            throw new \Exception("Unable to correct LLM response: " . $validator->errors()->toJson());
        }

        Log::info("Applied corrections to LLM response for {$symbol}");
        return $corrected;
    }

    private function getCurrentPrice(string $symbol): float
    {
        $instrument = Instrument::where('symbol', $symbol)->first();
        return $instrument ? $instrument->price : 0.0;
    }

    private function storeAnalysis(array $llmResponse, string $symbol, int $userId, array $fusionData): Analysis
    {
        $instrument = Instrument::where('symbol', $symbol)->first();
        if (!$instrument) {
            throw new \Exception("Instrument not found: {$symbol}");
        }

        return Analysis::create([
            'user_id' => $userId,
            'instrument_id' => $instrument->id,
            'final_score' => $llmResponse['final_score'],
            'recommendation' => $llmResponse['recommendation'],
            'confidence' => $llmResponse['confidence'],
            'time_horizon' => $llmResponse['time_horizon'],
            'risk_level' => $llmResponse['risk_level'],
            'position_size_recommendation' => $llmResponse['position_size_recommendation'],
            'price_targets' => $llmResponse['price_targets'],
            'top_drivers' => $llmResponse['top_drivers'],
            'evidence_sentences' => $llmResponse['evidence_sentences'],
            'explainability_text' => $llmResponse['explainability_text'],
            'risk_notes' => $llmResponse['risk_notes'],
            'key_levels' => $llmResponse['key_levels'],
            'catalysts' => $llmResponse['catalysts'],
            'technical_summary' => $llmResponse['technical_summary'],
            'fundamental_summary' => $llmResponse['fundamental_summary'],
            'sentiment_summary' => $llmResponse['sentiment_summary'],
            'fusion_data' => $fusionData,
            'llm_metadata' => [
                'model' => 'gemini-pro',
                'temperature' => 0.1,
                'max_tokens' => 2048,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function generateBatchAnalysis(array $symbols, int $userId): array
    {
        $results = [];
        $errors = [];

        foreach ($symbols as $symbol) {
            try {
                $results[$symbol] = $this->generateAnalysis($symbol, $userId);
            } catch (\Exception $e) {
                $errors[$symbol] = $e->getMessage();
                Log::error("Batch analysis failed for {$symbol}: " . $e->getMessage());
            }
        }

        return [
            'successful' => $results,
            'failed' => $errors,
            'summary' => [
                'total' => count($symbols),
                'successful' => count($results),
                'failed' => count($errors),
            ],
        ];
    }

    public function validateResponseQuality(array $response): array
    {
        $qualityScore = 0;
        $issues = [];

        // Check response completeness
        $requiredFields = [
            'final_score', 'recommendation', 'confidence', 'time_horizon',
            'risk_level', 'position_size_recommendation', 'price_targets',
            'top_drivers', 'evidence_sentences', 'explainability_text'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($response[$field])) {
                $issues[] = "Missing required field: {$field}";
            } else {
                $qualityScore += 10;
            }
        }

        // Check logical consistency
        if (isset($response['final_score']) && isset($response['recommendation'])) {
            $score = $response['final_score'];
            $rec = $response['recommendation'];
            
            if (($score > 0.3 && $rec === 'SELL') || ($score < -0.3 && $rec === 'BUY')) {
                $issues[] = "Score and recommendation misalignment";
                $qualityScore -= 20;
            }
        }

        // Check confidence vs evidence
        if (isset($response['confidence']) && isset($response['evidence_sentences'])) {
            if ($response['confidence'] > 0.8 && count($response['evidence_sentences']) < 3) {
                $issues[] = "High confidence with insufficient evidence";
                $qualityScore -= 15;
            }
        }

        // Check risk management
        if (isset($response['risk_level'], $response['position_size_recommendation']['size_percent'])) {
            $risk = $response['risk_level'];
            $size = $response['position_size_recommendation']['size_percent'];
            
            if ($risk === 'HIGH' && $size > 15) {
                $issues[] = "High risk with excessive position size";
                $qualityScore -= 25;
            }
        }

        return [
            'quality_score' => max(0, min(100, $qualityScore)),
            'quality_level' => match(true) {
                $qualityScore >= 80 => 'EXCELLENT',
                $qualityScore >= 60 => 'GOOD',
                $qualityScore >= 40 => 'FAIR',
                default => 'POOR',
            },
            'issues' => $issues,
            'recommendations' => $this->getQualityImprovementRecommendations($issues),
        ];
    }

    private function getQualityImprovementRecommendations(array $issues): array
    {
        $recommendations = [];
        
        foreach ($issues as $issue) {
            if (str_contains($issue, 'Missing')) {
                $recommendations[] = "Ensure all required fields are populated";
            } elseif (str_contains($issue, 'misalignment')) {
                $recommendations[] = "Align recommendation with quantitative score";
            } elseif (str_contains($issue, 'evidence')) {
                $recommendations[] = "Provide more supporting evidence for high confidence";
            } elseif (str_contains($issue, 'position size')) {
                $recommendations[] = "Adjust position size based on risk level";
            }
        }
        
        return array_unique($recommendations);
    }

    /**
     * Calculate estimated cost of LLM call.
     */
    private function calculateCost(array $response): float
    {
        // Rough estimation: $0.001 per 1K tokens
        // Average response is ~1500 tokens
        $estimatedTokens = strlen(json_encode($response)) / 4; // Rough token count
        return round(($estimatedTokens / 1000) * 0.001, 4);
    }
}