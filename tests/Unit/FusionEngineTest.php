<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Fusion\Services\FusionEngine;
use App\Domain\Quant\Services\QuantEngine;
use App\Domain\Sentiment\Services\SentimentEngine;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * FusionEngine Unit Tests
 * 
 * Tests the FusionEngine service with mocked dependencies
 * to ensure proper fusion algorithm and parallel execution.
 */
class FusionEngineTest extends TestCase
{
    private FusionEngine $fusionEngine;
    private $quantEngineMock;
    private $sentimentEngineMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->quantEngineMock = Mockery::mock(QuantEngine::class);
        $this->sentimentEngineMock = Mockery::mock(SentimentEngine::class);

        // Create FusionEngine with mocked dependencies
        $this->fusionEngine = new FusionEngine(
            $this->quantEngineMock,
            $this->sentimentEngineMock
        );

        // Clear cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_fusion_analysis_successfully(): void
    {
        // Arrange
        $symbol = 'BTCUSDT';
        
        $mockQuantData = $this->getMockQuantData();
        $mockSentimentData = $this->getMockSentimentData();

        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->with($symbol)
            ->andReturn($mockQuantData);

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with($symbol)
            ->andReturn($mockSentimentData);

        // Act
        $result = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fusion_score', $result);
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('alpha', $result);
        $this->assertArrayHasKey('top_drivers', $result);
        $this->assertArrayHasKey('risk_assessment', $result);
        
        // Validate score ranges
        $this->assertGreaterThanOrEqual(-1, $result['fusion_score']);
        $this->assertLessThanOrEqual(1, $result['fusion_score']);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(1, $result['confidence']);
    }

    /** @test */
    public function it_caches_fusion_analysis_results(): void
    {
        // Arrange
        $symbol = 'BTCUSDT';
        $mockQuantData = $this->getMockQuantData();
        $mockSentimentData = $this->getMockSentimentData();

        // First call should hit the engines
        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->with($symbol)
            ->andReturn($mockQuantData);

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with($symbol)
            ->andReturn($mockSentimentData);

        // Act - First call
        $result1 = $this->fusionEngine->generateFusionAnalysis($symbol);
        
        // Second call should use cache (no engine calls)
        $result2 = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert
        $this->assertEquals($result1, $result2);
        
        // Verify cache was used
        $cacheKey = "fusion_analysis:{$symbol}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_calculates_fusion_score_correctly_for_bullish_signals(): void
    {
        // Arrange - Strong bullish signals
        $symbol = 'BTCUSDT';
        
        $bullishQuantData = array_merge($this->getMockQuantData(), [
            'composite' => [
                'score' => 0.8,
                'confidence' => 0.9,
                'trend_score' => 0.85,
                'momentum_score' => 0.75,
            ]
        ]);
        
        $bullishSentimentData = array_merge($this->getMockSentimentData(), [
            'overall_score' => 0.7,
            'confidence' => 0.8,
        ]);

        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->andReturn($bullishQuantData);

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn($bullishSentimentData);

        // Act
        $result = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert
        $this->assertGreaterThan(0.5, $result['fusion_score'], 'Fusion score should be bullish');
        $this->assertEquals('BUY', $result['recommendation']);
    }

    /** @test */
    public function it_calculates_fusion_score_correctly_for_bearish_signals(): void
    {
        // Arrange - Strong bearish signals
        $symbol = 'BTCUSDT';
        
        $bearishQuantData = array_merge($this->getMockQuantData(), [
            'composite' => [
                'score' => -0.8,
                'confidence' => 0.9,
                'trend_score' => -0.85,
                'momentum_score' => -0.75,
            ]
        ]);
        
        $bearishSentimentData = array_merge($this->getMockSentimentData(), [
            'overall_score' => -0.7,
            'confidence' => 0.8,
        ]);

        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->andReturn($bearishQuantData);

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn($bearishSentimentData);

        // Act
        $result = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert
        $this->assertLessThan(-0.5, $result['fusion_score'], 'Fusion score should be bearish');
        $this->assertEquals('SELL', $result['recommendation']);
    }

    /** @test */
    public function it_handles_engine_failures_gracefully(): void
    {
        // Arrange
        $symbol = 'INVALID';
        
        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->with($symbol)
            ->andThrow(new \Exception('Failed to calculate indicators'));

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->never(); // Should not be called if quant fails

        // Act
        $result = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert - Should return empty/default fusion analysis
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fusion_score', $result);
        $this->assertEquals(0, $result['fusion_score']);
    }

    /** @test */
    public function it_adjusts_alpha_based_on_volatility_regime(): void
    {
        // This tests the dynamic alpha calculation
        // We'll test via the output - different volatility should affect fusion score
        
        $symbol = 'BTCUSDT';
        
        // High volatility - should trust sentiment more (lower alpha)
        $highVolQuantData = array_merge($this->getMockQuantData(), [
            'volatility' => [
                'volatility_regime' => 'high',
                'current' => 0.8,
            ]
        ]);
        
        $sentimentData = $this->getMockSentimentData();

        $this->quantEngineMock
            ->shouldReceive('calculateIndicators')
            ->once()
            ->andReturn($highVolQuantData);

        $this->sentimentEngineMock
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn($sentimentData);

        // Act
        $result = $this->fusionEngine->generateFusionAnalysis($symbol);

        // Assert
        $this->assertIsFloat($result['alpha']);
        $this->assertGreaterThanOrEqual(0, $result['alpha']);
        $this->assertLessThanOrEqual(1, $result['alpha']);
        
        // For high volatility, alpha should be lower (trusts sentiment more)
        $this->assertLessThanOrEqual(0.5, $result['alpha'], 'Alpha should be low for high volatility');
    }

    /**
     * Helper: Get mock quantitative data
     */
    private function getMockQuantData(): array
    {
        return [
            'composite' => [
                'score' => 0.5,
                'confidence' => 0.7,
                'trend_score' => 0.6,
                'momentum_score' => 0.4,
            ],
            'volatility' => [
                'volatility_regime' => 'medium',
                'current' => 0.3,
            ],
            'trend' => [
                'direction' => 'bullish',
                'strength' => 0.6,
            ],
            'momentum' => [
                'rsi' => 55,
                'macd' => ['signal' => 'bullish'],
            ],
        ];
    }

    /**
     * Helper: Get mock sentiment data
     */
    private function getMockSentimentData(): array
    {
        return [
            'overall_score' => 0.4,
            'confidence' => 0.6,
            'news_sentiment' => [
                'score' => 0.5,
                'count' => 10,
            ],
            'social_sentiment' => [
                'score' => 0.3,
                'count' => 100,
            ],
            'sources' => [
                'social_mentions' => 100,
                'news_count' => 10,
            ],
        ];
    }
}
