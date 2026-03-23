<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Http\Resources\Chat\ConversationResource;
use App\Http\Resources\Chat\MessageResource;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user() ?? $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne', 'userTwo'])
            ->get();

        $conversations->each(function($c) {
            $msg = $c->messages()->latest()->first();
            $c->last_message = $msg ? $msg->message : null;
            $c->last_message_at = $msg ? $msg->created_at : null;
            $c->unread_count = 0; // Stub
        });

        return ConversationResource::collection($conversations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|different:'.Auth::id(),
        ]);

        $user1 = min(Auth::id(), $validated['user_id']);
        $user2 = max(Auth::id(), $validated['user_id']);

        $conversation = Conversation::firstOrCreate([
            'user_one_id' => $user1,
            'user_two_id' => $user2,
        ]);

        return new ConversationResource($conversation->load(['userOne', 'userTwo']));
    }

    public function messages(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        
        $messages = $conversation->messages()
            ->with(['sender', 'reactions', 'media'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return MessageResource::collection($messages);
    }

    public function sendMessage(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|file',
        ]);

        // receiver is the one that is not me
        $receiverId = $conversation->user_one_id === Auth::id() ? $conversation->user_two_id : $conversation->user_one_id;

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiverId,
            'message' => $validated['content'],
            'is_read' => false,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $message->addMedia($file)->toMediaCollection('attachments');
            }
        }

        return new MessageResource($message->load(['sender', 'reactions', 'media']));
    }
}
