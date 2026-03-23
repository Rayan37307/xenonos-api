<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['role' => 'worker']);
        $this->user2 = User::factory()->create(['role' => 'worker']);
    }

    public function test_can_send_private_message(): void
    {
        $token = $this->user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/chat/messages/private', [
            'receiver_id' => $this->user2->id,
            'message' => 'Hello from user 1!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'message', 'sender', 'receiver'],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Hello from user 1!',
        ]);
    }

    public function test_can_get_private_messages(): void
    {
        $token = $this->user1->createToken('test-token')->plainTextToken;

        Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Test message 1',
        ]);

        Message::create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'message' => 'Test message 2',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/chat/messages/private/' . $this->user2->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'messages' => [['id', 'message', 'sender', 'receiver']],
            ]);
    }

    public function test_can_mark_message_as_read(): void
    {
        $token = $this->user2->createToken('test-token')->plainTextToken;

        $message = Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Unread message',
            'is_read' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/chat/messages/' . $message->id . '/read');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Message marked as read']);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_can_get_unread_count(): void
    {
        $token = $this->user2->createToken('test-token')->plainTextToken;

        Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Unread message 1',
            'is_read' => false,
        ]);

        Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Unread message 2',
            'is_read' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/chat/unread-count');

        $response->assertStatus(200)
            ->assertJson(['unread_count' => 2]);
    }

    public function test_can_get_conversations(): void
    {
        $token = $this->user1->createToken('test-token')->plainTextToken;

        Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'message' => 'Hello',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversations' => [['user', 'last_message', 'unread_count']],
            ]);
    }
}
