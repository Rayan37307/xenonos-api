<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'worker',
            'phone_number' => $data['phone_number'] ?? null,
            'profile_image_link' => $data['profile_image_link'] ?? null,
        ]);

        // Assign default role if roles exist (skip in testing if roles table is empty)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            try {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $user->role]);
                $user->assignRole($role);
            } catch (\Exception $e) {
                // Role assignment failed, continue without role
            }
        }

        // Create client profile if role is client
        if ($user->isClient() && isset($data['company_name'])) {
            $user->clientProfile()->create([
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
        }

        event(new Registered($user));

        return $user;
    }

    /**
     * Login user and create token.
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user (revoke tokens).
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Get authenticated user.
     */
    public function me(): User
    {
        return Auth::user();
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone_number' => $data['phone_number'] ?? $user->phone_number,
            'profile_image_link' => $data['profile_image_link'] ?? $user->profile_image_link,
        ]);

        if (isset($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        return $user->fresh();
    }

    /**
     * Update user avatar.
     */
    public function updateAvatar(User $user, $avatar): User
    {
        if ($avatar) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($avatar)
                ->toMediaCollection('avatar');
            
            $user->update([
                'avatar' => $user->getFirstMediaUrl('avatar'),
            ]);
        }

        return $user;
    }
}
