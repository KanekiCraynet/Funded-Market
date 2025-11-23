<?php

namespace App\Jobs;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\History\Models\Analysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CleanupOldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('cleanup');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CleanupOldDataJob started');

        $stats = [
            'audit_logs_deleted' => 0,
            'old_analyses_deleted' => 0,
        ];

        try {
            // Clean audit logs older than 90 days (keep only recent logs)
            $auditLogsDeleted = AuditLog::where('created_at', '<', now()->subDays(90))
                ->delete();
            
            $stats['audit_logs_deleted'] = $auditLogsDeleted;
            
            Log::info("Deleted old audit logs", ['count' => $auditLogsDeleted]);

            // Clean analyses older than 180 days (optional - depends on business needs)
            // Uncomment if you want to auto-delete old analyses
            /*
            $oldAnalysesDeleted = Analysis::where('created_at', '<', now()->subDays(180))
                ->delete();
            
            $stats['old_analyses_deleted'] = $oldAnalysesDeleted;
            
            Log::info("Deleted old analyses", ['count' => $oldAnalysesDeleted]);
            */

            // Optimize tables (optional)
            DB::statement('OPTIMIZE TABLE audit_logs');
            DB::statement('OPTIMIZE TABLE analyses');
            
            Log::info('Database tables optimized');

        } catch (\Exception $e) {
            Log::error('CleanupOldDataJob encountered an error', [
                'error' => $e->getMessage(),
                'stats' => $stats,
            ]);
            
            throw $e;
        }

        Log::info('CleanupOldDataJob completed', $stats);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupOldDataJob failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
