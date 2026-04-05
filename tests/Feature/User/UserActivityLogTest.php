<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_activity_entry(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'login',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);
    }

    public function test_authenticated_user_can_list_their_activity(): void
    {
        $user = User::factory()->create();

        activity()
            ->causedBy($user)
            ->useLog('auth')
            ->log('test_event');

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/activity');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'description',
                        'log_name',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('test_event', $response->json('data.0.description'));
    }
}
