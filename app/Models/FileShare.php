<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FileShare extends Pivot
{
    use HasFactory;

    protected $table = 'file_shares';

    protected $fillable = [
        'file_id',
        'user_id',
        'shared_by',
        'permission',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'permission' => 'string',
            'expires_at' => 'datetime',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canDownload(): bool
    {
        return in_array($this->permission, ['download', 'edit']) && !$this->isExpired();
    }

    public function canEdit(): bool
    {
        return $this->permission === 'edit' && !$this->isExpired();
    }
}
