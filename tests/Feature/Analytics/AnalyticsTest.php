<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $worker;
    private Client $clientProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->client = User::factory()->create(['role' => 'client']);
        $this->clientProfile = Client::create(['user_id' => $this->client->id, 'company_name' => 'Test']);
        $this->worker = User::factory()->create(['role' => 'worker']);
    }

    public function test_admin_can_get_dashboard_analytics(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Create some data
        Project::factory()->count(3)->create(['client_id' => $this->clientProfile->id]);
        Task::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'overview' => [
                        'total_projects',
                        'active_projects',
                        'total_clients',
                        'total_workers',
                    ],
                ],
            ]);
    }

    public function test_worker_can_get_personal_dashboard(): void
    {
        $token = $this->worker->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'user_info' => ['name', 'role'],
                    'tasks' => [
                        'total_tasks',
                        'completed_tasks',
                        'pending_tasks',
                    ],
                ],
            ]);
    }

    public function test_can_get_overview(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Project::factory()->count(2)->create(['client_id' => $this->clientProfile->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    'total_projects',
                    'active_projects',
                    'completed_projects',
                    'total_clients',
                    'total_workers',
                ],
            ]);
    }

    public function test_can_get_recent_projects(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Project::factory()->count(5)->create(['client_id' => $this->clientProfile->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/recent-projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'projects' => [['id', 'name', 'status', 'progress']],
            ]);
    }

    public function test_can_get_active_tasks(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Task::factory()->count(3)->create(['status' => 'in_progress']);
        Task::factory()->create(['status' => 'todo']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/active-tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks' => [['id', 'title', 'status', 'priority']],
            ]);
    }

    public function test_can_get_worker_productivity(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/analytics/worker-productivity');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'productivity' => [['id', 'name', 'total_tasks', 'completed_tasks', 'completion_rate']],
            ]);
    }
}
