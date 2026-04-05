<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'client_id',
        'status',
        'budget',
        'deadline',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get workers assigned to this project.
     */
    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_workers')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get tasks for this project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get messages for this project (project chat).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get files for this project.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get comments for this project.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get project details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ProjectDetails::class);
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include projects for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Get task count by status.
     */
    public function getTaskCountByStatusAttribute(): array
    {
        return [
            'todo' => $this->tasks()->where('status', 'todo')->count(),
            'in_progress' => $this->tasks()->where('status', 'in_progress')->count(),
            'review' => $this->tasks()->where('status', 'review')->count(),
            'completed' => $this->tasks()->where('status', 'completed')->count(),
        ];
    }

    /**
     * Get overall progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) {
            return 0;
        }
        return round($tasks->avg('progress'), 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project')
            ->logOnly(['name', 'description', 'client_id', 'status', 'budget', 'deadline'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
