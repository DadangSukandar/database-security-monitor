<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Notifications\SecurityAlertCreatedNotification;
use App\Notifications\SecurityAlertSlaBreachedNotification;
use Illuminate\Notifications\Notification as NotificationContract;
use Illuminate\Support\Facades\Notification;

class SecurityAlertNotificationService
{
    public function sendNewAlert(SecurityAlert $alert): int
    {
        if (! in_array(strtoupper((string) $alert->severity), ['HIGH', 'CRITICAL'], true)) {
            return 0;
        }

        return $this->send(new SecurityAlertCreatedNotification($alert));
    }

    public function sendSlaBreach(SecurityAlert $alert): int
    {
        return $this->send(new SecurityAlertSlaBreachedNotification($alert));
    }

    private function send(NotificationContract $notification): int
    {
        /** @var array<int, string> $recipients */
        $recipients = config('services.security_alerts.recipients', []);

        foreach ($recipients as $recipient) {
            Notification::route('mail', $recipient)->notify($notification);
        }

        return count($recipients);
    }
}
