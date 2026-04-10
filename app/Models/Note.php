<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Note extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'noteable_type',
        'noteable_id',
        'title',
        'content',
        'color',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('noteable_type', $type)
            ->where('noteable_id', $id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('note')
            ->logOnly(['title', 'content', 'color', 'is_pinned'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
