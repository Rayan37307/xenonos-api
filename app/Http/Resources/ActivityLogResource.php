<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Spatie\Activitylog\Models\Activity */
class ActivityLogResource extends JsonResource
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
            'properties' => $this->properties,
            'causer' => $this->when(
                $this->relationLoaded('causer'),
                fn () => $this->causer ? [
                    'id' => $this->causer->id,
                    'name' => $this->causer->name,
                    'email' => $this->causer->email,
                ] : null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
