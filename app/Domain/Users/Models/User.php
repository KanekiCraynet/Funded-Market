<?php

namespace App\Domain\Users\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\History\Models\Analysis;
use App\Domain\Users\Enums\TokenAbility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'email_verified',
        'preferences',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'preferences' => 'json',
        'metadata' => 'json',
    ];

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function getPreferredRiskLevelAttribute(): string
    {
        return $this->preferences['risk_level'] ?? 'MEDIUM';
    }

    public function getPreferredTimeHorizonAttribute(): string
    {
        return $this->preferences['time_horizon'] ?? 'medium_term';
    }

    public function getMaxPositionSizeAttribute(): float
    {
        return $this->preferences['max_position_size'] ?? 15.0;
    }

    public function getAnalysisCountAttribute(): int
    {
        return $this->analyses()->count();
    }

    public function getLastAnalysisAtAttribute(): ?\Carbon\Carbon
    {
        return $this->analyses()->latest()->first()?->created_at;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if user email is verified
     */
    public function isVerified(): bool
    {
        return $this->email_verified === true;
    }

    /**
     * Create a new API token with standard user abilities
     */
    public function createApiToken(string $name = 'api-token', ?\DateTimeInterface $expiresAt = null): NewAccessToken
    {
        return $this->createToken(
            $name,
            TokenAbility::userAbilities(),
            $expiresAt
        );
    }

    /**
     * Create a new API token with custom abilities
     */
    public function createTokenWithAbilities(string $name, array $abilities, ?\DateTimeInterface $expiresAt = null): NewAccessToken
    {
        return $this->createToken($name, $abilities, $expiresAt);
    }

    /**
     * Create a read-only API token
     */
    public function createReadOnlyToken(string $name = 'readonly-token', ?\DateTimeInterface $expiresAt = null): NewAccessToken
    {
        return $this->createToken(
            $name,
            TokenAbility::readAbilities(),
            $expiresAt
        );
    }

    /**
     * Create an admin API token (if user is admin)
     */
    public function createAdminToken(string $name = 'admin-token', ?\DateTimeInterface $expiresAt = null): NewAccessToken
    {
        // TODO: Add admin role check here
        // if (!$this->hasRole('admin')) {
        //     throw new \Exception('User is not an admin');
        // }

        return $this->createToken(
            $name,
            TokenAbility::allAbilities(),
            $expiresAt
        );
    }

    /**
     * Revoke all tokens for this user
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    /**
     * Revoke a specific token by ID
     */
    public function revokeToken(int $tokenId): bool
    {
        return $this->tokens()->where('id', $tokenId)->delete() > 0;
    }

    /**
     * Get user's active tokens
     */
    public function activeTokens()
    {
        return $this->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            });
    }
}