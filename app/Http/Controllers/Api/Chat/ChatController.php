<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Message;
use App\Http\Resources\Chat\MessageResource;

class ChatController extends Controller
{
    /**
     * Get online status of users.
     */
    public function onlineStatus(Request $request): JsonResponse
    {
        // In a real application, you'd use a caching layer, Redis, or WebSockets 
        // to determine if a user is actively connected.
        // Returning a stub here that says everyone queried is online.

        $userIds = $request->query('user_ids', []);
        $status = [];
        
        foreach($userIds as $id) {
            $status[$id] = 'online';
        }

        return response()->json([
            'data' => $status
        ]);
    }

    /**
     * Search messages.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        
        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        $user = auth()->user() ?? $request->user();
        
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $messages = Message::where('message', 'like', "%{$query}%")
            ->where(function($q) use ($user) {
                // Ensure the user has access to the message
                $q->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id)
                  ->orWhereHas('channel.members', function($channelQuery) use ($user) {
                      $channelQuery->where('user_id', $user->id);
                  })
                  ->orWhereHas('conversation', function($convQuery) use ($user) {
                      $convQuery->where('user_one_id', $user->id)
                                ->orWhere('user_two_id', $user->id);
                  });
            })
            ->with(['sender', 'channel', 'conversation'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => MessageResource::collection($messages)
        ]);
    }

    /**
     * Generic upload endpoint for chat attachments.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $path = $request->file('file')->store('chat-uploads', 'public');
        
        return response()->json([
            'url' => url('storage/' . $path),
            'name' => $request->file('file')->getClientOriginalName()
        ]);
    }
}
