<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'unread_count' => $this->unread_count ?? 0,
            'last_message' => $this->last_message,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'members_count' => $this->members_count ?? $this->members()->count(),
        ];
    }
}
