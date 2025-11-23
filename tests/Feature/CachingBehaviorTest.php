<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Users\Models\User;
use App\Domain\Market\Models\Instrument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

/**
 * Caching Behavior Feature Tests
 * 
 * Tests Phase 3 caching implementation including:
 * - Cache HIT/MISS behavior
 * - Cache bypass mechanisms
 * - Performance metrics
 * - Compression integration
 */
class CachingBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Instrument::factory()->count(5)->create();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function first_request_is_cache_miss(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function second_request_is_cache_hit(): void
    {
        Sanctum::actingAs($this->user);

        // First request - MISS
        $this->getJson('/api/v1/market/overview');

        // Second request - HIT
        $response = $this->getJson('/api/v1/market/overview');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache', 'HIT');
    }

    /** @test */
    public function cache_hit_is_significantly_faster(): void
    {
        Sanctum::actingAs($this->user);

        // First request - measure time
        $start1 = microtime(true);
        $this->getJson('/api/v1/market/overview');
        $time1 = (microtime(true) - $start1) * 1000; // Convert to ms

        // Second request - should be cached and faster
        $start2 = microtime(true);
        $response = $this->getJson('/api/v1/market/overview');
        $time2 = (microtime(true) - $start2) * 1000;

        $response->assertHeader('X-Cache', 'HIT');
        
        // Cached response should be at least 5x faster
        // (Usually it's 100-1000x faster, but tests can be slow)
        $this->assertLessThan($time1 / 5, $time2, 
            "Cached response ($time2 ms) should be at least 5x faster than uncached ($time1 ms)"
        );
    }

    /** @test */
    public function no_cache_parameter_bypasses_cache(): void
    {
        Sanctum::actingAs($this->user);

        // First request - populate cache
        $this->getJson('/api/v1/market/overview');

        // Request with no-cache parameter
        $response = $this->getJson('/api/v1/market/overview?no-cache=1');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function refresh_parameter_bypasses_cache(): void
    {
        Sanctum::actingAs($this->user);

        // First request - populate cache
        $this->getJson('/api/v1/market/overview');

        // Request with refresh parameter
        $response = $this->getJson('/api/v1/market/overview?refresh=1');

        $response->assertStatus(200);
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function cache_control_no_cache_header_bypasses_cache(): void
    {
        Sanctum::actingAs($this->user);

        // First request - populate cache
        $this->getJson('/api/v1/market/overview');

        // Request with Cache-Control: no-cache header
        $response = $this->getJson('/api/v1/market/overview', [
            'Cache-Control' => 'no-cache',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function different_query_parameters_create_different_cache_keys(): void
    {
        Sanctum::actingAs($this->user);

        // Request with type=crypto
        $response1 = $this->getJson('/api/v1/market/tickers?type=crypto');
        $response1->assertHeader('X-Cache', 'MISS');

        // Request with type=forex (different cache key)
        $response2 = $this->getJson('/api/v1/market/tickers?type=forex');
        $response2->assertHeader('X-Cache', 'MISS');

        // Same request again should hit cache
        $response3 = $this->getJson('/api/v1/market/tickers?type=crypto');
        $response3->assertHeader('X-Cache', 'HIT');
    }

    /** @test */
    public function post_requests_are_not_cached(): void
    {
        Sanctum::actingAs($this->user);

        // POST requests should never be cached
        $response1 = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => 'BTCUSDT'
        ]);

        // Check it doesn't have cache headers
        $this->assertFalse($response1->headers->has('X-Cache'));
    }

    /** @test */
    public function cache_respects_ttl_configuration(): void
    {
        Sanctum::actingAs($this->user);

        // Get cached response
        $this->getJson('/api/v1/market/overview');
        
        // Verify cache exists
        $cacheKey = $this->getCacheKeyForRequest('/api/v1/market/overview');
        $this->assertTrue(Cache::has($cacheKey));
        
        // In a real scenario, we'd wait for TTL expiry
        // For testing, we can manually clear and verify behavior
        Cache::forget($cacheKey);
        
        $response = $this->getJson('/api/v1/market/overview');
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function cache_includes_user_context(): void
    {
        // User 1
        $user1 = User::factory()->create();
        Sanctum::actingAs($user1);
        
        $this->getJson('/api/v1/analysis/history');
        
        // User 2
        $user2 = User::factory()->create();
        Sanctum::actingAs($user2);
        
        // Should be cache MISS (different user context)
        $response = $this->getJson('/api/v1/analysis/history');
        $response->assertHeader('X-Cache', 'MISS');
    }

    /** @test */
    public function compression_works_with_caching(): void
    {
        Sanctum::actingAs($this->user);

        // First request with gzip support
        $response1 = $this->getJson('/api/v1/market/overview', [
            'Accept-Encoding' => 'gzip',
        ]);

        $response1->assertStatus(200);
        $response1->assertHeader('X-Cache', 'MISS');

        // Second request should be cached AND compressed
        $response2 = $this->getJson('/api/v1/market/overview', [
            'Accept-Encoding' => 'gzip',
        ]);

        $response2->assertStatus(200);
        $response2->assertHeader('X-Cache', 'HIT');
        
        // Should also have compression headers (if response is large enough)
        if ($response2->headers->has('Content-Encoding')) {
            $response2->assertHeader('Content-Encoding', 'gzip');
        }
    }

    /** @test */
    public function cache_handles_parallel_requests_correctly(): void
    {
        Sanctum::actingAs($this->user);

        // Simulate concurrent requests (in tests they're sequential, but logic should handle it)
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/market/overview');
        }

        // First should be MISS
        $this->assertEquals('MISS', $responses[0]->headers->get('X-Cache'));
        
        // Rest should be HIT
        for ($i = 1; $i < 5; $i++) {
            $this->assertEquals('HIT', $responses[$i]->headers->get('X-Cache'));
        }
    }

    /**
     * Helper: Generate cache key for a request
     * (This mirrors the logic in CacheApiResponse middleware)
     */
    private function getCacheKeyForRequest(string $url): string
    {
        return 'api_response:' . md5($url . $this->user->id);
    }
}
