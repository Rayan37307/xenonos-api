<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, Client $client): bool
    {
        return $user->role === 'admin' || $user->id === $client->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }
}
