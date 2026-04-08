<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Task;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class AlertService
{
    public function createAlert(array $data): Alert
    {
        return Alert::create([
            'user_id' => $data['user_id'] ?? Auth::id(),
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'severity' => $data['severity'] ?? 'info',
            'status' => 'active',
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'triggered_at' => now(),
        ]);
    }

    public function createTaskOverdueAlert(Task $task): Alert
    {
        return $this->createAlert([
            'user_id' => $task->assigned_to,
            'type' => 'task_overdue',
            'title' => 'Task Overdue',
            'message' => "The task '{$task->title}' is overdue. Due date was {$task->due_date->format('M d, Y')}.",
            'severity' => 'warning',
            'related_entity_id' => $task->id,
            'related_entity_type' => Task::class,
        ]);
    }

    public function createPaymentDueAlert(Invoice $invoice): Alert
    {
        return $this->createAlert([
            'user_id' => $invoice->client->user_id ?? null,
            'type' => 'payment_due',
            'title' => 'Payment Due',
            'message' => "Invoice payment of {$invoice->amount} is due on {$invoice->due_date->format('M d, Y')}.",
            'severity' => $invoice->due_date->isPast() ? 'critical' : 'warning',
            'related_entity_id' => $invoice->id,
            'related_entity_type' => Invoice::class,
        ]);
    }

    public function createProjectDeadlineAlert(Project $project): void
    {
        $workers = $project->workers;
        
        foreach ($workers as $worker) {
            $this->createAlert([
                'user_id' => $worker->id,
                'type' => 'project_deadline',
                'title' => 'Project Deadline Approaching',
                'message' => "Project '{$project->name}' deadline is {$project->end_date->format('M d, Y')}.",
                'severity' => 'warning',
                'related_entity_id' => $project->id,
                'related_entity_type' => Project::class,
            ]);
        }
    }

    public function getUserAlerts(?string $status = null)
    {
        $query = Alert::where('user_id', Auth::id());

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('triggered_at', 'desc')->get();
    }

    public function getActiveAlerts()
    {
        return $this->getUserAlerts('active');
    }

    public function dismissAlert(Alert $alert): void
    {
        $alert->dismiss();
    }

    public function resolveAlert(Alert $alert): void
    {
        $alert->resolve();
    }

    public function getUnreadAlertCount(): int
    {
        return Alert::where('user_id', Auth::id())
            ->where('status', 'active')
            ->count();
    }

    public function checkAndCreateOverdueTaskAlerts(): int
    {
        $overdueTasks = Task::where('status', '!=', 'done')
            ->where('due_date', '<', now())
            ->whereNull('completed_at')
            ->get();

        $created = 0;
        foreach ($overdueTasks as $task) {
            $existingAlert = Alert::where('type', 'task_overdue')
                ->where('related_entity_id', $task->id)
                ->where('status', 'active')
                ->first();

            if (!$existingAlert) {
                $this->createTaskOverdueAlert($task);
                $created++;
            }
        }

        return $created;
    }

    public function checkAndCreatePaymentDueAlerts(): int
    {
        $pendingInvoices = Invoice::where('status', 'pending')
            ->where('due_date', '<', now()->addDays(7))
            ->get();

        $created = 0;
        foreach ($pendingInvoices as $invoice) {
            $existingAlert = Alert::where('type', 'payment_due')
                ->where('related_entity_id', $invoice->id)
                ->where('status', 'active')
                ->first();

            if (!$existingAlert) {
                $this->createPaymentDueAlert($invoice);
                $created++;
            }
        }

        return $created;
    }
}
