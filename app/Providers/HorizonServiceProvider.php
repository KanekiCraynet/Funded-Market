<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            // Allow access in local environment
            if (app()->environment('local')) {
                return true;
            }

            // In production, only allow admins or specific users
            // Adjust this logic based on your User model and authorization needs
            
            // Option 1: Check for admin role (if you have a role system)
            // return $user->hasRole('admin');
            
            // Option 2: Check for specific email addresses
            return in_array($user->email, [
                'admin@example.com',
                // Add your admin emails here
            ]);
            
            // Option 3: Check for a specific user attribute
            // return $user->is_admin === true;
            
            // Option 4: Use Laravel's authorization policies
            // return $user->can('view-horizon');
        });
    }
}
