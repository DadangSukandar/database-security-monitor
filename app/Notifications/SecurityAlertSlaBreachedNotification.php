<?php

namespace App\Notifications;

use App\Models\SecurityAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertSlaBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SecurityAlert $alert) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(sprintf('[SLA BREACH] %s', $this->alert->title))
            ->greeting('Security alert escalation')
            ->line('The response SLA for this alert has been breached.')
            ->line('Severity: '.strtoupper((string) $this->alert->severity))
            ->line('Deadline: '.($this->alert->responseSlaDeadline()?->format('d M Y H:i:s') ?? '-'))
            ->action('Escalate Security Alert', route('security-alerts.show', $this->alert));
    }
}
