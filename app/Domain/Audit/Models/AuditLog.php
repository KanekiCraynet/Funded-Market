<?php

namespace App\Domain\Audit\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseModel
{
    protected $fillable = [
        'user_id',
        'event_type',
        'context',
        'severity',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that owns the audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs of a given event type.
     */
    public function scopeOfEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to only include logs of a given severity.
     */
    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if this is an error log.
     */
    public function isError(): bool
    {
        return in_array($this->severity, ['error', 'critical']);
    }

    /**
     * Check if this is a critical log.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
