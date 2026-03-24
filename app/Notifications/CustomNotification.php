<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;
    public string $message;
    public string $type;
    public ?array $data;
    public bool $sendEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        string $type = 'custom',
        ?array $data = null,
        bool $sendEmail = false
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->sendEmail = $sendEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
        ], $this->data ?? []);
    }
}
