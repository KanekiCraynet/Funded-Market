<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Collection;

class AuditService
{
    /**
     * Log an LLM request.
     */
    public function logLLMRequest(
        int $userId,
        string $symbol,
        array $prompt,
        array $response,
        float $duration,
        float $cost = 0.0
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'event_type' => 'llm_request',
            'context' => [
                'symbol' => $symbol,
                'prompt_length' => strlen(json_encode($prompt)),
                'response_length' => strlen(json_encode($response)),
                'duration_seconds' => round($duration, 3),
                'cost_usd' => round($cost, 4),
                'model' => config('services.gemini.model', 'gemini-pro'),
                'timestamp' => now()->toIso8601String(),
            ],
            'severity' => 'info',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a rate limit violation.
     */
    public function logRateLimitViolation(
        int $userId,
        string $endpoint,
        int $retryAfter
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'event_type' => 'rate_limit',
            'context' => [
                'endpoint' => $endpoint,
                'retry_after' => $retryAfter,
                'attempted_at' => now()->toIso8601String(),
                'ip_address' => Request::ip(),
            ],
            'severity' => 'warning',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log an error.
     */
    public function logError(
        string $context,
        \Throwable $exception,
        string $severity = 'error',
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'event_type' => 'error',
            'context' => [
                'context' => $context,
                'exception_message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $this->getCleanTrace($exception),
                'timestamp' => now()->toIso8601String(),
            ],
            'severity' => $severity,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a user action.
     */
    public function logUserAction(
        int $userId,
        string $action,
        array $metadata = []
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'event_type' => 'user_action',
            'context' => [
                'action' => $action,
                'metadata' => $metadata,
                'timestamp' => now()->toIso8601String(),
            ],
            'severity' => 'info',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Get audit trail for a user.
     */
    public function getAuditTrail(
        ?int $userId = null,
        ?string $eventType = null,
        ?string $severity = null,
        int $days = 30,
        int $limit = 100
    ): Collection {
        $query = AuditLog::query()
            ->with('user')
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($eventType) {
            $query->ofEventType($eventType);
        }

        if ($severity) {
            $query->ofSeverity($severity);
        }

        return $query->get();
    }

    /**
     * Get error statistics.
     */
    public function getErrorStats(int $days = 7): array
    {
        $errors = AuditLog::ofEventType('error')
            ->recent($days)
            ->get();

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('severity', 'critical')->count(),
            'error_rate' => $this->calculateErrorRate($errors, $days),
            'top_error_contexts' => $this->getTopErrorContexts($errors),
        ];
    }

    /**
     * Get LLM usage statistics.
     */
    public function getLLMStats(int $days = 7): array
    {
        $llmLogs = AuditLog::ofEventType('llm_request')
            ->recent($days)
            ->get();

        $totalCost = 0;
        $totalDuration = 0;

        foreach ($llmLogs as $log) {
            $totalCost += $log->context['cost_usd'] ?? 0;
            $totalDuration += $log->context['duration_seconds'] ?? 0;
        }

        return [
            'total_requests' => $llmLogs->count(),
            'total_cost_usd' => round($totalCost, 2),
            'average_duration' => $llmLogs->count() > 0 ? round($totalDuration / $llmLogs->count(), 2) : 0,
            'requests_per_day' => round($llmLogs->count() / $days, 1),
        ];
    }

    /**
     * Get rate limit statistics.
     */
    public function getRateLimitStats(int $days = 7): array
    {
        $rateLimitLogs = AuditLog::ofEventType('rate_limit')
            ->recent($days)
            ->get();

        return [
            'total_violations' => $rateLimitLogs->count(),
            'unique_users' => $rateLimitLogs->pluck('user_id')->unique()->count(),
            'violations_per_day' => round($rateLimitLogs->count() / $days, 1),
            'top_violators' => $this->getTopViolators($rateLimitLogs),
        ];
    }

    /**
     * Clean exception trace for storage.
     */
    private function getCleanTrace(\Throwable $exception): string
    {
        $trace = $exception->getTraceAsString();
        
        // Limit trace to 2000 characters to avoid huge JSON
        if (strlen($trace) > 2000) {
            $trace = substr($trace, 0, 2000) . '... (truncated)';
        }
        
        return $trace;
    }

    /**
     * Calculate error rate.
     */
    private function calculateErrorRate(Collection $errors, int $days): float
    {
        $totalRequests = AuditLog::recent($days)->count();
        
        if ($totalRequests === 0) {
            return 0.0;
        }
        
        return round(($errors->count() / $totalRequests) * 100, 2);
    }

    /**
     * Get top error contexts.
     */
    private function getTopErrorContexts(Collection $errors): array
    {
        return $errors->pluck('context.context')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    /**
     * Get top rate limit violators.
     */
    private function getTopViolators(Collection $rateLimitLogs): array
    {
        return $rateLimitLogs->groupBy('user_id')
            ->map(fn($logs) => $logs->count())
            ->sortDesc()
            ->take(5)
            ->toArray();
    }
}
