<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Http\Resources\Chat\MessageResource;

class MessageController extends Controller
{
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $message->update(['message' => $validated['content']]);

        return new MessageResource($message->load(['sender', 'reactions', 'media']));
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->delete();
        
        return response()->json(['message' => 'Message deleted']);
    }

    public function addReaction(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        
        $validated = $request->validate([
            'emoji' => 'required|string|max:50',
        ]);

        $message->reactions()->firstOrCreate([
            'user_id' => Auth::id(),
            'emoji' => $validated['emoji'],
        ]);

        return new MessageResource($message->load(['sender', 'reactions', 'media']));
    }

    public function removeReaction($id, $emoji)
    {
        $message = Message::findOrFail($id);
        
        $message->reactions()
            ->where('user_id', Auth::id())
            ->where('emoji', $emoji)
            ->delete();

        return new MessageResource($message->load(['sender', 'reactions', 'media']));
    }

    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json(['message' => 'Marked as read']);
    }
}
