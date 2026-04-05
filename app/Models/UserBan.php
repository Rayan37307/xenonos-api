<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'ban_type',
        'expires_at',
        'is_permanent',
        'banned_by_type',
        'banned_by_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_permanent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannedBy(): MorphTo
    {
        return $this->morphTo('bannedBy', 'banned_by_type', 'banned_by_id');
    }

    public function isActive(): bool
    {
        if ($this->is_permanent) {
            return true;
        }
        return !$this->expires_at || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return !$this->is_permanent && $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
                ->orWhere(function ($q2) {
                    $q2->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        });
    }

    public function scopePermanent($query)
    {
        return $query->where('is_permanent', true);
    }

    public function scopeTemporary($query)
    {
        return $query->where('is_permanent', false);
    }
}
