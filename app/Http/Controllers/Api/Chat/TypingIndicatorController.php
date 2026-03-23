<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TypingIndicatorController extends Controller
{
    /**
     * Broadcast typing indicator.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['nullable', 'exists:users,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'is_typing' => ['boolean'],
        ]);

        $user = $request->user();

        event(new UserTyping(
            $user->id,
            $user->name,
            $validated['receiver_id'] ?? null,
            $validated['project_id'] ?? null,
            $validated['is_typing'] ?? true
        ));

        return response()->json([
            'message' => 'Typing indicator sent',
        ]);
    }
}
