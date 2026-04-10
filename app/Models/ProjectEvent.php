<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ProjectEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'event_date',
        'end_date',
        'color',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'end_date' => 'datetime',
            'is_completed' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_event')
            ->logOnly(['title', 'description', 'type', 'event_date', 'end_date', 'color', 'is_completed'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
