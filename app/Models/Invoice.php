<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'client_id',
        'issued_by',
        'updated_by',
        'date_issued',
        'due_date',
        'amount',
        'status',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date_issued' => 'date',
            'due_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoice')
            ->logOnly([
                'project_id',
                'client_id',
                'issued_by',
                'updated_by',
                'date_issued',
                'due_date',
                'amount',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
