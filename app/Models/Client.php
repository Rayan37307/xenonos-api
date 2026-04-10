<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Client extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'company_name',
        'phone',
        'address',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('client')
            ->logOnly([
                'user_id',
                'company_name',
                'phone',
                'address',
                'status',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getStatsAttribute(): array
    {
        return [
            'total_projects' => $this->projects()->count(),
            'active_projects' => $this->projects()->where('status', 'active')->count(),
            'completed_projects' => $this->projects()->where('status', 'completed')->count(),
            'total_invoices' => $this->invoices()->count(),
            'total_invoice_amount' => $this->invoices()->sum('amount'),
            'unpaid_invoices' => $this->invoices()->where('status', 'unpaid')->count(),
            'total_service_orders' => $this->serviceOrders()->count(),
            'pending_service_orders' => $this->serviceOrders()->where('status', 'pending')->count(),
        ];
    }
}
