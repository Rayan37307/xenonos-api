<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_id',
        'device_name',
        'device_type',
        'os_family',
        'browser',
        'ip_address',
        'city',
        'region',
        'country',
        'user_agent',
        'is_current',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted last active time.
     */
    public function getFormattedLastActiveAttribute(): string
    {
        if (!$this->last_active_at) {
            return 'Unknown';
        }

        $diff = $this->last_active_at->diffForHumans();
        return $diff;
    }

    /**
     * Check if session is active (within last 30 minutes).
     */
    public function isActive(): bool
    {
        return $this->last_active_at && $this->last_active_at->gt(now()->subMinutes(30));
    }

    /**
     * Get device icon.
     */
    public function getDeviceIconAttribute(): string
    {
        if ($this->device_type === 'mobile') {
            return 'smartphone';
        } elseif ($this->device_type === 'tablet') {
            return 'tablet';
        }
        return 'monitor';
    }
}
