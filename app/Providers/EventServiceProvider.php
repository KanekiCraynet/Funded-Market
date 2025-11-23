<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Custom events
        'App\Events\AnalysisGenerated' => [
            'App\Listeners\LogAnalysisGenerated',
            'App\Listeners\UpdateUserStatistics',
        ],
        
        'App\Events\MarketDataUpdated' => [
            'App\Listeners\UpdateCache',
            'App\Listeners\TriggerAlerts',
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}