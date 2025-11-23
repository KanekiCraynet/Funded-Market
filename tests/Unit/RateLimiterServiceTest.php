<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\RateLimiter\Services\RateLimiterService;
use Illuminate\Support\Facades\Redis;

class RateLimiterServiceTest extends TestCase
{
    private RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateLimiterService();
        
        // Clear Redis before each test
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis not available');
        }
    }

    protected function tearDown(): void
    {
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            // Ignore
        }
        parent::tearDown();
    }

    public function test_first_attempt_is_allowed(): void
    {
        $result = $this->service->attempt('test_key', 60);
        
        $this->assertTrue($result->isAllowed());
        $this->assertEquals(0, $result->retryAfter);
    }

    public function test_second_attempt_is_denied(): void
    {
        $this->service->attempt('test_key', 60);
        $result = $this->service->attempt('test_key', 60);
        
        $this->assertTrue($result->isDenied());
        $this->assertGreaterThan(0, $result->retryAfter);
        $this->assertLessThanOrEqual(60, $result->retryAfter);
    }

    public function test_reset_clears_rate_limit(): void
    {
        $this->service->attempt('test_key', 60);
        $this->service->reset('test_key');
        
        $result = $this->service->attempt('test_key', 60);
        $this->assertTrue($result->isAllowed());
    }

    public function test_is_locked_returns_correct_status(): void
    {
        $this->assertFalse($this->service->isLocked('test_key'));
        
        $this->service->attempt('test_key', 60);
        $this->assertTrue($this->service->isLocked('test_key'));
    }

    public function test_get_remaining_time_returns_correct_value(): void
    {
        $this->service->attempt('test_key', 60);
        
        $remaining = $this->service->getRemainingTime('test_key');
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(60, $remaining);
    }

    public function test_increment_increments_counter(): void
    {
        $count1 = $this->service->increment('counter_key', 60);
        $count2 = $this->service->increment('counter_key', 60);
        $count3 = $this->service->increment('counter_key', 60);
        
        $this->assertEquals(1, $count1);
        $this->assertEquals(2, $count2);
        $this->assertEquals(3, $count3);
    }

    public function test_too_many_attempts_checks_correctly(): void
    {
        $this->assertFalse($this->service->tooManyAttempts('attempts_key', 3, 60));
        
        $this->service->increment('attempts_key', 60);
        $this->assertFalse($this->service->tooManyAttempts('attempts_key', 3, 60));
        
        $this->service->increment('attempts_key', 60);
        $this->assertFalse($this->service->tooManyAttempts('attempts_key', 3, 60));
        
        $this->service->increment('attempts_key', 60);
        $this->assertFalse($this->service->tooManyAttempts('attempts_key', 3, 60));
        
        $this->service->increment('attempts_key', 60);
        $this->assertTrue($this->service->tooManyAttempts('attempts_key', 3, 60));
    }

    public function test_get_info_returns_correct_information(): void
    {
        $info = $this->service->getInfo('info_key');
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('locked', $info);
        $this->assertArrayHasKey('remaining_time', $info);
        $this->assertArrayHasKey('key', $info);
        $this->assertFalse($info['locked']);
        
        $this->service->attempt('info_key', 60);
        $info = $this->service->getInfo('info_key');
        
        $this->assertTrue($info['locked']);
        $this->assertGreaterThan(0, $info['remaining_time']);
    }
}
