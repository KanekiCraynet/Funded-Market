<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class ApiKey extends Model
{
    protected $fillable = [
        'service',
        'key_value',
        'secret_value',
        'environment',
        'is_active',
        'expires_at',
        'last_used_at',
        'rotated_at',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'rotated_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    protected $hidden = [
        'key_value',
        'secret_value',
    ];

    /**
     * Automatically encrypt key when setting
     */
    protected function keyValue(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Automatically encrypt secret when setting
     */
    protected function secretValue(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Check if key is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if key is usable
     */
    public function isUsable(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Increment usage counter
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope: Active keys only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope: Filter by environment
     */
    public function scopeForEnvironment($query, string $environment = null)
    {
        $environment = $environment ?? config('app.env');
        return $query->where('environment', $environment);
    }
}
