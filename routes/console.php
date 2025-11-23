<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Domain\Market\Services\InstrumentService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Scheduled Tasks
 */

// Warm instrument cache every 5 minutes
Schedule::call(function () {
    $instrumentService = app(InstrumentService::class);
    
    try {
        $instrumentService->warmCache();
        \Log::info('Instrument cache warmed successfully');
    } catch (\Exception $e) {
        \Log::error('Failed to warm instrument cache', [
            'error' => $e->getMessage(),
        ]);
    }
})->everyFiveMinutes()->name('warm-instrument-cache')->withoutOverlapping();

// Cleanup old cache entries (optional - Redis handles this automatically with TTL)
Schedule::call(function () {
    \Log::info('Cache cleanup task executed');
})->daily()->at('03:00')->name('cache-cleanup');
