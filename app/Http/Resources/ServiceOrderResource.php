<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_type' => $this->service_type,
            'title' => $this->title,
            'description' => $this->description,
            'budget_min' => $this->budget_min ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max ? (float) $this->budget_max : null,
            'deadline' => $this->deadline?->toIso8601String(),
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            'client' => $this->whenLoaded('client', function () {
                return $this->client ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ] : null;
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
