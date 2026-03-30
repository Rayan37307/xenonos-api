<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'os_family' => $this->os_family,
            'browser' => $this->browser,
            'ip_address' => $this->ip_address,
            'location' => $this->formatLocation(),
            'user_agent' => $this->user_agent,
            'is_current' => $this->is_current,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'last_active_human' => $this->last_active_at?->diffForHumans(),
            'is_active_now' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function formatLocation(): string
    {
        $parts = array_filter([
            $this->city,
            $this->region,
            $this->country
        ]);

        return !empty($parts) ? implode(', ', $parts) : 'Unknown location';
    }
}
