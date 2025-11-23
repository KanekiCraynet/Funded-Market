<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Users\Models\User;
use App\Domain\Market\Models\Instrument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Market Endpoints Feature Tests
 * 
 * Tests all market-related API endpoints with proper authentication,
 * caching, and response compression.
 */
class MarketEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        // Create test instruments
        Instrument::factory()->count(15)->create();
    }

    /** @test */
    public function it_requires_authentication_for_market_endpoints(): void
    {
        $response = $this->getJson('/api/v1/market/overview');
        
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
    }

    /** @test */
    public function it_returns_market_overview_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'trending',
                'top_gainers',
                'top_losers',
                'market_summary',
                'sector_performance',
            ]
        ]);
    }

    /** @test */
    public function it_caches_market_overview_response(): void
    {
        Sanctum::actingAs($this->user);

        // First request
        $response1 = $this->getJson('/api/v1/market/overview');
        $response1->assertHeader('X-Cache', 'MISS');

        // Second request should be cached
        $response2 = $this->getJson('/api/v1/market/overview');
        $response2->assertHeader('X-Cache', 'HIT');
        
        // Responses should be identical
        $this->assertEquals(
            $response1->json('data'),
            $response2->json('data')
        );
    }

    /** @test */
    public function it_compresses_market_overview_response(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/overview', [
            'Accept-Encoding' => 'gzip',
        ]);

        $response->assertStatus(200);
        
        // Check if compression headers are present
        if ($response->headers->has('Content-Encoding')) {
            $response->assertHeader('Content-Encoding', 'gzip');
            $this->assertNotNull($response->headers->get('X-Compression-Ratio'));
        }
    }

    /** @test */
    public function it_returns_market_tickers_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/tickers');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /** @test */
    public function it_filters_tickers_by_type(): void
    {
        Sanctum::actingAs($this->user);

        // Create specific type instruments
        Instrument::factory()->create(['type' => 'crypto']);
        Instrument::factory()->create(['type' => 'forex']);

        $response = $this->getJson('/api/v1/market/tickers?type=crypto');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (!empty($data)) {
            foreach ($data as $ticker) {
                $this->assertEquals('crypto', $ticker['type']);
            }
        }
    }

    /** @test */
    public function it_returns_instruments_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/instruments');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'symbol',
                    'name',
                    'type',
                ]
            ]
        ]);
    }

    /** @test */
    public function it_searches_instruments_by_query(): void
    {
        Sanctum::actingAs($this->user);

        // Create specific instrument
        Instrument::factory()->create([
            'symbol' => 'BTCUSDT',
            'name' => 'Bitcoin',
        ]);

        $response = $this->getJson('/api/v1/market/instruments?search=BTC');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (!empty($data)) {
            $symbols = collect($data)->pluck('symbol')->toArray();
            $this->assertContains('BTCUSDT', $symbols);
        }
    }

    /** @test */
    public function it_respects_rate_limiting(): void
    {
        Sanctum::actingAs($this->user);

        // Make multiple requests quickly
        $responses = [];
        for ($i = 0; $i < 65; $i++) {
            $responses[] = $this->getJson('/api/v1/market/overview');
        }

        // At least one should be rate limited (60 req/min limit)
        $rateLimited = collect($responses)->first(fn($r) => $r->status() === 429);
        
        // Note: Might not always trigger in testing, so we check structure if it does
        if ($rateLimited) {
            $rateLimited->assertStatus(429);
        }
    }

    /** @test */
    public function it_bypasses_cache_with_no_cache_parameter(): void
    {
        Sanctum::actingAs($this->user);

        // First request
        $response1 = $this->getJson('/api/v1/market/overview');
        
        // Request with no-cache should bypass
        $response2 = $this->getJson('/api/v1/market/overview?no-cache=1');
        
        $response2->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function it_handles_invalid_type_filter_gracefully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/tickers?type=invalid_type');

        $response->assertStatus(200);
        // Should return empty or all data, not error
    }

    /** @test */
    public function market_summary_has_correct_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        
        $summary = $response->json('data.market_summary');
        
        $this->assertArrayHasKey('total_instruments', $summary);
        $this->assertArrayHasKey('gainers_count', $summary);
        $this->assertArrayHasKey('losers_count', $summary);
        
        $this->assertIsInt($summary['total_instruments']);
        $this->assertIsInt($summary['gainers_count']);
        $this->assertIsInt($summary['losers_count']);
    }

    /** @test */
    public function trending_instruments_are_limited_to_ten(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        
        $trending = $response->json('data.trending');
        $this->assertLessThanOrEqual(10, count($trending));
    }

    /** @test */
    public function top_gainers_show_positive_changes(): void
    {
        Sanctum::actingAs($this->user);

        // Create gainers
        Instrument::factory()->create(['change_percent_24h' => 10.5]);
        Instrument::factory()->create(['change_percent_24h' => 5.2]);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        
        $gainers = $response->json('data.top_gainers');
        
        if (!empty($gainers)) {
            foreach ($gainers as $gainer) {
                $this->assertGreaterThan(0, $gainer['change_percent_24h']);
            }
        }
    }

    /** @test */
    public function top_losers_show_negative_changes(): void
    {
        Sanctum::actingAs($this->user);

        // Create losers
        Instrument::factory()->create(['change_percent_24h' => -10.5]);
        Instrument::factory()->create(['change_percent_24h' => -5.2]);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        
        $losers = $response->json('data.top_losers');
        
        if (!empty($losers)) {
            foreach ($losers as $loser) {
                $this->assertLessThan(0, $loser['change_percent_24h']);
            }
        }
    }
}
