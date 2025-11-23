<?php

namespace App\Jobs;

use App\Domain\Sentiment\Services\SentimentEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;
    public array $backoff = [15, 45];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $symbols
    ) {
        $this->onQueue('sentiment');
    }

    /**
     * Execute the job.
     */
    public function handle(SentimentEngine $sentimentEngine): void
    {
        Log::info('ProcessSentimentJob started', ['symbols' => $this->symbols]);

        $successCount = 0;
        $errorCount = 0;

        foreach ($this->symbols as $symbol) {
            try {
                // Fetch and analyze sentiment for the symbol
                $sentiment = $sentimentEngine->analyzeSentiment($symbol);
                
                $successCount++;
                
                Log::info("Sentiment processed successfully", [
                    'symbol' => $symbol,
                    'sentiment_score' => $sentiment['overall_sentiment'] ?? 'N/A',
                ]);
            } catch (\Exception $e) {
                $errorCount++;
                
                Log::error("Failed to process sentiment", [
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProcessSentimentJob completed', [
            'total' => count($this->symbols),
            'success' => $successCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSentimentJob failed completely', [
            'symbols' => $this->symbols,
            'exception' => $exception->getMessage(),
        ]);
    }
}
