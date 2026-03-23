<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Http\Resources\Chat\ChannelResource;
use App\Http\Resources\Chat\MessageResource;

class ChannelController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user() ?? $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $channels = Channel::where('type', 'public')
            ->orWhereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();
            
        // Mocking last_message for demo purposes. In production this would be a subquery or relation.
        $channels->each(function($c) {
            $msg = $c->messages()->latest()->first();
            $c->last_message = $msg ? $msg->message : null;
            $c->last_message_at = $msg ? $msg->created_at : null;
            $c->unread_count = 0; // Not robustly implemented yet
        });

        return ChannelResource::collection($channels);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:public,private',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id'
        ]);

        $channel = Channel::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'created_by_id' => Auth::id(),
        ]);

        $members = $validated['member_ids'] ?? [];
        $members[] = Auth::id(); // always add creator
        $channel->members()->sync(array_unique($members));

        return new ChannelResource($channel->loadCount('members'));
    }

    public function show($id)
    {
        $channel = Channel::withCount('members')->findOrFail($id);
        return new ChannelResource($channel);
    }

    public function update(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:public,private',
        ]);

        $channel->update($validated);
        return new ChannelResource($channel->loadCount('members'));
    }

    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);
        $channel->delete();
        return response()->json(['message' => 'Channel deleted']);
    }

    public function addMember(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $channel->members()->syncWithoutDetaching([$validated['user_id']]);
        return response()->json(['message' => 'Member added']);
    }

    public function removeMember($id, $userId)
    {
        $channel = Channel::findOrFail($id);
        $channel->members()->detach($userId);
        return response()->json(['message' => 'Member removed']);
    }

    public function messages(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        $messages = $channel->messages()
            ->with(['sender', 'reactions', 'media'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return MessageResource::collection($messages);
    }

    public function sendMessage(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        
        $validated = $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|file',
        ]);

        $message = $channel->messages()->create([
            'sender_id' => Auth::id(),
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
