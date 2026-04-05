<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->sender_id,
            'user' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar ? url('storage/' . $this->sender->avatar) : null,
                'role' => $this->sender->role,
            ],
            'content' => $this->message,
            'content_hash' => $this->content_hash,
            'attachments' => $this->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ];
            }),
            'reactions' => $this->reactions->groupBy('emoji')->map(function ($reactions, $emoji) {
                return [
                    'emoji' => $emoji,
                    'count' => $reactions->count(),
                    'users' => $reactions->pluck('user_id'),
                ];
            })->values(),
            // Just a basic representation for read_by for now (or empty if not tracking perfectly yet)
            'read_by' => $this->is_read ? [$this->receiver_id] : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
