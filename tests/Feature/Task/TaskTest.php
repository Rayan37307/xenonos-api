<?php

namespace Tests\Feature\Task;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $worker;
    private Client $clientProfile;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->client = User::factory()->create(['role' => 'client']);
        $this->clientProfile = Client::create(['user_id' => $this->client->id, 'company_name' => 'Test']);
        $this->worker = User::factory()->create(['role' => 'worker']);
        $this->project = Project::factory()->create(['client_id' => $this->clientProfile->id]);
    }

    public function test_can_create_task(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Task description',
            'project_id' => $this->project->id,
            'status' => 'todo',
            'priority' => 'high',
            'progress' => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'task' => ['id', 'title', 'description', 'status', 'priority'],
            ]);
    }

    public function test_can_get_tasks(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Task::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tasks?project_id=' . $this->project->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks' => [['id', 'title', 'status', 'priority']],
            ]);
    }

    public function test_can_update_task(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'progress' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/tasks/' . $task->id, [
            'status' => 'in_progress',
            'progress' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Task updated successfully',
                'task' => [
                    'status' => 'in_progress',
                    'progress' => 50,
                ],
            ]);
    }

    public function test_can_assign_task_to_worker(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks/' . $task->id . '/assign', [
            'worker_id' => $this->worker->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'task' => ['assigned_worker' => ['id', 'name']],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $this->worker->id,
        ]);
    }

    public function test_can_update_task_progress(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'progress' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks/' . $task->id . '/progress', [
            'progress' => 75,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Progress updated successfully',
                'task' => ['progress' => 75],
            ]);
    }

    public function test_can_get_kanban_tasks(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        Task::factory()->create(['project_id' => $this->project->id, 'status' => 'todo']);
        Task::factory()->create(['project_id' => $this->project->id, 'status' => 'in_progress']);
        Task::factory()->create(['project_id' => $this->project->id, 'status' => 'completed']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/projects/' . $this->project->id . '/tasks/kanban');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kanban' => [
                    'todo',
                    'in_progress',
                    'review',
                    'completed',
                ],
            ]);
    }

    public function test_can_delete_task(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/tasks/' . $task->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
