<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create clients
        $client1 = User::create([
            'name' => 'John Client',
            'email' => 'client@xenon.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
        $client1->assignRole('client');
        
        Client::create([
            'user_id' => $client1->id,
            'company_name' => 'Acme Corp',
            'phone' => '+1234567890',
            'address' => '123 Business St, City, Country',
        ]);

        $client2 = User::create([
            'name' => 'Sarah Client',
            'email' => 'client2@xenon.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
        $client2->assignRole('client');

        Client::create([
            'user_id' => $client2->id,
            'company_name' => 'TechStart Inc',
            'phone' => '+0987654321',
        ]);

        // Create workers
        $workers = [];
        $workerData = [
            ['name' => 'Alice Developer', 'email' => 'alice@xenon.com'],
            ['name' => 'Bob Designer', 'email' => 'bob@xenon.com'],
            ['name' => 'Charlie Manager', 'email' => 'charlie@xenon.com'],
        ];

        foreach ($workerData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'worker',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('worker');
            $workers[] = $user;
        }

        // Create projects
        $project1 = Project::create([
            'name' => 'Website Redesign',
            'description' => 'Complete redesign of the company website with modern UI/UX',
            'client_id' => $client1->clientProfile->id,
            'status' => 'active',
            'budget' => 15000,
            'deadline' => now()->addDays(60),
        ]);

        $project2 = Project::create([
            'name' => 'Mobile App Development',
            'description' => 'Native iOS and Android app for e-commerce',
            'client_id' => $client2->clientProfile->id,
            'status' => 'active',
            'budget' => 50000,
            'deadline' => now()->addDays(120),
        ]);

        $project3 = Project::create([
            'name' => 'Brand Identity',
            'description' => 'Logo, brand guidelines, and marketing materials',
            'client_id' => $client1->clientProfile->id,
            'status' => 'planning',
            'budget' => 8000,
            'deadline' => now()->addDays(30),
        ]);

        // Assign workers to projects
        $project1->workers()->attach([$workers[0]->id, $workers[1]->id], ['role' => 'worker']);
        $project2->workers()->attach([$workers[0]->id, $workers[2]->id], ['role' => 'worker']);
        $project3->workers()->attach([$workers[1]->id], ['role' => 'lead']);

        // Create tasks for project 1
        $tasks1 = [
            ['title' => 'Design homepage mockup', 'status' => 'completed', 'priority' => 'high', 'assigned_to' => $workers[1]->id, 'progress' => 100],
            ['title' => 'Develop homepage', 'status' => 'in_progress', 'priority' => 'high', 'assigned_to' => $workers[0]->id, 'progress' => 60],
            ['title' => 'Setup CMS integration', 'status' => 'todo', 'priority' => 'medium', 'assigned_to' => $workers[0]->id, 'progress' => 0],
            ['title' => 'Create about page', 'status' => 'todo', 'priority' => 'low', 'assigned_to' => $workers[1]->id, 'progress' => 0],
        ];

        foreach ($tasks1 as $taskData) {
            Task::create([
                'title' => $taskData['title'],
                'description' => 'Task description for ' . $taskData['title'],
                'project_id' => $project1->id,
                'assigned_to' => $taskData['assigned_to'],
                'created_by' => $client1->id,
                'status' => $taskData['status'],
                'priority' => $taskData['priority'],
                'progress' => $taskData['progress'],
                'deadline' => now()->addDays(rand(7, 45)),
                'position' => rand(1, 10),
            ]);
        }

        // Create tasks for project 2
        $tasks2 = [
            ['title' => 'Setup project architecture', 'status' => 'completed', 'priority' => 'urgent', 'assigned_to' => $workers[0]->id, 'progress' => 100],
            ['title' => 'Design app screens', 'status' => 'in_progress', 'priority' => 'high', 'assigned_to' => $workers[1]->id, 'progress' => 40],
            ['title' => 'Implement authentication', 'status' => 'in_progress', 'priority' => 'high', 'assigned_to' => $workers[0]->id, 'progress' => 30],
            ['title' => 'Create API endpoints', 'status' => 'todo', 'priority' => 'medium', 'assigned_to' => $workers[0]->id, 'progress' => 0],
            ['title' => 'Setup push notifications', 'status' => 'todo', 'priority' => 'low', 'assigned_to' => $workers[0]->id, 'progress' => 0],
        ];

        foreach ($tasks2 as $taskData) {
            Task::create([
                'title' => $taskData['title'],
                'description' => 'Task description for ' . $taskData['title'],
                'project_id' => $project2->id,
                'assigned_to' => $taskData['assigned_to'],
                'created_by' => $client2->id,
                'status' => $taskData['status'],
                'priority' => $taskData['priority'],
                'progress' => $taskData['progress'],
                'deadline' => now()->addDays(rand(15, 90)),
                'position' => rand(1, 10),
            ]);
        }

        // Create some messages
        Message::create([
            'sender_id' => $client1->id,
            'project_id' => $project1->id,
            'message' => 'Looking forward to seeing the progress on the website redesign!',
            'is_read' => true,
            'read_at' => now(),
        ]);

        Message::create([
            'sender_id' => $workers[0]->id,
            'project_id' => $project1->id,
            'message' => 'Homepage development is 60% complete. Should be ready for review by end of week.',
            'is_read' => true,
            'read_at' => now(),
        ]);

        Message::create([
            'sender_id' => $workers[1]->id,
            'receiver_id' => $workers[0]->id,
            'message' => 'Hey, can you review the designs I uploaded?',
            'is_read' => false,
        ]);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Clients: client@xenon.com, client2@xenon.com');
        $this->command->info('Workers: alice@xenon.com, bob@xenon.com, charlie@xenon.com');
        $this->command->info('All passwords: password');
    }
}
