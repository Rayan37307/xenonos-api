<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_image_link' => $this->profile_image_link,
            'role' => $this->role,
            'avatar' => $this->avatar ?? ($this->hasMedia('avatar') ? $this->getFirstMediaUrl('avatar') : null),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'client_profile' => $this->whenLoaded('clientProfile', function () {
                return [
                    'id' => $this->clientProfile->id,
                    'company_name' => $this->clientProfile->company_name,
                    'phone' => $this->clientProfile->phone,
                    'address' => $this->clientProfile->address,
                ];
            }),

            // Stats (only for workers)
            'stats' => $this->when($this->role === 'worker', function () {
                return [
                    'total_tasks' => $this->assignedTasks()->count(),
                    'completed_tasks' => $this->assignedTasks()->where('status', 'completed')->count(),
                ];
            }),
        ];
    }
}
