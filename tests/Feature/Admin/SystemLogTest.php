<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SystemLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['role' => 'worker']);
    }

    public function test_non_admin_cannot_access_system_logs(): void
    {
        $token = $this->regularUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs');

        $response->assertForbidden();
    }

    public function test_admin_can_list_system_logs(): void
    {
        activity()
            ->useLog('auth')
            ->event('login')
            ->causedBy($this->admin)
            ->log('User logged in');

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'log_name',
                        'description',
                        'event',
                        'causer_type',
                        'causer_id',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('User logged in', $response->json('data.0.description'));
    }

    public function test_admin_can_filter_logs_by_log_name(): void
    {
        activity()->useLog('auth')->event('login')->log('Login event');
        activity()->useLog('user')->event('updated')->log('User updated');

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs?log_name=auth');

        $response->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->toArray();
        $this->assertContains('Login event', $descriptions);
        $this->assertNotContains('User updated', $descriptions);
    }

    public function test_admin_can_filter_logs_by_event(): void
    {
        activity()->useLog('auth')->event('login')->log('Login event');
        activity()->useLog('auth')->event('logout')->log('Logout event');

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs?event=login');

        $response->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->toArray();
        $this->assertContains('Login event', $descriptions);
        $this->assertNotContains('Logout event', $descriptions);
    }

    public function test_admin_can_search_logs(): void
    {
        activity()->useLog('auth')->event('login')->log('User admin logged in');
        activity()->useLog('user')->event('updated')->log('Profile updated');

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs?search=admin');

        $response->assertOk();

        $descriptions = collect($response->json('data'))->pluck('description')->toArray();
        $this->assertContains('User admin logged in', $descriptions);
        $this->assertNotContains('Profile updated', $descriptions);
    }

    public function test_admin_can_view_single_log_entry(): void
    {
        $activity = activity()
            ->useLog('auth')
            ->event('login')
            ->causedBy($this->admin)
            ->log('User logged in');

        $logEntry = Activity::query()->latest()->first();

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/system-logs/{$logEntry->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'log' => [
                    'id',
                    'log_name',
                    'description',
                    'event',
                    'causer_type',
                    'causer_id',
                    'causer',
                    'created_at',
                ],
            ]);

        $this->assertSame('User logged in', $response->json('log.description'));
    }

    public function test_admin_can_delete_log_entry(): void
    {
        $logEntry = Activity::create([
            'log_name' => 'test',
            'description' => 'Test log entry',
            'event' => 'test',
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/system-logs/{$logEntry->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Log entry deleted successfully',
            ]);

        $this->assertDatabaseMissing('activity_log', ['id' => $logEntry->id]);
    }

    public function test_delete_non_existent_log_returns_404(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/system-logs/99999');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Log entry not found',
            ]);
    }

    public function test_view_non_existent_log_returns_404(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs/99999');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Log entry not found',
            ]);
    }

    public function test_admin_can_view_log_stats(): void
    {
        activity()->useLog('auth')->event('login')->log('Login event');
        activity()->useLog('user')->event('updated')->log('User updated');
        activity()->useLog('user')->event('created')->log('User created');

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/system-logs/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'total_logs',
                    'logs_by_log_name',
                    'logs_by_event',
                    'recent_count',
                ],
            ]);

        $this->assertSame(3, $response->json('stats.total_logs'));
    }

    public function test_unauthenticated_user_cannot_access_system_logs(): void
    {
        $response = $this->getJson('/api/admin/system-logs');

        $response->assertUnauthorized();
    }
}
