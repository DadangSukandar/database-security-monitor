<?php

namespace App\Notifications;

use App\Models\SecurityAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertCreatedNotification extends Notification implements ShouldQueue
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
        $description = $this->alert->getAttribute('description');

        return (new MailMessage)
            ->subject(sprintf('[%s] Security Alert: %s', $this->alert->severity, $this->alert->title))
            ->greeting('Security alert detected')
            ->line('A new '.strtoupper((string) $this->alert->severity).' security alert requires attention.')
            ->line('Database: '.($this->alert->database_name ?: 'Unknown'))
            ->line(is_string($description) && $description !== ''
                ? $description
                : 'No additional description is available.')
            ->action('Review Security Alert', route('security-alerts.show', $this->alert));
    }
}
