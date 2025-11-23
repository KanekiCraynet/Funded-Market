<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditService();
        $this->user = User::factory()->create();
    }

    public function test_log_llm_request_creates_audit_log(): void
    {
        $prompt = ['test' => 'prompt'];
        $response = ['test' => 'response'];
        
        $log = $this->service->logLLMRequest(
            $this->user->id,
            'BTCUSDT',
            $prompt,
            $response,
            1.5,
            0.001
        );
        
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('llm_request', $log->event_type);
        $this->assertEquals('info', $log->severity);
        $this->assertArrayHasKey('symbol', $log->context);
        $this->assertEquals('BTCUSDT', $log->context['symbol']);
    }

    public function test_log_rate_limit_violation_creates_audit_log(): void
    {
        $log = $this->service->logRateLimitViolation(
            $this->user->id,
            '/api/v1/analysis/generate',
            60
        );
        
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('rate_limit', $log->event_type);
        $this->assertEquals('warning', $log->severity);
        $this->assertArrayHasKey('endpoint', $log->context);
        $this->assertArrayHasKey('retry_after', $log->context);
    }

    public function test_log_error_creates_audit_log(): void
    {
        $exception = new \Exception('Test error');
        
        $log = $this->service->logError(
            'test_context',
            $exception,
            'error',
            $this->user->id
        );
        
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('error', $log->event_type);
        $this->assertEquals('error', $log->severity);
        $this->assertArrayHasKey('exception_message', $log->context);
    }

    public function test_log_user_action_creates_audit_log(): void
    {
        $metadata = ['action' => 'test', 'data' => 'value'];
        
        $log = $this->service->logUserAction(
            $this->user->id,
            'test_action',
            $metadata
        );
        
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('user_action', $log->event_type);
        $this->assertEquals('info', $log->severity);
    }

    public function test_get_audit_trail_returns_logs(): void
    {
        // Create multiple logs
        $this->service->logUserAction($this->user->id, 'action1');
        $this->service->logUserAction($this->user->id, 'action2');
        $this->service->logUserAction($this->user->id, 'action3');
        
        $trail = $this->service->getAuditTrail($this->user->id);
        
        $this->assertCount(3, $trail);
    }

    public function test_get_audit_trail_filters_by_event_type(): void
    {
        $this->service->logUserAction($this->user->id, 'action1');
        $this->service->logError('context', new \Exception('test'), 'error', $this->user->id);
        
        $trail = $this->service->getAuditTrail($this->user->id, 'error');
        
        $this->assertCount(1, $trail);
        $this->assertEquals('error', $trail[0]->event_type);
    }

    public function test_get_error_stats_returns_correct_data(): void
    {
        // Create some error logs
        $this->service->logError('context1', new \Exception('error1'), 'error', $this->user->id);
        $this->service->logError('context2', new \Exception('error2'), 'critical', $this->user->id);
        $this->service->logError('context3', new \Exception('error3'), 'error', $this->user->id);
        
        $stats = $this->service->getErrorStats(7);
        
        $this->assertArrayHasKey('total_errors', $stats);
        $this->assertArrayHasKey('critical_errors', $stats);
        $this->assertArrayHasKey('error_rate', $stats);
        $this->assertEquals(3, $stats['total_errors']);
        $this->assertEquals(1, $stats['critical_errors']);
    }

    public function test_get_llm_stats_returns_correct_data(): void
    {
        // Create LLM logs
        $this->service->logLLMRequest($this->user->id, 'BTC', [], [], 1.0, 0.001);
        $this->service->logLLMRequest($this->user->id, 'ETH', [], [], 2.0, 0.002);
        
        $stats = $this->service->getLLMStats(7);
        
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('total_cost_usd', $stats);
        $this->assertArrayHasKey('average_duration', $stats);
        $this->assertEquals(2, $stats['total_requests']);
        $this->assertEquals(0.003, $stats['total_cost_usd']);
    }
}
