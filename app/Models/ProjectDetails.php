<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProjectDetails extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'overview',
        'objectives',
        'priority',
        'start_date',
        'end_date',
        'actual_budget',
        'progress',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_budget' => 'decimal:2',
            'progress' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectEvent::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_details')
            ->logOnly(['overview', 'objectives', 'priority', 'start_date', 'end_date', 'actual_budget', 'progress', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
