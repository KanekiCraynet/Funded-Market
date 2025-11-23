<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAnalysisNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300]; // 1min, 5min

    public function __construct(
        private string $analysisId,
        private int $userId
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $analysis = \App\Domain\History\Models\Analysis::with(['user', 'instrument'])
                ->findOrFail($this->analysisId);

            $user = $analysis->user;
            
            if (!$user->preferences['notifications'] ?? true) {
                Log::info("User {$user->id} has notifications disabled");
                return;
            }

            // Send email notification
            Mail::to($user->email)->send(new \App\Mail\AnalysisCompletedMail($analysis));

            Log::info("Analysis notification sent to user {$user->id} for analysis {$this->analysisId}");

        } catch (\Exception $e) {
            Log::error("Failed to send analysis notification: " . $e->getMessage());
        }
    }
}