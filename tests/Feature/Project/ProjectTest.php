<?php

namespace Tests\Feature\Project;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $worker;
    private Client $clientProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create client with profile
        $this->client = User::factory()->create(['role' => 'client']);
        $this->clientProfile = Client::create([
            'user_id' => $this->client->id,
            'company_name' => 'Test Company',
        ]);

        // Create worker
        $this->worker = User::factory()->create(['role' => 'worker']);
    }

    public function test_admin_can_create_project(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/projects', [
            'name' => 'Test Project',
            'description' => 'Project description',
            'client_id' => $this->clientProfile->id,
            'status' => 'active',
            'budget' => 10000,
            'deadline' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'project' => ['id', 'name', 'description', 'status', 'budget'],
            ]);
    }

    public function test_can_get_all_projects(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Project::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'projects' => [['id', 'name', 'description', 'status']],
            ]);
    }

    public function test_can_get_project_by_id(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $project = Project::factory()->create([
            'name' => 'Specific Project',
            'client_id' => $this->clientProfile->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'project' => ['id', 'name', 'description', 'status'],
            ]);
    }

    public function test_can_update_project(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $project = Project::factory()->create([
            'client_id' => $this->clientProfile->id,
            'name' => 'Original Name',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/projects/' . $project->id, [
            'name' => 'Updated Name',
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Project updated successfully',
                'project' => [
                    'name' => 'Updated Name',
                    'status' => 'completed',
                ],
            ]);
    }

    public function test_can_delete_project(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $project = Project::factory()->create([
            'client_id' => $this->clientProfile->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project deleted successfully']);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_can_assign_workers_to_project(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $project = Project::factory()->create([
            'client_id' => $this->clientProfile->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/projects/' . $project->id . '/workers', [
            'worker_ids' => [$this->worker->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'project' => [
                    'workers' => [['id', 'name', 'email']],
                ],
            ]);

        $this->assertDatabaseHas('project_workers', [
            'project_id' => $project->id,
            'user_id' => $this->worker->id,
        ]);
    }

    public function test_can_get_project_statistics(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $project = Project::factory()->create([
            'client_id' => $this->clientProfile->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/projects/' . $project->id . '/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_tasks',
                    'completed_tasks',
                    'progress_percentage',
                    'total_workers',
                ],
            ]);
    }
}
