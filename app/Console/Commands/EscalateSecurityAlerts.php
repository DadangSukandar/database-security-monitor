<?php

namespace App\Console\Commands;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Services\SecurityAlertNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('security:escalate-alerts')]
#[Description('Notify configured recipients about alerts that breached response SLA')]
class EscalateSecurityAlerts extends Command
{
    public function handle(SecurityAlertNotificationService $notificationService): int
    {
        $alerts = SecurityAlert::query()
            ->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])
            ->whereDoesntHave('histories', fn ($query) => $query->where('action', 'SLA_ESCALATION'))
            ->get()
            ->filter(fn (SecurityAlert $alert): bool => $alert->hasBreachedResponseSla());

        $escalated = 0;

        foreach ($alerts as $alert) {
            DB::transaction(function () use ($alert, $notificationService, &$escalated): void {
                if ($notificationService->sendSlaBreach($alert) === 0) {
                    return;
                }

                SecurityAlertHistory::query()->create([
                    'security_alert_id' => $alert->id,
                    'action' => 'SLA_ESCALATION',
                    'old_status' => $alert->status,
                    'new_status' => $alert->status,
                    'notes' => 'Response SLA breached; escalation notification sent.',
                ]);

                $escalated++;
            });
        }

        $this->info("Escalated {$escalated} security alert(s).");

        return self::SUCCESS;
    }
}
