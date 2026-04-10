<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Task extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'project_id',
        'assigned_to',
        'created_by',
        'status',
        'priority',
        'progress',
        'deadline',
        'estimated_hours',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'deadline' => 'date',
            'estimated_hours' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($task) {
            if ($task->position === 0) {
                $task->position = static::where('project_id', $task->project_id)->max('position') + 1;
            }
        });
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the worker assigned to this task.
     */
    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get time tracking entries for this task.
     */
    public function timeTracking(): HasMany
    {
        return $this->hasMany(TaskTimeTracking::class);
    }

    /**
     * Get files attached to this task.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get comments for this task.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get total tracked time in seconds.
     */
    public function getTotalTrackedTimeAttribute(): int
    {
        return $this->timeTracking()->sum('duration_seconds');
    }

    /**
     * Get total tracked time formatted (HH:MM).
     */
    public function getFormattedTrackedTimeAttribute(): string
    {
        $seconds = $this->total_tracked_time;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Scope a query to only include tasks with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tasks assigned to a specific user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope a query to order tasks by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task')
            ->logOnly([
                'title',
                'description',
                'project_id',
                'assigned_to',
                'created_by',
                'status',
                'priority',
                'progress',
                'deadline',
                'estimated_hours',
                'position',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
