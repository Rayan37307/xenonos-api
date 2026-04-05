<?php

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $clientUser;
    private User $worker;
    private Client $clientProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->clientUser = User::factory()->client()->create();
        $this->clientProfile = Client::factory()->create([
            'user_id' => $this->clientUser->id,
        ]);
        $this->worker = User::factory()->worker()->create();
    }

    public function test_admin_can_list_clients(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clients' => [['id', 'user_id', 'company_name', 'phone', 'address', 'status']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_admin_can_search_clients(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Client::factory()->create([
            'company_name' => 'Acme Corp',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients?search=Acme');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('clients'));
    }

    public function test_admin_can_filter_clients_by_status(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Client::factory()->inactive()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients?status=inactive');

        $response->assertStatus(200);
        foreach ($response->json('clients') as $client) {
            $this->assertEquals('inactive', $client['status']);
        }
    }

    public function test_admin_can_view_single_client(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients/' . $this->clientProfile->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'client' => ['id', 'user_id', 'company_name', 'phone', 'address', 'status', 'user'],
            ]);
    }

    public function test_admin_can_create_client(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company_name' => 'John Corp',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'client' => ['id', 'user_id', 'company_name', 'status'],
            ]);

        $this->assertDatabaseHas('clients', [
            'company_name' => 'John Corp',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'client',
        ]);
    }

    public function test_admin_can_update_client(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/admin/clients/' . $this->clientProfile->id, [
            'company_name' => 'Updated Corp',
            'status' => 'inactive',
            'notes' => 'Test note',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Client updated successfully',
                'client' => [
                    'company_name' => 'Updated Corp',
                    'status' => 'inactive',
                ],
            ]);
    }

    public function test_admin_can_delete_client(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/admin/clients/' . $this->clientProfile->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Client deleted successfully']);

        $this->assertSoftDeleted('clients', ['id' => $this->clientProfile->id]);
    }

    public function test_admin_can_view_client_stats(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Project::factory()->create([
            'client_id' => $this->clientProfile->id,
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients/' . $this->clientProfile->id . '/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_projects',
                    'active_projects',
                    'completed_projects',
                    'total_invoices',
                    'total_service_orders',
                ],
            ]);
    }

    public function test_non_admin_cannot_list_clients(): void
    {
        $token = $this->clientUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/clients');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_create_client(): void
    {
        $token = $this->clientUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/clients', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_validation_fails_on_missing_fields(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/clients', [
            'name' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
