<?php

namespace App\Observers;

use App\Models\SecurityAlert;
use App\Services\SecurityAlertNotificationService;

class SecurityAlertObserver
{
    public function __construct(
        private SecurityAlertNotificationService $notificationService
    ) {}

    public function created(SecurityAlert $securityAlert): void
    {
        $this->notificationService->sendNewAlert($securityAlert);
    }
}
