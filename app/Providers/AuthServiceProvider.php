<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Models and their policies
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Gates for authorization
        Gate::define('view-analysis', function ($user, $analysis) {
            return $user->id === $analysis->user_id;
        });

        Gate::define('create-analysis', function ($user) {
            return $user->is_active && $user->email_verified;
        });

        Gate::define('access-premium-features', function ($user) {
            // Would check subscription status
            return true; // For now, allow all active users
        });
    }
}