<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Key Management Service
 * 
 * Provides secure access to encrypted API keys stored in database.
 * Implements caching for performance and fallback to .env for development.
 */
class ApiKeyService
{
    /**
     * Cache TTL for API keys (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get API key for a service
     *
     * @param string $service Service name (gemini, newsapi, binance, etc.)
     * @param bool $useCache Whether to use cache
     * @return string|null The decrypted API key
     */
    public function get(string $service, bool $useCache = true): ?string
    {
        $cacheKey = $this->getCacheKey($service, 'key');

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try to get from database
        $apiKey = $this->getFromDatabase($service);

        if ($apiKey && $apiKey->key_value) {
            $decryptedKey = $apiKey->key_value; // Auto-decrypted by model
            
            if ($useCache) {
                Cache::put($cacheKey, $decryptedKey, self::CACHE_TTL);
            }

            // Record usage
            $apiKey->recordUsage();

            return $decryptedKey;
        }

        // Fallback to .env for development/backward compatibility
        $envKey = $this->getEnvKey($service);
        
        if ($envKey) {
            Log::warning("Using .env fallback for {$service} API key. Consider migrating to encrypted storage.");
            return $envKey;
        }

        Log::error("API key not found for service: {$service}");
        return null;
    }

    /**
     * Get API secret for a service (for services with key+secret)
     *
     * @param string $service Service name
     * @param bool $useCache Whether to use cache
     * @return string|null The decrypted API secret
     */
    public function getSecret(string $service, bool $useCache = true): ?string
    {
        $cacheKey = $this->getCacheKey($service, 'secret');

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $apiKey = $this->getFromDatabase($service);

        if ($apiKey && $apiKey->secret_value) {
            $decryptedSecret = $apiKey->secret_value; // Auto-decrypted by model
            
            if ($useCache) {
                Cache::put($cacheKey, $decryptedSecret, self::CACHE_TTL);
            }

            return $decryptedSecret;
        }

        // Fallback to .env
        $envSecret = $this->getEnvSecret($service);
        
        if ($envSecret) {
            Log::warning("Using .env fallback for {$service} API secret.");
            return $envSecret;
        }

        return null;
    }

    /**
     * Store or update API key
     *
     * @param string $service Service name
     * @param string $key API key
     * @param string|null $secret API secret (optional)
     * @param array $options Additional options (environment, expires_at, etc.)
     * @return ApiKey
     */
    public function store(string $service, string $key, ?string $secret = null, array $options = []): ApiKey
    {
        $data = [
            'service' => $service,
            'key_value' => $key,
            'secret_value' => $secret,
            'environment' => $options['environment'] ?? config('app.env'),
            'is_active' => $options['is_active'] ?? true,
            'expires_at' => $options['expires_at'] ?? null,
        ];

        $apiKey = ApiKey::updateOrCreate(
            [
                'service' => $service,
                'environment' => $data['environment'],
            ],
            $data
        );

        // Clear cache
        $this->clearCache($service);

        Log::info("API key stored/updated for service: {$service}");

        return $apiKey;
    }

    /**
     * Rotate API key (mark current as inactive, store new one)
     *
     * @param string $service Service name
     * @param string $newKey New API key
     * @param string|null $newSecret New API secret
     * @return ApiKey
     */
    public function rotate(string $service, string $newKey, ?string $newSecret = null): ApiKey
    {
        // Mark current key as inactive
        $currentKey = $this->getFromDatabase($service);
        
        if ($currentKey) {
            $currentKey->update([
                'is_active' => false,
                'rotated_at' => now(),
            ]);
        }

        // Store new key
        $newApiKey = $this->store($service, $newKey, $newSecret, [
            'is_active' => true,
        ]);

        Log::info("API key rotated for service: {$service}");

        return $newApiKey;
    }

    /**
     * Deactivate API key
     *
     * @param string $service Service name
     * @return bool
     */
    public function deactivate(string $service): bool
    {
        $apiKey = $this->getFromDatabase($service);

        if ($apiKey) {
            $apiKey->update(['is_active' => false]);
            $this->clearCache($service);
            
            Log::info("API key deactivated for service: {$service}");
            
            return true;
        }

        return false;
    }

    /**
     * Check if service has valid API key
     *
     * @param string $service Service name
     * @return bool
     */
    public function hasKey(string $service): bool
    {
        return $this->get($service, useCache: false) !== null;
    }

    /**
     * Get API key statistics
     *
     * @param string $service Service name
     * @return array|null
     */
    public function getStats(string $service): ?array
    {
        $apiKey = $this->getFromDatabase($service);

        if (!$apiKey) {
            return null;
        }

        return [
            'service' => $apiKey->service,
            'environment' => $apiKey->environment,
            'is_active' => $apiKey->is_active,
            'is_expired' => $apiKey->isExpired(),
            'is_usable' => $apiKey->isUsable(),
            'usage_count' => $apiKey->usage_count,
            'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
            'created_at' => $apiKey->created_at->toIso8601String(),
            'expires_at' => $apiKey->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Get all services with API keys
     *
     * @return array
     */
    public function getAllServices(): array
    {
        return ApiKey::active()
            ->forEnvironment()
            ->pluck('service')
            ->toArray();
    }

    /**
     * Get API key from database
     *
     * @param string $service Service name
     * @return ApiKey|null
     */
    private function getFromDatabase(string $service): ?ApiKey
    {
        return ApiKey::where('service', $service)
            ->forEnvironment()
            ->active()
            ->first();
    }

    /**
     * Get .env fallback key
     *
     * @param string $service Service name
     * @return string|null
     */
    private function getEnvKey(string $service): ?string
    {
        $envMap = [
            'gemini' => 'GEMINI_API_KEY',
            'newsapi' => 'NEWSAPI_KEY',
            'cryptopanic' => 'CRYPTOPANIC_KEY',
            'binance' => 'BINANCE_API_KEY',
            'alpha_vantage' => 'ALPHA_VANTAGE_API_KEY',
            'twitter' => 'TWITTER_BEARER_TOKEN',
        ];

        $envKey = $envMap[$service] ?? strtoupper($service) . '_API_KEY';
        
        return env($envKey);
    }

    /**
     * Get .env fallback secret
     *
     * @param string $service Service name
     * @return string|null
     */
    private function getEnvSecret(string $service): ?string
    {
        $envMap = [
            'binance' => 'BINANCE_API_SECRET',
        ];

        $envKey = $envMap[$service] ?? null;
        
        return $envKey ? env($envKey) : null;
    }

    /**
     * Get cache key
     *
     * @param string $service Service name
     * @param string $type 'key' or 'secret'
     * @return string
     */
    private function getCacheKey(string $service, string $type = 'key'): string
    {
        $env = config('app.env');
        return "api_keys:{$env}:{$service}:{$type}";
    }

    /**
     * Clear cache for service
     *
     * @param string $service Service name
     * @return void
     */
    private function clearCache(string $service): void
    {
        Cache::forget($this->getCacheKey($service, 'key'));
        Cache::forget($this->getCacheKey($service, 'secret'));
    }
}
