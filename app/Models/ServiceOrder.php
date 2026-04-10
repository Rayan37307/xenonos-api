<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ServiceOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_id',
        'user_id',
        'service_type',
        'title',
        'description',
        'budget_min',
        'budget_max',
        'deadline',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('service_order')
            ->logOnly([
                'client_id',
                'user_id',
                'service_type',
                'title',
                'description',
                'budget_min',
                'budget_max',
                'deadline',
                'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
