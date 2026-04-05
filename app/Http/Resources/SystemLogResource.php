<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Activitylog\Models\Activity */
class SystemLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject' => $this->when($this->relationLoaded('subject'), fn () => $this->formatSubject()),
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer' => $this->when($this->relationLoaded('causer'), fn () => $this->formatCauser()),
            'properties' => $this->properties,
            'batch_uuid' => $this->batch_uuid,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function formatCauser(): ?array
    {
        if (!$this->causer) {
            return null;
        }

        return match (true) {
            method_exists($this->causer, 'getEmailAttribute') => [
                'id' => $this->causer->id,
                'name' => $this->causer->name ?? null,
                'email' => $this->causer->email,
                'type' => class_basename($this->causer),
            ],
            default => [
                'id' => $this->causer->id,
                'type' => class_basename($this->causer),
            ],
        };
    }

    protected function formatSubject(): ?array
    {
        if (!$this->subject) {
            return null;
        }

        return [
            'id' => $this->subject->id,
            'type' => class_basename($this->subject),
        ];
    }
}
