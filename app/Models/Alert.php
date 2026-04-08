<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'severity',
        'status',
        'related_entity_id',
        'related_entity_type',
        'triggered_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'severity' => 'string',
            'status' => 'string',
            'related_entity_id' => 'integer',
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedEntity(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function dismiss(): void
    {
        $this->status = 'dismissed';
        $this->save();
    }

    public function resolve(): void
    {
        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->save();
    }
}
