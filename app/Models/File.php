<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class File extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'fileable_type',
        'fileable_id',
        'uploaded_by',
        'external_link',
        'is_external',
        'disk',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_external' => 'boolean',
        ];
    }

    /**
     * Get the parent fileable model (task, project, etc).
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file shares.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    /**
     * Get file access logs.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(FileAccessLog::class);
    }

    /**
     * Get human readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if ($this->is_external) {
            return 'External Link';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get full URL to the file.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_external && $this->external_link) {
            return $this->external_link;
        }

        return asset('storage/' . $this->path);
    }

    /**
     * Check if file is stored externally.
     */
    public function isExternal(): bool
    {
        return $this->is_external === true;
    }

    /**
     * Get file download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        if ($this->is_external) {
            return $this->external_link;
        }

        return url("/api/files/{$this->id}/download");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('file')
            ->logOnly([
                'name',
                'original_name',
                'mime_type',
                'size',
                'fileable_type',
                'fileable_id',
                'uploaded_by',
                'is_external',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
