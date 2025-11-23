<?php

namespace App\Jobs;

use App\Domain\LLM\Services\LLMOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // LLM has its own retry logic
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $symbol,
        public int $userId
    ) {
        $this->onQueue('llm');
    }

    /**
     * Execute the job.
     */
    public function handle(LLMOrchestrator $llmOrchestrator): void
    {
        Log::info('GenerateAnalysisJob started', [
            'symbol' => $this->symbol,
            'user_id' => $this->userId,
        ]);

        try {
            $analysis = $llmOrchestrator->generateAnalysis($this->symbol, $this->userId);
            
            Log::info('Analysis generated successfully', [
                'symbol' => $this->symbol,
                'user_id' => $this->userId,
                'analysis_id' => $analysis->id,
                'recommendation' => $analysis->recommendation,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate analysis in job', [
                'symbol' => $this->symbol,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAnalysisJob failed', [
            'symbol' => $this->symbol,
            'user_id' => $this->userId,
            'exception' => $exception->getMessage(),
        ]);

        // Could notify user that their analysis failed
    }
}
