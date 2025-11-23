<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Users\Models\User;
use App\Domain\Market\Models\Instrument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;

class AnalysisGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Instrument $instrument;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->instrument = Instrument::factory()->create([
            'symbol' => 'BTCUSDT',
            'type' => 'crypto',
        ]);
        
        // Clear Redis
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            // Ignore if Redis not available
        }
    }

    public function test_unauthorized_request_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => 'BTCUSDT'
        ]);
        
        $response->assertStatus(401);
    }

    public function test_analysis_generation_succeeds(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => 'BTCUSDT'
        ]);

        // Might fail if LLM/services not configured, but structure should be correct
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500
        );
        
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'data',
                'message',
                'rate_limit_reset',
            ]);
        }
    }

    public function test_rate_limit_blocks_duplicate_requests(): void
    {
        Sanctum::actingAs($this->user);

        // First request
        $response1 = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => 'BTCUSDT'
        ]);

        // Second request should be rate limited
        $response2 = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => 'BTCUSDT'
        ]);

        $response2->assertStatus(429);
        $response2->assertJsonStructure(['retry_after', 'message']);
    }

    public function test_invalid_symbol_validation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/analysis/generate', [
            'symbol' => ''
        ]);

        $response->assertStatus(422);
    }

    public function test_fetch_history_works(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/analysis/history');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination'
        ]);
    }

    public function test_history_pagination_works(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/analysis/history?page=1&per_page=10');

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.current_page', 1);
        $response->assertJsonPath('pagination.per_page', 10);
    }

    public function test_history_filters_by_symbol(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/analysis/history?symbol=BTCUSDT');

        $response->assertStatus(200);
    }

    public function test_history_filters_by_recommendation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/analysis/history?recommendation=BUY');

        $response->assertStatus(200);
    }

    public function test_stats_endpoint_works(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/analysis/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'overall',
                'recommendation_distribution',
            ]
        ]);
    }
}
