<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'message' => $this->message,
            'content_hash' => $this->content_hash,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            
            // Relationships
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar ?? $this->sender->getFirstMediaUrl('avatar'),
            ],
            
            'receiver' => $this->whenLoaded('receiver', function () {
                return $this->receiver ? [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                    'avatar' => $this->receiver->avatar ?? $this->receiver->getFirstMediaUrl('avatar'),
                ] : null;
            }),
            
            'project' => $this->whenLoaded('project', function () {
                return $this->project ? [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                ] : null;
            }),
        ];
    }
}
