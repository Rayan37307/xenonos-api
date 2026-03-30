<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceOrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $serviceOrderId;
    public string $serviceOrderTitle;
    public string $serviceType;
    public string $clientName;
    public ?string $budgetMin;
    public ?string $budgetMax;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        int $serviceOrderId,
        string $serviceOrderTitle,
        string $serviceType,
        string $clientName,
        ?string $budgetMin = null,
        ?string $budgetMax = null
    ) {
        $this->serviceOrderId = $serviceOrderId;
        $this->serviceOrderTitle = $serviceOrderTitle;
        $this->serviceType = $serviceType;
        $this->clientName = $clientName;
        $this->budgetMin = $budgetMin;
        $this->budgetMax = $budgetMax;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New Service Order Proposal: ' . $this->serviceOrderTitle)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new service order has been submitted.')
            ->line('**Service:** ' . $this->serviceType)
            ->line('**Client:** ' . $this->clientName)
            ->line('**Title:** ' . $this->serviceOrderTitle);

        if ($this->budgetMin || $this->budgetMax) {
            $budgetRange = '';
            if ($this->budgetMin) {
                $budgetRange .= '$' . $this->budgetMin;
            }
            if ($this->budgetMin && $this->budgetMax) {
                $budgetRange .= ' - ';
            }
            if ($this->budgetMax) {
                $budgetRange .= '$' . $this->budgetMax;
            }
            $mail->line('**Budget:** ' . $budgetRange);
        }

        return $mail->action('Review Order', url('/admin/service-orders/' . $this->serviceOrderId))
            ->line('Please review this service order proposal at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_order_created',
            'service_order_id' => $this->serviceOrderId,
            'service_order_title' => $this->serviceOrderTitle,
            'service_type' => $this->serviceType,
            'client_name' => $this->clientName,
            'budget_min' => $this->budgetMin,
            'budget_max' => $this->budgetMax,
        ];
    }
}
