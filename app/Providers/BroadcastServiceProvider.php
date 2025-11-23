<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::ignoreMigrations();

        // Broadcast authentication
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}