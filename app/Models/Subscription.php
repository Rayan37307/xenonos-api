<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Subscription extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_id',
        'plan_name',
        'amount',
        'billing_cycle',
        'start_date',
        'end_date',
        'status',
        'payment_method',
        'external_subscription_id',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'string',
            'billing_cycle' => 'string',
            'features' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function renew(): void
    {
        $cycleDays = match($this->billing_cycle) {
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };
        
        $this->start_date = $this->end_date ?? now();
        $this->end_date = $this->start_date->copy()->addDays($cycleDays);
        $this->status = 'active';
        $this->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('subscription')
            ->logOnly([
                'client_id',
                'plan_name',
                'amount',
                'billing_cycle',
                'start_date',
                'end_date',
                'status',
                'payment_method',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
