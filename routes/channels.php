<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private chat channel
Broadcast::channel('chat.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
});

// Project chat room
Broadcast::channel('project.{projectId}', function (User $user, int $projectId) {
    // Check if user is assigned to the project or is the client
    return $user->assignedProjects()->where('project_id', $projectId)->exists() ||
           $user->clientProfile?->projects()->where('id', $projectId)->exists() ||
           $user->isAdmin();
});

// Presence channel for online users
Broadcast::channel('online-users', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
    ];
});
