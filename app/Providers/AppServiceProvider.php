<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Domain\Market\Services\MarketDataService;
use App\Domain\Market\Services\InstrumentService;
use App\Domain\Quant\Services\QuantEngine;
use App\Domain\Sentiment\Services\SentimentEngine;
use App\Domain\Fusion\Services\FusionEngine;
use App\Domain\LLM\Services\LLMOrchestrator;
use App\Domain\Shared\Services\CircuitBreakerService;
use App\Services\ApiKeyService;
use App\Services\SanitizationService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain Services
        $this->app->singleton(MarketDataService::class);
        $this->app->singleton(InstrumentService::class);
        $this->app->singleton(QuantEngine::class);
        $this->app->singleton(SentimentEngine::class);
        $this->app->singleton(FusionEngine::class);
        $this->app->singleton(LLMOrchestrator::class);
        
        // Shared Services
        $this->app->singleton(CircuitBreakerService::class);
        $this->app->singleton(ApiKeyService::class);
        $this->app->singleton(SanitizationService::class);
    }

    public function boot(): void
    {
        // Redis health check - fallback to file cache if Redis is unavailable
        $this->checkRedisHealth();

        // Custom validation rules
        \Validator::extend('valid_symbol', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[A-Z0-9\.\-]{1,10}$/i', $value);
        });

        // Custom Blade directives
        \Blade::directive('formatNumber', function ($expression) {
            return "<?php echo number_format($expression, 2); ?>";
        });

        \Blade::directive('formatPercent', function ($expression) {
            return "<?php echo number_format($expression, 2) . '%'; ?>";
        });
    }

    /**
     * Check Redis health and fallback to file cache if unavailable
     */
    private function checkRedisHealth(): void
    {
        // Only check if Redis is configured as default
        if (config('cache.default') !== 'redis') {
            return;
        }

        try {
            // Attempt to ping Redis
            Cache::driver('redis')->getStore()->connection()->ping();
            Log::debug('Redis health check: OK');
        } catch (\Exception $e) {
            // Redis is not available - fallback to file cache
            Log::warning('Redis unavailable, falling back to file cache', [
                'error' => $e->getMessage(),
            ]);
            
            // Change cache driver to file
            config(['cache.default' => 'file']);
            
            // Optionally notify admin
            if (app()->environment('production')) {
                Log::critical('Redis is down in production! Using file cache fallback.');
                // TODO: Send alert to monitoring system (Sentry, Slack, etc.)
            }
        }
    }
}