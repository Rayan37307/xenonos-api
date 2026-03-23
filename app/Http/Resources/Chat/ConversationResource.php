<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $otherUser = $this->user_one_id === $request->user()->id ? $this->userTwo : $this->userOne;

        return [
            'id' => $this->id,
            'user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar ? url('storage/' . $otherUser->avatar) : null,
                'role' => $otherUser->role,
            ],
            'unread_count' => $this->unread_count ?? 0,
            'last_message' => $this->last_message,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
        ];
    }
}
